<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    // Create User API
    public function createUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'name' => 'required|string|min:3|max:50',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
        ]);

        // Send Emails
        Mail::to($user->email)->send(new \App\Mail\WelcomeUserMail($user));
        Mail::to('admin@example.com')->send(new \App\Mail\NewUserNotificationMail($user));

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at,
        ], 201);
    }

    // Get Users API
    public function getUsers(Request $request)
    {
        $search = $request->query('search', '');
        $page = $request->query('page', 1);
        $sortBy = $request->query('sortBy', 'created_at');

        $users = User::where(function ($query) use ($search) {
            if ($search) {
                $query->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
            }
        })
        ->orderBy($sortBy)
        ->paginate(10, ['*'], 'page', $page);

        $users->getCollection()->transform(function ($user) {
            $user->orders_count = $user->orders()->count();
            return $user;
        });

        return response()->json([
            'page' => $users->currentPage(),
            'users' => $users->items(),
        ]);
    }
}
