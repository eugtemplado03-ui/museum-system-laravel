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
        $now = Carbon::now('Asia/Manila');
        $last7Days = ScanEvent::where('created_at', '>', $now->copy()->subDays(7)->utc())->count();
        $last24Hours = ScanEvent::where('created_at', '>', $now->copy()->subHours(24)->utc())->count();
        $scanCount = ScanEvent::where('source', 'scan')->count();
        $viewCount = ScanEvent::where(function ($query) {
            $query->where('source', '!=', 'scan')->orWhereNull('source');
        })->count();

        $totals = [
            'allTime' => $allTime,
            'last7Days' => $last7Days,
            'last24Hours' => $last24Hours,
            'exhibits' => Exhibit::count(),
            'scans' => $scanCount,
            'views' => $viewCount,
            'ratings' => Rating::count(),
            'visitors' => Visitor::count(),
            'favorites' => Favorite::count(),
        ];

        $exhibits = Exhibit::all()->keyBy('id');
        $eventCounts = ScanEvent::select('exhibit_id', 'source', DB::raw('count(*) as count'))
            ->groupBy('exhibit_id', 'source')
            ->get()
            ->groupBy('exhibit_id');
        $ratingStats = Rating::select(
            'exhibit_id',
            DB::raw('count(*) as count'),
            DB::raw('avg(rating) as average')
        )->groupBy('exhibit_id')->get()->keyBy('exhibit_id');

        $byExhibit = [];
        foreach ($exhibits as $id => $ex) {
            $scans = 0;
            $views = 0;
            foreach ($eventCounts->get($id, collect()) as $eventCount) {
                if ($eventCount->source === 'scan') {
                    $scans += (int) $eventCount->count;
                } else {
                    $views += (int) $eventCount->count;
                }
            }
            $ratings = $ratingStats->get($id);
            $byExhibit[] = [
                'exhibitId' => $id,
                'code' => $ex->code,
                'title' => $ex->title,
                'scans' => $scans,
                'views' => $views,
                'total' => $scans + $views,
                'ratings' => (int) ($ratings->count ?? 0),
                'averageRating' => round((float) ($ratings->average ?? 0), 1),
            ];
        }
        usort($byExhibit, fn ($a, $b) => $b['total'] <=> $a['total']);

        $recentData = ScanEvent::orderBy('created_at', 'desc')->take(30)->get();
        $recent = $recentData->map(function ($ev) use ($exhibits) {
            $ex = $exhibits->get($ev->exhibit_id);
            $at = $ev->created_at?->toIso8601String();

            return [
                'id' => $ev->id,
                'exhibitId' => $ev->exhibit_id,
                'code' => $ex ? $ex->code : null,
                'title' => $ex ? $ex->title : '(deleted exhibit)',
                'source' => $ev->source,
                'visitorId' => $ev->visitor_id,
                'at' => $at,
                'timestamp' => $at,
            ];
        });

        return response()->json([
            'totals' => $totals,
            'byExhibit' => $byExhibit,
            'recent' => $recent,
            'daily' => $this->dailyBreakdown(30),
        ]);
    }

    public function dailyAnalytics(Request $request)
    {
        $days = min(366, max(1, (int) $request->query('days', 30)));

        return response()->json(['daily' => $this->dailyBreakdown($days)]);
    }

    private function dailyBreakdown(int $days): array
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        $firstDay = $today->copy()->subDays($days - 1);
        $counts = [];

        for ($day = $firstDay->copy(); $day->lte($today); $day->addDay()) {
            $counts[$day->toDateString()] = [
                'date' => $day->toDateString(),
                'label' => $day->format('M j'),
                'scans' => 0,
                'views' => 0,
                'total' => 0,
            ];
        }

        $events = ScanEvent::where('created_at', '>=', $firstDay->copy()->utc())
            ->get(['source', 'created_at']);
        foreach ($events as $event) {
            $eventDate = Carbon::parse($event->created_at, 'UTC')
                ->setTimezone('Asia/Manila')
                ->toDateString();
            if (! isset($counts[$eventDate])) {
                continue;
            }
            $metric = $event->source === 'scan' ? 'scans' : 'views';
            $counts[$eventDate][$metric]++;
            $counts[$eventDate]['total']++;
        }

        return array_values($counts);
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
        if (! $rating) {
            return response()->json(['error' => 'Rating not found.'], 404);
        }

        $rating->delete();

        return response()->json(['success' => true]);
    }
}
