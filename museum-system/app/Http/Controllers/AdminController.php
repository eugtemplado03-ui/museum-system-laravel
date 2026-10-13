<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\ScanEvent;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function me(Request $request)
    {
        return response()->json(['user' => $request->attributes->get('jwt_user')]);
    }

    public function analytics()
    {
        $allTime = ScanEvent::count();
        $last7Days = ScanEvent::where('created_at', '>', Carbon::now()->subDays(7))->count();
        $last24Hours = ScanEvent::where('created_at', '>', Carbon::now()->subHours(24))->count();

        $totals = [
            'allTime' => $allTime,
            'last7Days' => $last7Days,
            'last24Hours' => $last24Hours,
            'exhibits' => Exhibit::count(),
            'scans' => $allTime,
            'ratings' => Rating::count(),
            'visitors' => Visitor::count(),
            'favorites' => Favorite::count(),
        ];

        // Summary by Exhibit
        $exhibits = Exhibit::all()->keyBy('id');
        $scansByExhibit = ScanEvent::select('exhibit_id', DB::raw('count(*) as count'))
            ->groupBy('exhibit_id')
            ->pluck('count', 'exhibit_id');

        $byExhibit = [];
        foreach ($exhibits as $id => $ex) {
            $byExhibit[] = [
                'exhibitId' => $id,
                'code' => $ex->code,
                'title' => $ex->title,
                'scans' => $scansByExhibit[$id] ?? 0,
                'ratings' => Rating::where('exhibit_id', $id)->count(),
                'averageRating' => round(Rating::where('exhibit_id', $id)->avg('rating') ?: 0, 1),
            ];
        }

        // Recent 30 events
        $recentData = ScanEvent::orderBy('created_at', 'desc')->take(30)->get();
        $recent = $recentData->map(function ($ev) use ($exhibits) {
            $ex = $exhibits->get($ev->exhibit_id);
            return [
                'id' => $ev->id,
                'exhibitId' => $ev->exhibit_id,
                'code' => $ex ? $ex->code : null,
                'title' => $ex ? $ex->title : '(deleted exhibit)',
                'source' => $ev->source,
                'visitorId' => $ev->visitor_id,
                'timestamp' => $ev->timestamp,
            ];
        });

        // Daily stats for last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $dailyRaw = ScanEvent::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        $daily = $dailyRaw->map(fn($row) => ['date' => $row->date, 'count' => (int)$row->count]);

        return response()->json([
            'totals' => $totals,
            'byExhibit' => $byExhibit,
            'recent' => $recent,
            'daily' => $daily,
        ]);
    }

    public function dailyAnalytics(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $since = Carbon::now()->subDays($days);

        $dailyRaw = ScanEvent::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        $daily = $dailyRaw->map(fn($row) => ['date' => $row->date, 'count' => (int)$row->count]);

        return response()->json(['daily' => $daily]);
    }

    public function ratings()
    {
        $exhibits = Exhibit::all()->keyBy('id');
        $ratings = Rating::orderBy('created_at', 'desc')->get()->map(function ($r) use ($exhibits) {
            $ex = $exhibits->get($r->exhibit_id);
            return [
                'id' => $r->id,
                'visitorId' => $r->visitor_id,
                'visitorName' => $r->visitor_name,
                'exhibitId' => $r->exhibit_id,
                'code' => $ex ? $ex->code : null,
                'title' => $ex ? $ex->title : '(deleted exhibit)',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'createdAt' => $r->created_at ? $r->created_at->toISOString() : null,
            ];
        });

        return response()->json(['ratings' => $ratings]);
    }

    public function deleteRating($id)
    {
        $rating = Rating::find($id);
        if (!$rating) {
            return response()->json(['error' => 'Rating not found.'], 404);
        }

        $rating->delete();
        return response()->json(['success' => true]);
    }
}
