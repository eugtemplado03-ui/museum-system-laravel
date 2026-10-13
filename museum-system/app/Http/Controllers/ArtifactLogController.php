<?php

namespace App\Http\Controllers;

use App\Models\ArtifactLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArtifactLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ArtifactLog::with(['exhibit', 'visitor']);

        if ($vId = $request->input('visitorId')) {
            $query->where('visitor_id', $vId);
        }
        if ($eId = $request->input('exhibitId')) {
            $query->where('exhibit_id', $eId);
        }

        $limit = (int) $request->input('limit', 50);
        $logs = $query->orderBy('created_at', 'desc')->take($limit)->get();

        return response()->json(['logs' => $logs]);
    }

    public function store(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $exhibitId = $request->input('exhibitId');
        $action = $request->input('action', 'viewed');

        if (!$visitorId || !$exhibitId) {
            return response()->json(['error' => 'visitorId and exhibitId are required.'], 400);
        }

        $log = ArtifactLog::create([
            'id' => (string) Str::random(10),
            'visitor_id' => $visitorId,
            'exhibit_id' => $exhibitId,
            'action' => $action,
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['success' => true, 'log' => $log], 201);
    }
}
