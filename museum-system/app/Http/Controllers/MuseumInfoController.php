<?php

namespace App\Http\Controllers;

use App\Models\MuseumInfo;
use Illuminate\Http\Request;

class MuseumInfoController extends Controller
{
    public function show()
    {
        $info = MuseumInfo::first();
        return response()->json($info ? $info->data : [
            'name' => 'Museo Sang Bata sa Negros',
            'address' => 'Barangay Old Sagay, Sagay City, Negros Occidental',
            'phone' => '+63 917 798 7420',
            'email' => 'museosangbata@gmail.com',
            'operatingHours' => 'Tuesday – Sunday: 8:00 AM – 5:00 PM',
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->all();
        $info = MuseumInfo::first();
        if ($info) {
            $info->data = array_merge($info->data ?: [], $payload);
            $info->save();
        } else {
            $info = MuseumInfo::create(['data' => $payload]);
        }

        return response()->json($info->data);
    }
}
