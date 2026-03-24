<?php

namespace App\Http\Controllers;

use App\Services\UserMenuService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenusController extends Controller
{
    public function __construct(
        private readonly UserMenuService $userMenuService
    ) {
    }

    public function getMenu(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error'=>'Unauthenticated'],401);
        }

        return response()->json($this->userMenuService->getForUser($user));
    }
}
