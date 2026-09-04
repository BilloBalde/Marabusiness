<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Get all active currencies
     */
    public function index()
    {
        try {
            $currencies = Currency::where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'symbol']);

            return response()->json([
                'success' => true,
                'data' => $currencies,
                'message' => 'Currencies retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve currencies',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}