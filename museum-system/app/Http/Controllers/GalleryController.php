<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function index()
    {
        return response()->json(['gallery' => GalleryItem::all()]);
    }

    public function show($id)
    {
        $item = GalleryItem::find($id);
        if (!$item) return response()->json(['error' => 'Item not found.'], 404);
        return response()->json($item);
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

        $item = GalleryItem::create([
            'id' => (string) Str::random(10),
            'title' => $title,
            'category' => $request->input('category'),
            'image_path' => $imagePath,
            'description' => $request->input('description'),
        ]);

        return response()->json($item, 201);
    }

    public function update($id, Request $request)
    {
        $item = GalleryItem::find($id);
        if (!$item) return response()->json(['error' => 'Item not found.'], 404);

        $imagePath = $request->input('imagePath', $item->image_path);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
        }

        $item->update([
            'title' => $request->filled('title') ? trim((string)$request->input('title')) : $item->title,
            'category' => $request->input('category', $item->category),
            'image_path' => $imagePath,
            'description' => $request->input('description', $item->description),
        ]);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = GalleryItem::find($id);
        if (!$item) return response()->json(['error' => 'Item not found.'], 404);
        $item->delete();
        return response()->json(['success' => true]);
    }
}
