<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_an_image_for_a_signed_in_admin(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('services/photo.jpg', 'x');

        $this->actingAs(User::factory()->make())
            ->postJson('/admin/media-library/delete', ['path' => 'services/photo.jpg'])
            ->assertOk();

        Storage::disk('public')->assertMissing('services/photo.jpg');
    }

    public function test_it_rejects_guests_traversal_and_non_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('services/photo.jpg', 'x');
        Storage::disk('public')->put('services/notes.txt', 'x');

        $this->postJson('/admin/media-library/delete', ['path' => 'services/photo.jpg'])
            ->assertStatus(302); // redirected to login by the auth middleware

        $user = User::factory()->make();

        $this->actingAs($user)
            ->postJson('/admin/media-library/delete', ['path' => '../../.env'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson('/admin/media-library/delete', ['path' => 'services/notes.txt'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson('/admin/media-library/delete', ['path' => 'services/missing.jpg'])
            ->assertStatus(422);

        Storage::disk('public')->assertExists('services/photo.jpg');
        Storage::disk('public')->assertExists('services/notes.txt');
    }
}
