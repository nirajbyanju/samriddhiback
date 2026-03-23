<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;

class MenusController extends Controller
{
  public function getMenu(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error'=>'Unauthenticated'],401);
        }

        $menus = Menu::orderBy('order')->get();
        $menuTree = $this->buildTree($menus, $user);

        return response()->json($menuTree);
    }

    private function buildTree($menus, $user, $parentId = null)
    {
        $branch = [];

        foreach ($menus->where('parent_id',$parentId) as $menu) {

            $children = $this->buildTree($menus, $user, $menu->id);

            $allowed = $this->getAllowedActions($menu,$user);

            if(count($allowed) > 0 || count($children) > 0){
                $branch[] = [
                    'id'=>$menu->id,
                    'name'=>$menu->name,
                    'icon'=>$menu->icon,
                    'route'=>$menu->route,
                    'url'=>$menu->url,
                    'permissions'=>$allowed,
                    'children'=>$children
                ];
            }
        }

        return $branch;
    }

    private function getAllowedActions($menu, $user)
    {
        $actions = ['view','create','edit','delete','approve','export','upload'];
        $allowed = [];

        if($menu->permission_name){
            foreach($actions as $action){
                $permName = "{$action}_{$menu->permission_name}"; // e.g., view_employees
                if($user->can($permName)){
                    $allowed[] = $action;
                }
            }
        }

        return $allowed;
    }
}
