<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VisitorController extends Controller
{
    public function checkin(Request $request)
    {
        $visitorName = trim((string)$request->input('visitorName', ''));
        $address = trim((string)$request->input('address', ''));
        $sex = $request->input('sex');

        if (!$visitorName) {
            return response()->json(['error' => 'Visitor name is required.'], 400);
        }
        if (!$address) {
            return response()->json(['error' => 'Address is required.'], 400);
        }
        if (!$sex) {
            return response()->json(['error' => 'Sex is required.'], 400);
        }

        $age = $request->filled('age') ? (int)$request->input('age') : null;
        if ($age !== null && ($age < 0 || $age > 120)) {
            return response()->json(['error' => 'Age must be between 0 and 120.'], 400);
        }

        $id = (string) Str::random(10);
        $passCode = 'VP-' . strtoupper(Str::random(6));

        $visitor = Visitor::create([
            'id' => $id,
            'pass_code' => $passCode,
            'visitor_name' => mb_substr($visitorName, 0, 150),
            'address' => mb_substr($address, 0, 250),
            'sex' => $sex,
            'age' => $age,
            'contact_number' => mb_substr(trim((string)$request->input('contactNumber', '')), 0, 50),
            'email' => mb_substr(trim((string)$request->input('email', '')), 0, 150),
            'group_name' => mb_substr(trim((string)$request->input('groupName', '')), 0, 150),
            'group_type' => $request->input('groupType', 'Walk-in / Individual'),
            'pax' => (int)$request->input('pax', 1),
            'purpose' => $request->input('purpose', 'General Visit'),
            'tour_guide' => mb_substr(trim((string)$request->input('tourGuide', '')), 0, 200),
            'notes' => mb_substr(trim((string)$request->input('notes', '')), 0, 500),
            'check_in_time' => now(),
            'is_checked_in' => true,
        ]);

        // Generate QR code data URL (SVG format, universally supported across all browsers)
        $svg = (string) QrCode::size(250)->margin(2)->generate($passCode);
        $qrCodeDataUrl = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return response()->json([
            'success' => true,
            'visitor' => $visitor,
            'qrCode' => $qrCodeDataUrl,
        ], 201);
    }

    public function verifyPass(Request $request, $code = null)
    {
        $passCode = $code ?: $request->input('passCode') ?: $request->input('code');
        if (!$passCode) {
            return response()->json(['error' => 'Pass code is required.'], 400);
        }

        $visitor = Visitor::where('pass_code', $passCode)
            ->orWhere('id', $passCode)
            ->first();

        if (!$visitor) {
            return response()->json(['valid' => false, 'error' => 'Invalid or unrecognized visitor pass.'], 404);
        }

        return response()->json([
            'valid' => true,
            'visitor' => $visitor,
        ]);
    }

    public function checkout(Request $request)
    {
        $passCode = $request->input('passCode') ?: $request->input('id');
        $visitor = Visitor::where('pass_code', $passCode)
            ->orWhere('id', $passCode)
            ->first();

        if (!$visitor) {
            return response()->json(['error' => 'Visitor not found.'], 404);
        }

        $visitor->is_checked_in = false;
        $visitor->check_out_time = now();
        $visitor->save();

        return response()->json([
            'success' => true,
            'visitor' => $visitor,
        ]);
    }

    public function index(Request $request)
    {
        $query = Visitor::query();

        if ($search = $request->input('q')) {
            $query->where(function($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                  ->orWhere('pass_code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('group_name', 'like', "%{$search}%");
            });
        }

        if ($from = $request->input('from')) {
            $query->whereDate('check_in_time', '>=', Carbon::parse($from));
        }
        if ($to = $request->input('to')) {
            $query->whereDate('check_in_time', '<=', Carbon::parse($to));
        }

        $limit = (int) $request->input('limit', 20);
        $page = (int) $request->input('page', 1);

        $total = $query->count();
        $visitors = $query->orderBy('check_in_time', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $stats = [
            'totalVisitors' => Visitor::count(),
            'checkedInToday' => Visitor::whereDate('check_in_time', Carbon::today())->count(),
            'currentlyInside' => Visitor::where('is_checked_in', true)->count(),
        ];

        return response()->json([
            'visitors' => $visitors,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / max($limit, 1)),
            'stats' => $stats,
        ]);
    }

    public function stats()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        return response()->json([
            'total' => Visitor::count(),
            'today' => Visitor::whereDate('check_in_time', $today)->count(),
            'thisMonth' => Visitor::where('check_in_time', '>=', $startOfMonth)->count(),
            'activeNow' => Visitor::where('is_checked_in', true)->count(),
            'byGender' => Visitor::selectRaw('sex, count(*) as count')->groupBy('sex')->pluck('count', 'sex'),
        ]);
    }

    public function show($id)
    {
        $visitor = Visitor::with('artifactLogs')->where('id', $id)->orWhere('pass_code', $id)->first();
        if (!$visitor) {
            return response()->json(['error' => 'Visitor not found.'], 404);
        }

        return response()->json(['visitor' => $visitor]);
    }
}
