<?php

namespace App\Http\Controllers;

use App\Models\MuseumEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(['events' => MuseumEvent::all()]);
    }

    public function show($id)
    {
        $event = MuseumEvent::find($id);
        if (!$event) return response()->json(['error' => 'Event not found.'], 404);
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $title = trim((string)$request->input('title'));
        if (!$title) return response()->json(['error' => 'Title is required.'], 400);

        $imagePath = $request->input('imagePath');
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
        }

        $event = MuseumEvent::create([
            'id' => (string) Str::random(10),
            'title' => $title,
            'description' => $request->input('description'),
            'date' => $request->input('date'),
            'location' => $request->input('location'),
            'image_path' => $imagePath,
        ]);

        return response()->json($event, 201);
    }

    public function update($id, Request $request)
    {
        $event = MuseumEvent::find($id);
        if (!$event) return response()->json(['error' => 'Event not found.'], 404);

        $imagePath = $request->input('imagePath', $event->image_path);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
        }

        $event->update([
            'title' => $request->filled('title') ? trim((string)$request->input('title')) : $event->title,
            'description' => $request->input('description', $event->description),
            'date' => $request->input('date', $event->date),
            'location' => $request->input('location', $event->location),
            'image_path' => $imagePath,
        ]);

        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = MuseumEvent::find($id);
        if (!$event) return response()->json(['error' => 'Event not found.'], 404);
        $event->delete();
        return response()->json(['success' => true]);
    }
}
