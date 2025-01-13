<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\User;

class UserController extends Controller
{
    public function getUser(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }

}
