<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_non_admin_cannot_access_book_management_routes(): void
    {
        $anggota = User::factory()->anggota()->create();
        $petugas = User::factory()->petugas()->create();
        $book = Book::factory()->create();

        $this->actingAs($anggota)->get(route('admin.books.index'))->assertForbidden();
        $this->actingAs($anggota)->post(route('admin.books.store'), [])->assertForbidden();

        $this->actingAs($petugas)->get(route('admin.books.index'))->assertForbidden();
        $this->actingAs($petugas)->put(route('admin.books.update', $book), [])->assertForbidden();
        $this->actingAs($petugas)->delete(route('admin.books.destroy', $book))->assertForbidden();
    }

    public function test_admin_can_create_book_with_valid_cover_and_server_calculates_available_stock(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $cover = $this->createValidPngUpload('../../traversal_attempt.png', 100, 120);

        $response = $this->actingAs($admin)->post(route('admin.books.store'), [
            'category_id' => $category->id,
            'title' => 'Keamanan Aplikasi Web',
            'author' => 'Budi Raharjo',
            'publisher' => 'Informatika',
            'isbn' => '978-602-1234-56-7',
            'publication_year' => 2025,
            'total_stock' => 7,
            'available_stock' => 999, // Injeksi dari client harus diabaikan server
            'description' => 'Panduan lengkap OWASP.',
            'cover' => $cover,
        ]);

        $response->assertRedirect(route('admin.books.index'));

        $book = Book::query()->where('isbn', '978-602-1234-56-7')->firstOrFail();

        $this->assertSame(7, $book->total_stock);
        $this->assertSame(7, $book->available_stock);
        $this->assertNotNull($book->cover_path);
        $this->assertStringStartsWith('covers/', $book->cover_path);
        $this->assertStringNotContainsString('..', $book->cover_path);
        $this->assertStringNotContainsString('traversal_attempt', $book->cover_path);
        Storage::disk('public')->assertExists($book->cover_path);
    }

    public function test_disallowed_and_oversized_cover_files_are_rejected(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        // SVG ditolak
        $svgFile = UploadedFile::fake()->create('xss.svg', 20, 'image/svg+xml');
        $resSvg = $this->actingAs($admin)->from(route('admin.books.create'))->post(route('admin.books.store'), [
            'category_id' => $category->id,
            'title' => 'Buku Test SVG',
            'author' => 'Penulis',
            'total_stock' => 3,
            'cover' => $svgFile,
        ]);
        $resSvg->assertSessionHasErrors('cover');

        // File terlalu besar (> 2048 KB) ditolak
        $largeImage = $this->createValidPngUpload('huge.png', 100, 120)->size(3000);
        $resLarge = $this->actingAs($admin)->from(route('admin.books.create'))->post(route('admin.books.store'), [
            'category_id' => $category->id,
            'title' => 'Buku Test Besar',
            'author' => 'Penulis',
            'total_stock' => 3,
            'cover' => $largeImage,
        ]);
        $resLarge->assertSessionHasErrors('cover');
    }

    private function createValidPngUpload(string $filename, int $width = 100, int $height = 100): UploadedFile
    {
        $ihdrData = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        $ihdrChunk = pack('N', strlen($ihdrData)).'IHDR'.$ihdrData.pack('N', crc32('IHDR'.$ihdrData));

        $rawScanlines = str_repeat("\x00".str_repeat("\x4F\x46\xE5", $width), $height);
        $idatData = gzcompress($rawScanlines);
        $idatChunk = pack('N', strlen($idatData)).'IDAT'.$idatData.pack('N', crc32('IDAT'.$idatData));

        $iendChunk = pack('N', 0).'IEND'.pack('N', crc32('IEND'));
        $pngBinary = "\x89PNG\r\n\x1a\n".$ihdrChunk.$idatChunk.$iendChunk;

        return UploadedFile::fake()->createWithContent($filename, $pngBinary);
    }

    public function test_updating_total_stock_recalculates_available_stock_and_blocks_below_borrowed_count(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create([
            'total_stock' => 5,
            'available_stock' => 3,
        ]);

        // 2 eksemplar sedang dipinjam (status = borrowed)
        Loan::factory()->count(2)->borrowed()->create([
            'book_id' => $book->id,
        ]);

        // Percobaan menurunkan total_stock di bawah 2 harus ditolak
        $failRes = $this->actingAs($admin)->from(route('admin.books.edit', $book))->put(route('admin.books.update', $book), [
            'category_id' => $book->category_id,
            'title' => $book->title,
            'author' => $book->author,
            'total_stock' => 1,
        ]);
        $failRes->assertSessionHasErrors('total_stock');
        $this->assertSame(5, $book->fresh()->total_stock);
        $this->assertSame(3, $book->fresh()->available_stock);

        // Mengubah total_stock menjadi 8 -> available_stock wajib menjadi 8 - 2 = 6
        $okRes = $this->actingAs($admin)->put(route('admin.books.update', $book), [
            'category_id' => $book->category_id,
            'title' => $book->title,
            'author' => $book->author,
            'total_stock' => 8,
            'available_stock' => 100, // Harus diabaikan
        ]);
        $okRes->assertRedirect(route('admin.books.index'));
        $this->assertSame(8, $book->fresh()->total_stock);
        $this->assertSame(6, $book->fresh()->available_stock);
    }

    public function test_book_cannot_be_soft_deleted_when_it_has_pending_or_borrowed_loans(): void
    {
        $admin = User::factory()->admin()->create();
        $bookWithPending = Book::factory()->create();
        $bookWithReturned = Book::factory()->create();

        Loan::factory()->create([
            'book_id' => $bookWithPending->id,
        ]);

        Loan::factory()->returned()->create([
            'book_id' => $bookWithReturned->id,
        ]);

        // Buku dengan transaksi Pending ditolak untuk dihapus
        $blockedRes = $this->actingAs($admin)->delete(route('admin.books.destroy', $bookWithPending));
        $blockedRes->assertRedirect(route('admin.books.index'));
        $blockedRes->assertSessionHas('error');
        $this->assertNull($bookWithPending->fresh()->deleted_at);

        // Buku yang hanya memiliki histori Returned berhasil di-soft-delete dan histori tetap utuh
        $allowedRes = $this->actingAs($admin)->delete(route('admin.books.destroy', $bookWithReturned));
        $allowedRes->assertRedirect(route('admin.books.index'));
        $this->assertSoftDeleted('books', ['id' => $bookWithReturned->id]);
        $this->assertDatabaseHas('loans', ['book_id' => $bookWithReturned->id]);
    }

    public function test_admin_can_create_and_update_book_with_up_to_five_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.books.store'), [
            'category_id' => $category->id,
            'title' => 'Dune: Frank Herbert',
            'author' => 'Frank Herbert',
            'total_stock' => 5,
            'tags' => 'Sci-Fi, Space Opera, Adventure, Classic, Best Seller',
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $book = Book::query()->where('title', 'Dune: Frank Herbert')->firstOrFail();

        $this->assertCount(5, $book->tags);
        $this->assertTrue($book->tags->pluck('name')->contains('Sci-Fi'));
        $this->assertTrue($book->tags->pluck('name')->contains('Best Seller'));

        // Update tags
        $updateRes = $this->actingAs($admin)->put(route('admin.books.update', $book), [
            'category_id' => $category->id,
            'title' => 'Dune: Frank Herbert',
            'author' => 'Frank Herbert',
            'total_stock' => 5,
            'tags' => ['Sci-Fi', 'Epic Fantasy'],
        ]);

        $updateRes->assertRedirect(route('admin.books.index'));
        $this->assertCount(2, $book->fresh()->tags);
        $this->assertTrue($book->fresh()->tags->pluck('name')->contains('Epic Fantasy'));
    }

    public function test_more_than_five_tags_is_rejected_by_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->from(route('admin.books.create'))->post(route('admin.books.store'), [
            'category_id' => $category->id,
            'title' => 'Buku Terlalu Banyak Tag',
            'author' => 'Penulis',
            'total_stock' => 3,
            'tags' => 'Tag 1, Tag 2, Tag 3, Tag 4, Tag 5, Tag 6',
        ]);

        $response->assertRedirect(route('admin.books.create'));
        $response->assertSessionHasErrors(['tags']);
    }
}
