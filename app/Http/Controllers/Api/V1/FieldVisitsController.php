<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FieldVisits;

class FieldVisitsController extends Controller
{
    public function frontTour(Request $request)
    {
        try {

            $request->validate([
                'property_id' => 'required|integer',
                'date' => 'required|date',
                'time' => 'required',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email',
                'message' => 'nullable|string'
            ]);

            $tour = FieldVisits::create([
                'property_id' => $request->property_id,
                'date' => $request->date,
                'time' => $request->time,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'message' => $request->message,
                'accept_term' => $request->accept_term
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tour booked successfully',
                'data' => $tour
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error booking tour',
                'error' => $e->getMessage(),
            ], 500);

        }
    }
}