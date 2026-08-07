<?php

namespace App\Http\Controllers;

use App\Support\ImageStore;
use App\Models\Record;
use App\Models\RecordMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecordMediaController extends Controller
{
    public function store(Request $request, Record $record)
    {
        $this->authorize('update', $record);

        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['image', 'max:4096'],
        ]);

        $nextOrder = $record->media()->max('sort_order') + 1;

        foreach ($request->file('photos') as $index => $photo) {
            $path = ImageStore::put($photo, 'record-media');
            $record->media()->create(['path' => $path, 'sort_order' => $nextOrder + $index]);
        }

        return back()->with('status', 'media-added');
    }

    public function destroy(RecordMedia $media)
    {
        $this->authorize('update', $media->record);

        ImageStore::delete($media->path);
        $media->delete();

        return back()->with('status', 'media-removed');
    }
}
