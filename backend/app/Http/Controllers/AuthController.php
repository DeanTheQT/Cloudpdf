<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|string|min:6',
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
        ]);

        // Store user ID in session
        $request->session()->put('user_id', $user->id);

        return response()->json(['user'=>$user]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'=>'required|email',
            'password'=>'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json(['message'=>'Invalid credentials'], 401);
        }

        // Store user ID in session
        $request->session()->put('user_id', $user->id);

        return response()->json(['user'=>$user]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user_id');
        return response()->json(['message'=>'Logged out']);
    }

    public function user(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $user = $userId ? User::find($userId) : null;
        return response()->json(['user'=>$user]);
    }
}
