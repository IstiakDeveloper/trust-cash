<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the shop users.
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 1 : 0);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::all();

        // Get max users limit from tenant plan
        $maxUsers = 9999;
        if (function_exists('tenant') && $tenant = tenant()) {
            $maxUsers = $tenant->plan?->max_users ?? 5;
        }

        $currentCount = User::count();

        return Inertia::render('Admin/Users', [
            'users'        => $users,
            'roles'        => $roles,
            'maxUsers'     => $maxUsers,
            'currentCount' => $currentCount,
            'filters'      => $request->only(['search', 'role_id', 'status']),
        ]);
    }

    /**
     * Store a newly created shop user (Manager / Seller / Staff).
     */
    public function store(Request $request)
    {
        // Enforce Plan User Limit
        if (function_exists('tenant') && $tenant = tenant()) {
            $maxUsers = $tenant->plan?->max_users ?? 5;
            $currentUsers = User::count();
            if ($currentUsers >= $maxUsers) {
                return redirect()->back()->with('error', "আপনার প্যাকেজের লিমিট পূর্ণ হয়েছে (সর্বোচ্চ {$maxUsers} জন ইউজার)। নতুন স্টাফ যোগ করতে দয়া করে প্যাকেজ আপগ্রেড করুন।");
            }
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'nullable|string|max:50|unique:users,username',
            'email'    => 'required|email|max:255|unique:users,email',
            'phone'    => 'nullable|string|max:50',
            'password' => 'required|string|min:4',
            'role_id'  => 'required|exists:roles,id',
            'status'   => 'nullable|boolean',
        ]);

        $username = !empty($validated['username']) 
            ? Str::slug($validated['username']) 
            : explode('@', $validated['email'])[0];

        User::create([
            'name'     => $validated['name'],
            'username' => $username,
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role_id'  => $validated['role_id'],
            'status'   => $validated['status'] ?? true,
        ]);

        return redirect()->back()->with('success', 'নতুন ইউজার সফলভাবে তৈরি করা হয়েছে!');
    }

    /**
     * Update the specified shop user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'    => 'nullable|string|max:50',
            'password' => 'nullable|string|min:4',
            'role_id'  => 'required|exists:roles,id',
            'status'   => 'nullable|boolean',
        ]);

        $updateData = [
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'phone'   => $validated['phone'] ?? null,
            'role_id' => $validated['role_id'],
            'status'  => $validated['status'] ?? true,
        ];

        if (isset($validated['username'])) {
            $updateData['username'] = !empty($validated['username']) ? Str::slug($validated['username']) : null;
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'ইউজারের তথ্য সফলভাবে আপডেট করা হয়েছে!');
    }

    /**
     * Remove the specified shop user.
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'আপনি নিজের অ্যাকাউন্ট ডিলিট করতে পারবেন না!');
        }

        $user->delete();

        return redirect()->back()->with('success', 'ইউজার সফলভাবে মুছে ফেলা হয়েছে!');
    }
}
