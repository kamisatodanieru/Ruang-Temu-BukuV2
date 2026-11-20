<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_non_admin_users_cannot_manage_categories(): void
    {
        $anggota = User::factory()->anggota()->create();
        $petugas = User::factory()->petugas()->create();
        $category = Category::factory()->create();

        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));

        $this->actingAs($anggota)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($anggota)->post(route('admin.categories.store'), ['name' => 'Baru'])->assertForbidden();

        $this->actingAs($petugas)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($petugas)->put(route('admin.categories.update', $category), ['name' => 'Ubah'])->assertForbidden();
        $this->actingAs($petugas)->delete(route('admin.categories.destroy', $category))->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_unused_category(): void
    {
        $admin = User::factory()->admin()->create();

        $storeRes = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Ensiklopedia',
        ]);
        $storeRes->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('name', 'Ensiklopedia')->firstOrFail();

        // Duplikat nama ditolak
        $dupRes = $this->actingAs($admin)->from(route('admin.categories.index'))->post(route('admin.categories.store'), [
            'name' => 'Ensiklopedia',
        ]);
        $dupRes->assertSessionHasErrors('name');

        // Update kategori
        $updateRes = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Ensiklopedia Umum',
        ]);
        $updateRes->assertRedirect(route('admin.categories.index'));
        $this->assertSame('Ensiklopedia Umum', $category->fresh()->name);

        // Hapus kategori yang tidak direferensikan buku
        $delRes = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));
        $delRes->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_referenced_by_books_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Fiksi Ilmiah']);
        Book::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
