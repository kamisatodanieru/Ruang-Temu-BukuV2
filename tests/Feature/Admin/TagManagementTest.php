<?php

namespace Tests\Feature\Admin;

use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_non_admin_cannot_access_tag_management(): void
    {
        $anggota = User::factory()->anggota()->create();
        $petugas = User::factory()->petugas()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($anggota)->get(route('admin.tags.index'))->assertForbidden();
        $this->actingAs($anggota)->post(route('admin.tags.store'), ['name' => 'Fiksi'])->assertForbidden();

        $this->actingAs($petugas)->get(route('admin.tags.index'))->assertForbidden();
        $this->actingAs($petugas)->put(route('admin.tags.update', $tag), ['name' => 'Fiksi Baru'])->assertForbidden();
        $this->actingAs($petugas)->delete(route('admin.tags.destroy', $tag))->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_tag(): void
    {
        $admin = User::factory()->admin()->create();

        // Create
        $response = $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Fiksi Ilmiah',
        ]);
        $response->assertRedirect(route('admin.tags.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tags', [
            'name' => 'Fiksi Ilmiah',
            'slug' => 'fiksi-ilmiah',
        ]);

        $tag = Tag::query()->where('slug', 'fiksi-ilmiah')->firstOrFail();

        // Update
        $updateResponse = $this->actingAs($admin)->put(route('admin.tags.update', $tag), [
            'name' => 'Sci-Fi Fantasi',
        ]);
        $updateResponse->assertRedirect(route('admin.tags.index'));
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Sci-Fi Fantasi',
            'slug' => 'sci-fi-fantasi',
        ]);

        // Delete
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag));
        $deleteResponse->assertRedirect(route('admin.tags.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_duplicate_tag_name_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Tag::createWithSlug('Teknologi');

        $response = $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Teknologi',
        ]);

        $response->assertSessionHasErrors(['name']);

        // Nama berbeda tetapi menghasilkan slug yang sama ("tek-nologi!" -> "tek-nologi") juga ditolak tanpa error 500
        Tag::createWithSlug('Tek-Nologi');
        $slugCollisionRes = $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Tek Nologi!',
        ]);
        $slugCollisionRes->assertSessionHasErrors(['name']);
    }
}
