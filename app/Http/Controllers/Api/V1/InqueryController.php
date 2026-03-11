<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquery;

class InqueryController extends Controller
{
    public function frontInquery(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'nullable|integer',
            'inquiry_type_id' => 'nullable|integer',
            'property_type_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'preferred_location' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'message' => 'nullable|string',
        ]);

        $inquery = Inquery::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry submitted successfully',
            'data' => $inquery
        ], 201);
    }
}
