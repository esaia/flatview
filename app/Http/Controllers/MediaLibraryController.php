<?php

namespace App\Http\Controllers;

use App\Filament\Support\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Deletes an image from the media-library picker.
 *
 * The picker is a custom field inside a Filament action modal, and the modal is
 * hosted by whichever resource page opened it — there is no single Livewire
 * component to hang a delete method on, so the tile calls this route instead.
 */
class MediaLibraryController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        $deleted = MediaLibrary::delete((string) $request->input('path', ''));

        if (! $deleted) {
            return response()->json(['message' => 'That image could not be deleted.'], 422);
        }

        return response()->json(['deleted' => true]);
    }
}
