<?php

namespace Tests\Feature\Admin;

use App\Models\Stadium;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StadiumControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_update_sets_cover_to_first_new_gallery_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $stadium = Stadium::query()->create([
            'api_venue_id' => 19445,
            'name' => 'BC Place',
            'city' => 'Vancouver, British Columbia',
            'country' => 'Canada',
            'address' => '777 Pacific Boulevard, False Creek',
            'capacity' => 54405,
            'surface' => 'artificial turf',
            'image_url' => 'https://media.api-sports.io/football/venues/19445.png',
            'image_gallery' => [
                'https://media.api-sports.io/football/venues/19445.png',
            ],
        ]);

        $newOne = UploadedFile::fake()->image('stadium-new-1.jpg', 1200, 800);
        $newTwo = UploadedFile::fake()->image('stadium-new-2.jpg', 1200, 800);

        $this->actingAs($admin)
            ->put(route('admin.stadiums.update', $stadium), [
                'api_venue_id' => $stadium->api_venue_id,
                'name' => $stadium->name,
                'city' => $stadium->city,
                'country' => $stadium->country,
                'address' => $stadium->address,
                'capacity' => $stadium->capacity,
                'surface' => $stadium->surface,
                'image_url' => $stadium->image_url,
                'gallery_existing' => $stadium->image_gallery,
                'gallery_images' => [$newOne, $newTwo],
            ])
            ->assertRedirect();

        $stadium->refresh();

        $this->assertNotSame('https://media.api-sports.io/football/venues/19445.png', $stadium->image_url);
        $this->assertStringStartsWith('stadiums/gallery/', $stadium->image_url);
        $this->assertIsArray($stadium->image_gallery);
        $this->assertGreaterThanOrEqual(2, count($stadium->image_gallery));

        foreach ($stadium->image_gallery as $path) {
            if (is_string($path) && str_starts_with($path, 'stadiums/gallery/')) {
                Storage::disk('public')->assertExists($path);
            }
        }
    }
}

