<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function forVisitor($visitorId)
    {
        $favs = Favorite::where('visitor_id', $visitorId)->with('exhibit')->get();
        return response()->json(['favorites' => $favs]);
    }

    public function store(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $exhibitId = $request->input('exhibitId');

        if (!$visitorId || !$exhibitId) {
            return response()->json(['error' => 'visitorId and exhibitId are required.'], 400);
        }

        $fav = Favorite::firstOrCreate([
            'visitor_id' => $visitorId,
            'exhibit_id' => $exhibitId,
        ]);

        return response()->json(['success' => true, 'favorite' => $fav], 201);
    }

    public function destroy(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $exhibitId = $request->input('exhibitId');

        if (!$visitorId || !$exhibitId) {
            return response()->json(['error' => 'visitorId and exhibitId are required.'], 400);
        }

        Favorite::where('visitor_id', $visitorId)->where('exhibit_id', $exhibitId)->delete();
        return response()->json(['success' => true]);
    }
}
