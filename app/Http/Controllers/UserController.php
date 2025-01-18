<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

use App\Models\User;

class UserController extends Controller
{


    public function getUser(Request $request)
    {
        $user = $request->user();
        // also return phone_number which doesn't have the +prefix
        // see if phone_prefix is set, if so, strip the prefix from "phone"
        if ($user->phone_prefix) {
            $phone_number = str_replace($user->phone_prefix, '', $user->phone_combined);
        } else {
            $phone_number = $user->phone_combined;
        }
        if (strpos($phone_number, '+') === 0) {
            $phone_number = substr($phone_number, 1);
        }

        $user->phone_number = $phone_number;
        return response()->json($user);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();
        
        // Validate input fields
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_prefix' => 'required|string|max:5',
            'phone_combined' => 'required|phone:AUTO,mobile',
            'language' => 'nullable|string',
            'password' => 'sometimes|nullable',
        ], [
            'phone.phone' => 'Please enter a valid mobile phone number.',
            'phone.required' => 'The phone number field is required.'
        ]);

        // Format phone number if provided
        if (!empty($validated['phone_combined'])) {
            $validated['phone_combined'] = phone($validated['phone_combined'])->formatE164();
        }

        // Remove password from validated data if it's empty
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function deleteUser(Request $request)
    {
        $user = $request->user();
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

}
