<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function show(): JsonResponse
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Debug logging
        \Log::info('Profile update request received', [
            'user_id' => $user->id,
            'request_data' => $request->all(),
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_file' => $request->hasFile('profile_picture'),
            'name_field' => $request->input('name'),
            'name_field_exists' => $request->has('name'),
            'all_inputs' => $request->all()
        ]);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Update user data
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        $user->company = $request->input('company');
        $user->bio = $request->input('bio');

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $user->profile_picture = $path;
            
            \Log::info('Profile picture uploaded', ['path' => $path]);
        }

        $user->save();

        \Log::info('Profile updated successfully', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Profile updated successfully'
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string'
        ]);

        $user = Auth::user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    // Admin methods
    public function index(): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $users = User::all();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function getUsersForAssignment(): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $users = User::where('status', 'active')
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'user'])],
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Prevent admin from deleting themselves
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function toggleStatus(User $user): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Prevent admin from deactivating themselves
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate your own account'
            ], 400);
        }

        // Toggle status
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User status updated successfully'
        ]);
    }
}
