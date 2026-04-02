<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stadium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StadiumController extends Controller
{
    private static ?bool $hasImageGalleryColumn = null;

    public function index(): Response
    {
        $search = request('search');

        $stadiums = Stadium::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Stadiums/Index', [
            'filters' => request()->only('search'),
            'stadiums' => $stadiums,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $payload = $this->buildPayload($request, $validated, null);

        Stadium::create($payload);

        return redirect()->back()->with('success', 'Stadium created successfully');
    }

    public function update(Request $request, Stadium $stadium)
    {
        $validated = $this->validatePayload($request);
        $payload = $this->buildPayload($request, $validated, $stadium);

        $stadium->update($payload);

        return redirect()->back()->with('success', 'Stadium updated successfully');
    }

    public function destroy(Stadium $stadium)
    {
        $this->cleanupStoredStadiumImages($stadium);
        $stadium->delete();

        return redirect()->back()->with('success', 'Stadium deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:stadiums,id'],
        ]);

        Stadium::query()
            ->whereIn('id', $validated['ids'])
            ->get()
            ->each(fn (Stadium $stadium) => $this->cleanupStoredStadiumImages($stadium));

        $deleted = Stadium::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted stadiums deleted successfully",
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'api_venue_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer'],
            'surface' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'cover_image_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'gallery_existing' => ['nullable', 'array'],
            'gallery_existing.*' => ['nullable', 'string', 'max:2048'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
        ]);
    }

    private function buildPayload(Request $request, array $validated, ?Stadium $stadium): array
    {
        $supportsImageGallery = $this->supportsImageGallery();

        $incomingImageUrl = array_key_exists('image_url', $validated)
            ? trim((string) $validated['image_url'])
            : null;

        if ($incomingImageUrl === '' || strtolower((string) $incomingImageUrl) === 'null') {
            $incomingImageUrl = null;
        }

        $payload = [
            'api_venue_id' => array_key_exists('api_venue_id', $validated)
                ? $validated['api_venue_id']
                : $stadium?->api_venue_id,
            'name' => $validated['name'],
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'address' => $validated['address'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'surface' => $validated['surface'] ?? null,
            'image_url' => $incomingImageUrl,
        ];

        $gallery = [];
        if ($supportsImageGallery) {
            $previousGallery = collect($stadium?->image_gallery ?? [])
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->values()
                ->all();

            $gallery = $validated['gallery_existing'] ?? ($stadium?->image_gallery ?? []);
            $gallery = collect($gallery)
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->values()
                ->all();

            if ($stadium) {
                $removedGalleryPaths = array_diff($previousGallery, $gallery);
                foreach ($removedGalleryPaths as $path) {
                    if (is_string($path) && $path !== '' && ! str_starts_with($path, 'http')) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        }

        if ($request->hasFile('cover_image_file')) {
            $oldCover = $stadium?->image_url;
            if ($oldCover && ! str_starts_with($oldCover, 'http')) {
                Storage::disk('public')->delete($oldCover);
            }

            $payload['image_url'] = $request->file('cover_image_file')->store('stadiums', 'public');
        } elseif ($stadium && array_key_exists('image_url', $validated) && $payload['image_url'] === null) {
            $oldCover = $stadium->image_url;
            if ($oldCover && ! str_starts_with($oldCover, 'http')) {
                Storage::disk('public')->delete($oldCover);
            }
        }

        $uploadedGalleryPaths = [];
        $incomingGalleryFiles = $request->file('gallery_images', []);

        if (! $supportsImageGallery && count($incomingGalleryFiles) > 1) {
            throw ValidationException::withMessages([
                'gallery_images' => 'Multi-image gallery is not available yet in this environment. Run migrations to add the stadiums.image_gallery column.',
            ]);
        }

        if ($supportsImageGallery && $request->hasFile('gallery_images')) {
            foreach ($incomingGalleryFiles as $file) {
                $storedPath = $file->store('stadiums/gallery', 'public');
                $gallery[] = $storedPath;
                $uploadedGalleryPaths[] = $storedPath;
            }
        } elseif (! $supportsImageGallery && $request->hasFile('gallery_images')) {
            $oldCover = $stadium?->image_url;
            if ($oldCover && ! str_starts_with($oldCover, 'http')) {
                Storage::disk('public')->delete($oldCover);
            }

            $payload['image_url'] = $incomingGalleryFiles[0]->store('stadiums', 'public');
        }

        if ($supportsImageGallery) {
            $payload['image_gallery'] = array_values(array_unique($gallery));
        }

        if ($supportsImageGallery && ! empty($uploadedGalleryPaths)) {
            // When new gallery images are uploaded, use the first new image as cover.
            $payload['image_url'] = $uploadedGalleryPaths[0];
        }

        if (
            $supportsImageGallery &&
            ! $payload['image_url'] &&
            ! empty($payload['image_gallery'])
        ) {
            $payload['image_url'] = $payload['image_gallery'][0];
        }

        return $payload;
    }

    private function cleanupStoredStadiumImages(Stadium $stadium): void
    {
        if ($stadium->image_url && ! str_starts_with($stadium->image_url, 'http')) {
            Storage::disk('public')->delete($stadium->image_url);
        }

        if (! $this->supportsImageGallery()) {
            return;
        }

        foreach (($stadium->image_gallery ?? []) as $path) {
            if (is_string($path) && $path !== '' && ! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function supportsImageGallery(): bool
    {
        if (self::$hasImageGalleryColumn === null) {
            self::$hasImageGalleryColumn = Schema::hasColumn('stadiums', 'image_gallery');
        }

        return self::$hasImageGalleryColumn;
    }
}
