<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exhibit;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\ScanEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExhibitController extends Controller
{
    protected function enrichExhibit($exhibit)
    {
        $ratings = Rating::where('exhibit_id', $exhibit->id)->get();
        $count = $ratings->count();
        $avg = $count > 0 ? round($ratings->avg('rating'), 1) : 0;
        $favCount = Favorite::where('exhibit_id', $exhibit->id)->count();

        $data = $exhibit->toArray();
        $data['ratingAverage'] = $avg;
        $data['ratingCount'] = $count;
        $data['favoriteCount'] = $favCount;

        return $data;
    }

    public function index()
    {
        $exhibits = Exhibit::all()->map(fn($e) => $this->enrichExhibit($e));
        return response()->json(['exhibits' => $exhibits]);
    }

    public function recommended(Request $request)
    {
        $limit = (int) $request->query('limit', 6);
        $exhibits = Exhibit::all()
            ->map(fn($e) => $this->enrichExhibit($e))
            ->sortByDesc('ratingAverage')
            ->values()
            ->take($limit);

        return response()->json(['exhibits' => $exhibits]);
    }

    public function categories()
    {
        $cats = Category::pluck('name')->values()->all();
        return response()->json(['categories' => $cats]);
    }

    public function show($code)
    {
        $exhibit = Exhibit::where('code', $code)->orWhere('id', $code)->first();
        if (!$exhibit) {
            return response()->json(['error' => 'Exhibit not found.'], 404);
        }

        return response()->json(['exhibit' => $this->enrichExhibit($exhibit)]);
    }

    public function qr($code, Request $request)
    {
        $exhibit = Exhibit::where('code', $code)->orWhere('id', $code)->first();
        if (!$exhibit) {
            return response()->json(['error' => 'Exhibit not found.'], 404);
        }

        $siteUrl = env('SITE_URL', $request->root());
        $url = rtrim($siteUrl, '/') . '/exhibit.html?code=' . urlencode($exhibit->code);

        $svg = (string) QrCode::size(300)->margin(2)->generate($url);
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function ratings($code)
    {
        $exhibit = Exhibit::where('code', $code)->orWhere('id', $code)->first();
        if (!$exhibit) {
            return response()->json(['error' => 'Exhibit not found.'], 404);
        }

        $all = Rating::where('exhibit_id', $exhibit->id)->orderBy('created_at', 'desc')->get();
        $count = $all->count();
        $avg = $count > 0 ? round($all->avg('rating'), 1) : 0;

        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($all as $r) {
            $val = (int)$r->rating;
            if (isset($dist[$val])) $dist[$val]++;
        }

        return response()->json([
            'summary' => [
                'average' => $avg,
                'count' => $count,
                'distribution' => $dist,
            ],
            'ratings' => $all,
        ]);
    }

    public function submitRating($code, Request $request)
    {
        $exhibit = Exhibit::where('code', $code)->orWhere('id', $code)->first();
        if (!$exhibit) {
            return response()->json(['error' => 'Exhibit not found.'], 404);
        }

        $visitorId = $request->input('visitorId');
        $rating = (int) $request->input('rating');
        $comment = $request->input('comment');
        $visitorName = $request->input('visitorName', 'Visitor');

        if (!$visitorId || !is_string($visitorId)) {
            return response()->json(['error' => 'A visitorId is required.'], 400);
        }
        if ($rating < 1 || $rating > 5) {
            return response()->json(['error' => 'Rating must be a whole number from 1 to 5.'], 400);
        }

        $record = Rating::create([
            'visitor_id' => $visitorId,
            'visitor_name' => mb_substr(trim($visitorName ?: 'Visitor'), 0, 50),
            'exhibit_id' => $exhibit->id,
            'rating' => $rating,
            'comment' => $comment ? mb_substr(trim($comment), 0, 500) : null,
        ]);

        $all = Rating::where('exhibit_id', $exhibit->id)->get();
        $count = $all->count();
        $avg = $count > 0 ? round($all->avg('rating'), 1) : 0;

        return response()->json([
            'rating' => $record,
            'summary' => [
                'average' => $avg,
                'count' => $count,
            ],
        ], 201);
    }

    public function scan($code, Request $request)
    {
        $exhibit = Exhibit::where('code', $code)->orWhere('id', $code)->first();
        $exhibitId = $exhibit ? $exhibit->id : $code;

        ScanEvent::create([
            'exhibit_id' => $exhibitId,
            'visitor_id' => $request->input('visitorId'),
            'source' => $request->input('source', 'qr_camera'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $title = $request->input('title');
        if (!$title || !trim($title)) {
            return response()->json(['error' => 'Title is required.'], 400);
        }

        $category = trim((string)$request->input('category', ''));
        if ($category && !Category::where('name', $category)->exists()) {
            Category::create(['name' => $category]);
        }

        $id = (string) Str::random(10);
        $code = $request->input('code');
        if (!$code) {
            $count = Exhibit::count() + 1;
            $code = 'EX-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $imagePath = $request->input('imagePath');
        $imagePaths = $request->input('imagePaths') ?: [];
        if (is_string($imagePaths)) {
            $imagePaths = json_decode($imagePaths, true) ?: [$imagePaths];
        }

        // Handle uploaded file
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
            array_unshift($imagePaths, $imagePath);
        }

        $audioPath = $request->input('audioPath');
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $audioPath = '/uploads/' . $filename;
        }

        $videoPath = $request->input('videoPath');
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $videoPath = '/uploads/' . $filename;
        }

        $exhibit = Exhibit::create([
            'id' => $id,
            'code' => $code,
            'title' => trim($title),
            'category' => $category ?: null,
            'origin' => $request->input('origin'),
            'year' => $request->input('year'),
            'location' => $request->input('location'),
            'image_path' => $imagePath ?: ($imagePaths[0] ?? null),
            'image_paths' => array_values(array_unique(array_filter($imagePaths))),
            'description' => $request->input('description'),
            'additional_details' => $request->input('additionalDetails'),
            'description_tl' => $request->input('description_tl'),
            'description_cb' => $request->input('description_cb'),
            'audio_path' => $audioPath,
            'video_path' => $videoPath,
            'floor' => (int) $request->input('floor', 1),
            'map_zone' => $request->input('mapZone'),
            'pin_x' => $request->filled('pinX') ? (float)$request->input('pinX') : null,
            'pin_y' => $request->filled('pinY') ? (float)$request->input('pinY') : null,
            'directions' => $request->input('directions'),
        ]);

        return response()->json($this->enrichExhibit($exhibit), 201);
    }

    public function update($id, Request $request)
    {
        $exhibit = Exhibit::where('id', $id)->orWhere('code', $id)->first();
        if (!$exhibit) {
            return response()->json(['error' => 'Exhibit not found.'], 404);
        }

        $category = trim((string)$request->input('category', ''));
        if ($category && !Category::where('name', $category)->exists()) {
            Category::create(['name' => $category]);
        }

        $imagePath = $request->input('imagePath', $exhibit->image_path);
        $imagePaths = $request->input('imagePaths') ?: $exhibit->image_paths ?: [];
        if (is_string($imagePaths)) {
            $imagePaths = json_decode($imagePaths, true) ?: [$imagePaths];
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imagePath = '/uploads/' . $filename;
            array_unshift($imagePaths, $imagePath);
        }

        $audioPath = $request->input('audioPath', $exhibit->audio_path);
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $audioPath = '/uploads/' . $filename;
        }

        $videoPath = $request->input('videoPath', $exhibit->video_path);
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $videoPath = '/uploads/' . $filename;
        }

        $exhibit->update([
            'title' => $request->filled('title') ? trim($request->input('title')) : $exhibit->title,
            'category' => $category ?: $exhibit->category,
            'origin' => $request->input('origin', $exhibit->origin),
            'year' => $request->input('year', $exhibit->year),
            'location' => $request->input('location', $exhibit->location),
            'image_path' => $imagePath,
            'image_paths' => array_values(array_unique(array_filter($imagePaths))),
            'description' => $request->input('description', $exhibit->description),
            'additional_details' => $request->input('additionalDetails', $exhibit->additional_details),
            'description_tl' => $request->input('description_tl', $exhibit->description_tl),
            'description_cb' => $request->input('description_cb', $exhibit->description_cb),
            'audio_path' => $audioPath,
            'video_path' => $videoPath,
            'floor' => $request->has('floor') ? (int)$request->input('floor') : $exhibit->floor,
            'map_zone' => $request->input('mapZone', $exhibit->map_zone),
            'pin_x' => $request->has('pinX') ? (float)$request->input('pinX') : $exhibit->pin_x,
            'pin_y' => $request->has('pinY') ? (float)$request->input('pinY') : $exhibit->pin_y,
            'directions' => $request->input('directions', $exhibit->directions),
        ]);

        return response()->json(['exhibit' => $this->enrichExhibit($exhibit)]);
    }

    public function destroy($id)
    {
        $exhibit = Exhibit::where('id', $id)->orWhere('code', $id)->first();
        if (!$exhibit) {
            return response()->json(['error' => 'Exhibit not found.'], 404);
        }

        $exhibit->delete();
        return response()->json(['success' => true]);
    }

    public function addCategory(Request $request)
    {
        $name = trim((string)$request->input('name'));
        if (!$name) {
            return response()->json(['error' => 'Category name is required.'], 400);
        }
        if (!Category::where('name', $name)->exists()) {
            Category::create(['name' => $name]);
        }
        return response()->json(['categories' => Category::pluck('name')->values()]);
    }

    public function deleteCategory($name)
    {
        Category::where('name', $name)->delete();
        return response()->json(['categories' => Category::pluck('name')->values()]);
    }
}
