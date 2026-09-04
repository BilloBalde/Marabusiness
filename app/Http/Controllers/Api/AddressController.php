<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Get user's addresses
     */
    public function index()
    {
        try {
            $addresses = Address::where('user_id', Auth::id())
                ->whereNull('order_id')
                ->orderByDesc('is_default')
                ->latest()
                ->get()
                ->map(fn($address) => $this->formatAddress($address));

            return response()->json([
                'success' => true,
                'data' => $addresses,
                'message' => 'Addresses retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new address
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'zone' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = Auth::id();
            $data['country'] = $data['country'] ?? 'Guinea';
            $data['is_default'] = $data['is_default'] ?? false;

            // If this is set as default, unset other defaults
            if ($data['is_default']) {
                Address::where('user_id', Auth::id())->whereNull('order_id')->update(['is_default' => false]);
            }

            $address = Address::create($data);

            return response()->json([
                'success' => true,
                'data' => $this->formatAddress($address),
                'message' => 'Address created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update address
     */
    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', Auth::id())->whereNull('order_id')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'street_address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'state' => 'sometimes|required|string|max:255',
            'zip_code' => 'sometimes|nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'zone' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Handle default status
            if (isset($data['is_default']) && $data['is_default']) {
                Address::where('user_id', Auth::id())
                    ->whereNull('order_id')
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);

            return response()->json([
                'success' => true,
                'data' => $this->formatAddress($address->fresh()),
                'message' => 'Address updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete address
     */
    public function destroy($id)
    {
        try {
            $address = Address::where('user_id', Auth::id())->whereNull('order_id')->findOrFail($id);
            
            // Check if it's the default address
            $wasDefault = $address->is_default;
            
            $address->delete();

            // If deleted address was default, set another as default
            if ($wasDefault) {
                $newDefault = Address::where('user_id', Auth::id())->whereNull('order_id')->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set default address
     */
    public function setDefault($id)
    {
        try {
            $address = Address::where('user_id', Auth::id())->whereNull('order_id')->findOrFail($id);

            // Unset all other defaults
            Address::where('user_id', Auth::id())
                ->whereNull('order_id')
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);

            // Set this as default
            $address->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'data' => $this->formatAddress($address->fresh()),
                'message' => 'Default address updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get address by ID
     */
    public function show($id)
    {
        try {
            $address = Address::where('user_id', Auth::id())->whereNull('order_id')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatAddress($address),
                'message' => 'Address retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get default address
     */
    public function getDefault()
    {
        try {
            $address = Address::where('user_id', Auth::id())
                ->whereNull('order_id')
                ->where('is_default', true)
                ->first();

            if (!$address) {
                $address = Address::where('user_id', Auth::id())->whereNull('order_id')->first();
            }

            return response()->json([
                'success' => true,
                'data' => $address ? $this->formatAddress($address) : null,
                'message' => $address ? 'Default address retrieved' : 'No address found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve default address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format address for API response
     */
    private function formatAddress($address)
    {
        return [
            'id' => $address->id,
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'full_name' => $address->first_name . ' ' . $address->last_name,
            'phone' => $address->phone,
            'street_address' => $address->street_address,
            'city' => $address->city,
            'state' => $address->state,
            'zip_code' => $address->zip_code,
            'country' => $address->country ?? 'Guinea',
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'zone' => $address->zone,
            'is_default' => (bool) $address->is_default,
            'formatted_address' => $this->formatAddressString($address),
            'created_at' => $address->created_at->toDateTimeString(),
            'updated_at' => $address->updated_at->toDateTimeString()
        ];
    }

    /**
     * Format address as string
     */
    private function formatAddressString($address)
    {
        $parts = [
            $address->street_address,
            $address->city,
            $address->state,
            $address->zip_code,
            $address->country ?? 'Guinea'
        ];

        return implode(', ', array_filter($parts));
    }
}