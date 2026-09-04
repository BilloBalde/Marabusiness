<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProfileUpdated; // You'll need to create this Mailable

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'roles' => $user->getRoleNames(),
                ],
                'message' => 'User profile retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|max:20',
                'current_password' => 'required_with:new_password|string',
                'new_password' => 'required_with:current_password|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $changes = [];
            $oldData = [];

            // Track changes for email notification
            if ($request->has('name') && $request->name !== $user->name) {
                $changes['name'] = $request->name;
                $oldData['name'] = $user->name;
            }
            
            if ($request->has('email') && $request->email !== $user->email) {
                $changes['email'] = $request->email;
                $oldData['email'] = $user->email;
            }
            
            if ($request->has('phone') && $request->phone !== $user->phone) {
                $changes['phone'] = $request->phone;
                $oldData['phone'] = $user->phone;
            }

            $passwordChanged = false;
            if ($request->has('current_password') && $request->has('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect'
                    ], 422);
                }
                $user->password = Hash::make($request->new_password);
                $passwordChanged = true;
            }

            // Update basic info
            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            $user->save();

            // Send email notification if there were changes
            if (!empty($changes) || $passwordChanged) {
                try {
                    Mail::to($user->email)->send(new ProfileUpdated($user, $changes, $oldData, $passwordChanged));
                    
                    \Log::info('Profile update email sent', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'changes' => $changes,
                        'password_changed' => $passwordChanged
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to send profile update email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't fail the request if email fails
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'roles' => $user->getRoleNames(),
                ],
                'message' => 'Profile updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}