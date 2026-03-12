<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestforPosts;
use Illuminate\Support\Facades\Validator;

class RequestForPostsController extends Controller
{
    public function frontrequestPost(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'name' => 'required|string|max:255',

            'email' => 'nullable|email|required_without:phone',
            'phone' => 'nullable|string|required_without:email',

            'message' => 'nullable|string',
            'location' => 'required|string',

            'request_type' => 'required|in:buy,sell,rent',

            'budget' => 'required_if:request_type,buy',

            'images' => 'required_if:request_type,sell,rent',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'description' => 'nullable|string'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $imagePaths = [];

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('request_posts', 'public');

                $imagePaths[] = asset('storage/' . $path);
            }
        }

        $post = RequestforPosts::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'request_type' => $request->request_type,
            'location' => $request->location,
            'budget' => $request->budget,
            'images' => $imagePaths ? json_encode($imagePaths) : null,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Request submitted successfully',
            'data' => $post
        ]);
    }
}
