<?php

namespace App\Http\Controllers;

use App\Support\ImageStore;
use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Models\Person;
use App\Models\Record;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecordController extends Controller
{
    public function store(StoreRecordRequest $request, Person $person)
    {
        $record = $person->records()->create([
            ...$request->safe()->except('photos'),
            'created_by_person_id' => $request->user()->person?->id,
        ]);

        foreach ($request->file('photos', []) as $index => $photo) {
            $path = ImageStore::put($photo, 'record-media');
            $record->media()->create(['path' => $path, 'sort_order' => $index]);
        }

        return back()->with('status', 'record-added');
    }

    public function update(UpdateRecordRequest $request, Record $record)
    {
        $record->update($request->validated());

        return back()->with('status', 'record-updated');
    }

    public function destroy(Record $record)
    {
        $this->authorize('delete', $record);

        foreach ($record->media as $media) {
            ImageStore::delete($media->path);
        }

        $record->delete();

        return back()->with('status', 'record-removed');
    }
}
