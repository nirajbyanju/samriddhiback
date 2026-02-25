<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api\V1;

use App\Models\Menu;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class MenuController extends Controller
{
  
    /**
     * Get menus based on user permissions
     */
    public function getAccessibleMenus(Request $request)
    {
        $user = $request->user();
        
        $menus = Menu::with(['children' => function($query) use ($user) {
            $query->active()->orderBy('order');
        }])
        ->whereNull('parent_id')
        ->active()
        ->orderBy('order')
        ->get()
        ->filter(function($menu) use ($user) {
            // Filter parent menus
            if (!$menu->isAccessibleBy($user)) {
                return false;
            }
            
            // Filter children menus
            $menu->children = $menu->children->filter(function($child) use ($user) {
                return $child->isAccessibleBy($user);
            })->values();
            
            return true;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $menus
        ]);
    }

    /**
     * Get all menus (for admin)
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view menus')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $menus = Menu::with(['parent', 'creator'])
            ->when($request->has('parent_id'), function($query) use ($request) {
                return $query->where('parent_id', $request->parent_id);
            })
            ->orderBy('order')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $menus
        ]);
    }

    /**
     * Store a new menu
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create menus')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'integer',
            'is_status' => 'boolean',
            'permission_name' => 'nullable|string|max:255',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['created_by'] = $request->user()->id;

        $menu = Menu::create($data);

        // Create corresponding permission if needed
        if (!$menu->is_public && empty($menu->permission_name)) {
            $permissionName = "view menu-{$menu->id}";
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            $menu->update(['permission_name' => $permissionName]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => $menu->load('parent')
        ], 201);
    }

    /**
     * Update menu
     */
    public function update(Request $request, Menu $menu)
    {
        if (!$request->user()->can('edit menus')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'integer',
            'is_status' => 'boolean',
            'permission_name' => 'nullable|string|max:255',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $menu->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => $menu->fresh(['parent'])
        ]);
    }

    /**
     * Delete menu
     */
    public function destroy(Request $request, Menu $menu)
    {
        if (!$request->user()->can('delete menus')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if has children
        if ($menu->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete menu with submenus'
            ], 422);
        }

        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully'
        ]);
    }

    /**
     * Reorder menus
     */
    public function reorder(Request $request)
    {
        if (!$request->user()->can('edit menus')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->menus as $menuData) {
            Menu::where('id', $menuData['id'])->update(['order' => $menuData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menus reordered successfully'
        ]);
    }
}