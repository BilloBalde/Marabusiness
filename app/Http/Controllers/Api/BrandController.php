<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Get all active brands
     */
    public function index(Request $request)
    {
        try {
            $brands = Brand::where('is_active', 1)
                ->with('translations')
                ->select(['id', 'name', 'slug', 'image', 'is_active', 'created_by'])
                ->orderBy('name')
                ->get();

            // Convert is_active to boolean explicitly
            $brands = $brands->map(function($brand) {
                $brand->is_active = (bool) $brand->is_active;
                return $brand;
            });

            return response()->json([
                'success' => true,
                'data' => $brands,
                'message' => 'Brands retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve brands',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single brand
     */
    public function show($id)
    {
        try {
            $brand = Brand::where('is_active', 1)
                ->with('translations')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $brand,
                'message' => 'Brand retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get featured brands
     */
    public function featured()
    {
        try {
            $brands = Brand::where('is_active', 1)
                ->with('translations')
                ->select(['id', 'name', 'slug', 'image'])
                ->inRandomOrder()
                ->limit(8)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $brands,
                'message' => 'Featured brands retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured brands',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}