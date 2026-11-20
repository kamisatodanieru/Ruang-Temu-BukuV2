<?php

namespace Tests\Feature\Catalog;

use App\Models\Book;
use App\Models\Category;
use App\Models\Tag;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_can_view_catalog_and_book_detail(): void
    {
        $category = Category::factory()->create(['name' => 'Sains Komputer']);
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Arsitektur Perangkat Lunak Aman',
            'author' => 'Dr. Andi Pratama',
            'total_stock' => 5,
            'available_stock' => 5,
        ]);

        $indexResponse = $this->get(route('catalog.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Arsitektur Perangkat Lunak Aman');
        $indexResponse->assertSee('Dr. Andi Pratama');

        $detailResponse = $this->get(route('catalog.show', $book));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Arsitektur Perangkat Lunak Aman');
        $detailResponse->assertSee('Masuk untuk Meminjam');
    }

    public function test_catalog_search_and_filters_work_accurately(): void
    {
        $catTeknologi = Category::factory()->create(['name' => 'Teknologi']);
        $catSastra = Category::factory()->create(['name' => 'Sastra']);

        Book::factory()->create([
            'category_id' => $catTeknologi->id,
            'title' => 'Pemrograman Laravel Modern',
            'author' => 'Rudi Hartono',
            'total_stock' => 3,
            'available_stock' => 2,
        ]);

        Book::factory()->create([
            'category_id' => $catSastra->id,
            'title' => 'Senja di Pelabuhan',
            'author' => 'Siti Aminah',
            'total_stock' => 2,
            'available_stock' => 0,
        ]);

        // Pencarian berdasarkan judul/pengarang
        $searchRes = $this->get(route('catalog.index', ['search' => 'Laravel']));
        $searchRes->assertOk();
        $searchRes->assertSee('Pemrograman Laravel Modern');
        $searchRes->assertDontSee('Senja di Pelabuhan');

        // Filter kategori
        $catRes = $this->get(route('catalog.index', ['category' => $catSastra->id]));
        $catRes->assertOk();
        $catRes->assertSee('Senja di Pelabuhan');
        $catRes->assertDontSee('Pemrograman Laravel Modern');

        // Filter ketersediaan (available)
        $availRes = $this->get(route('catalog.index', ['availability' => 'available']));
        $availRes->assertOk();
        $availRes->assertSee('Pemrograman Laravel Modern');
        $availRes->assertDontSee('Senja di Pelabuhan');
    }

    public function test_soft_deleted_book_is_hidden_from_catalog_and_detail_returns_404(): void
    {
        $book = Book::factory()->create([
            'title' => 'Buku Dihapus Sementara',
        ]);

        $book->delete();

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('Buku Dihapus Sementara');

        $this->get(route('catalog.show', $book->id))
            ->assertNotFound();
    }

    public function test_catalog_can_be_filtered_by_tag_and_displays_tags_on_detail(): void
    {
        $book1 = Book::factory()->create(['title' => 'Petualangan Di Luar Angkasa']);
        $book2 = Book::factory()->create(['title' => 'Resep Masakan Nusantara']);

        $tagSciFi = Tag::createWithSlug('Sci-Fi');
        $tagKuliner = Tag::createWithSlug('Kuliner');

        $book1->tags()->attach($tagSciFi);
        $book2->tags()->attach($tagKuliner);

        // Filter katalog berdasarkan tag Sci-Fi
        $response = $this->get(route('catalog.index', ['tag' => 'sci-fi']));
        $response->assertOk();
        $response->assertSee('Petualangan Di Luar Angkasa');
        $response->assertDontSee('Resep Masakan Nusantara');

        // Detail menampilkan tag
        $detailResponse = $this->get(route('catalog.show', $book1));
        $detailResponse->assertOk();
        $detailResponse->assertSee('#Sci-Fi');
    }
}
