<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /* ================= LIST ================= */
    public function index()
    {
        $categories = Category::latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Category List Retrieved Successfully',
            'data' => $categories
        ]);
    }

    /* ================= STORE ================= */
    public function store (Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
            'status' => 'required|in:active,inactive'
        ]);

        $slug = Str::slug($request->name);

        $category = Category::create([
            'name'=> $request->name,
            'slug'=> $slug,
            'status'=> $request->status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category Created Successfully',
            'data' => $category
        ], 201);
  
    }

    /* ================= SHOW ================= */
    public function show($id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Category Retrieved Successfully',
            'data' => $category
        ]);
    }

    /* ================= UPDATE ================= */
    public function Update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $request->validate([
            'name' => 'required|unique:categories,name,'.$category->id,
            'status' => 'required|in:active,inactive'
        ]);
        $slug = Str::slug($request->name);
        if(Category::where('slug', $slug)
            ->where('id', '!=', $category->id)
            ->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'The name has already been taken.'
            ], 422);
        }
        $category->update([ 
            'name'=> $request->name,
            'slug'=> $slug,
            'status'=> $request->status
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Category Updated Successfully',
            'data' => $category
        ]);
    }

     /* ================= DELETE ================= */
    public function destroy($id)
    {
        $category = Category::withCount('posts')->findOrFail($id);

        if ($category->posts_count > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete category. Posts exist.'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category Deleted Successfully'
        ]);
    }
}
