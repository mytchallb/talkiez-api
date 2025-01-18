<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\User;
use App\Models\Friendship;

class FriendshipController extends Controller
{

    public function getFriends(Request $request)
    {
        $user = $request->user();
        
        return response()->json(
            User::select('users.name', 'users.email', 'friendships.status')
                ->selectRaw('friendships.id, friendships.sender_id, friendships.recipient_id')
                ->join('friendships', function($join) use ($user) {
                    $join->on('users.id', '=', 'friendships.recipient_id')
                        ->where('friendships.sender_id', '=', $user->id)
                    ->orOn('users.id', '=', 'friendships.sender_id')
                        ->where('friendships.recipient_id', '=', $user->id);
                })
                ->whereIn('friendships.status', ['accepted', 'pending'])
                ->orderBy('friendships.created_at', 'asc')
                ->get()
        );
    }

    private function checkFriendExists($phone = null, $email = null)
    {
        $query = User::query();
        
        if (!empty($phone)) {
            $query->where('phone', $phone);
        }
        
        if (!empty($email)) {
            $query->orWhere('email', strtolower($email));
        }

        return $query->first();
    }

    public function sendFriendRequest(Request $request)
    {
        // Validate initial request
        $validated = $request->validate([
            'phone' => 'required_without:email|string|nullable',
            'email' => 'required_without:phone|email|nullable',
        ]);

        // First check if the friend exists
        $friend = $this->checkFriendExists(
            $validated['phone'] ?? null,
            $validated['email'] ?? null
        );

        if (!$friend) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user = $request->user();
        
        // Check if trying to add self
        if ($user->id === $friend->id) {
            return response()->json(['message' => 'Cannot send friend request to yourself'], 400);
        }

        // Check for existing friendship
        $existingFriendship = Friendship::where(function($query) use ($user, $friend) {
            $query->where('sender_id', $user->id)
                  ->where('recipient_id', $friend->id);
        })->orWhere(function($query) use ($user, $friend) {
            $query->where('sender_id', $friend->id)
                  ->where('recipient_id', $user->id);
        })->first();

        if ($existingFriendship) {
            return response()->json(['message' => 'Friend request already exists'], 400);
        }

        // Create new friendship request
        Friendship::create([
            'sender_id' => $user->id,
            'recipient_id' => $friend->id,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Friend request sent successfully',
            'friend' => [
                'id' => $friend->id,
                'name' => $friend->name
            ]
        ]);
    }

    public function acceptFriendRequest(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'request_id' => 'required'
        ]);

        $user = $request->user();

        // Log the request
        Log::info('Accepting friend request', ['request_id' => $validated['request_id']]);
        
        // Find the friendship request
        $friendship = Friendship::where('id', $validated['request_id'])
            ->where('recipient_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        // Update friendship status
        $friendship->status = 'accepted';
        $friendship->save();

        return response()->json(['message' => 'Friend request accepted']);
    }

    public function rejectFriendRequest(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'request_id' => 'required'
        ]);

        $user = $request->user();
        
        // Find the friendship request
        $friendship = Friendship::where('id', $validated['request_id'])
            ->where('recipient_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        // Delete the friendship request instead of updating status
        $friendship->delete();

        return response()->json(['message' => 'Friend request rejected']);
    }

    public function cancelFriendRequest(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'request_id' => 'required'
        ]);

        $user = $request->user();

        // Find the friendship request
        $friendship = Friendship::where('id', $validated['request_id'])
            ->where('sender_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        // Delete the friendship request
        $friendship->delete();

        return response()->json(['message' => 'Friend request canceled']);
    }

}
