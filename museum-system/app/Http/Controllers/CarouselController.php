<?php

namespace App\Http\Controllers;

use App\Models\CarouselSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarouselController extends Controller
{
    public function index()
    {
        $slides = CarouselSlide::orderBy('order', 'asc')->get();
        return response()->json(['slides' => $slides]);
    }

    public function store(Request $request)
    {
        $imagePath = $request->input('imagePath');
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
        }

        if (!$imagePath) {
            return response()->json(['error' => 'An image is required.'], 400);
        }

        $slide = CarouselSlide::create([
            'id' => (string) Str::random(10),
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'image_path' => $imagePath,
            'order' => (int) $request->input('order', 0),
            'is_active' => $request->boolean('isActive', true),
        ]);

        return response()->json($slide, 201);
    }

    public function update($id, Request $request)
    {
        $slide = CarouselSlide::find($id);
        if (!$slide) return response()->json(['error' => 'Slide not found.'], 404);

        $imagePath = $request->input('imagePath', $slide->image_path);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
        }

        $slide->update([
            'title' => $request->input('title', $slide->title),
            'subtitle' => $request->input('subtitle', $slide->subtitle),
            'image_path' => $imagePath,
            'order' => $request->has('order') ? (int)$request->input('order') : $slide->order,
            'is_active' => $request->has('isActive') ? $request->boolean('isActive') : $slide->is_active,
        ]);

        return response()->json($slide);
    }

    public function destroy($id)
    {
        $slide = CarouselSlide::find($id);
        if (!$slide) return response()->json(['error' => 'Slide not found.'], 404);
        $slide->delete();
        return response()->json(['success' => true]);
    }
}
