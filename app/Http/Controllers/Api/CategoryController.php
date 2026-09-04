<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all active categories
     */
    public function index(Request $request)
    {
        try {
            $categories = Category::where('is_active', 1)
                ->select(['id', 'name', 'slug', 'image', 'description', 'parent_id'])
                ->with(['children' => function($query) {
                    $query->where('is_active', 1)->select(['id', 'name', 'slug', 'image', 'parent_id']);
                }])
                ->whereNull('parent_id') // Get only parent categories
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single category with its products
     */
    public function show($id)
    {
        try {
            $category = Category::where('is_active', 1)
                ->with(['products' => function($query) {
                    $query->where('is_active', 1)
                        ->with(['vendorProducts' => function($q) {
                            $q->with('vendor.currency')
                              ->whereHas('vendor', fn($v) => $v->where('is_active', 1));
                        }]);
                }])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get category tree/hierarchy
     */
    public function tree()
    {
        try {
            $categories = Category::where('is_active', 1)
                ->whereNull('parent_id')
                ->with(['children' => function($query) {
                    $query->where('is_active', 1)
                          ->with(['children' => function($q) {
                              $q->where('is_active', 1);
                          }]);
                }])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Category tree retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category tree',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured categories
     */
    public function featured()
    {
        try {
            $categories = Category::where('is_active', 1)
                ->where('is_featured', 1)
                ->select(['id', 'name', 'slug', 'image'])
                ->withCount('products')
                ->orderBy('name')
                ->limit(8)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Featured categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}