<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    public function index()
    {
        return response()->json(['programs' => Program::all()]);
    }

    public function show($id)
    {
        $program = Program::find($id);
        if (!$program) return response()->json(['error' => 'Program not found.'], 404);
        return response()->json($program);
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

        $program = Program::create([
            'id' => (string) Str::random(10),
            'title' => $title,
            'description' => $request->input('description'),
            'schedule' => $request->input('schedule'),
            'image_path' => $imagePath,
        ]);

        return response()->json($program, 201);
    }

    public function update($id, Request $request)
    {
        $program = Program::find($id);
        if (!$program) return response()->json(['error' => 'Program not found.'], 404);

        $imagePath = $request->input('imagePath', $program->image_path);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
        }

        $program->update([
            'title' => $request->filled('title') ? trim((string)$request->input('title')) : $program->title,
            'description' => $request->input('description', $program->description),
            'schedule' => $request->input('schedule', $program->schedule),
            'image_path' => $imagePath,
        ]);

        return response()->json($program);
    }

    public function destroy($id)
    {
        $program = Program::find($id);
        if (!$program) return response()->json(['error' => 'Program not found.'], 404);
        $program->delete();
        return response()->json(['success' => true]);
    }
}
