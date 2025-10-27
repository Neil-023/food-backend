<?php

namespace App\Http\Controllers;

use App\Models\Category;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all(['category_id','category_name','icon_name']);
        return response()->json($categories);
    }

    public function store(Request $r)
    {
        $c = Category::create([
            'category_name' => $r->category_name,
            'icon_name' => $r->icon_name,
        ]);
        return response()->json($c, 201);
    }
}
