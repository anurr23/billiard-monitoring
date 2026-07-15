<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return Inertia::render('Master/Users', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'role' => ['required', Rule::in(['admin', 'kasir'])],
            'password' => 'required|string|min:4',
            'is_active' => 'sometimes|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('user_photos', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);

        $validated['password'] = Hash::make($validated['password']);

        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        User::create($validated);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'kasir'])],
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:4',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->photo_path);
            }
            $path = $request->file('photo')->store('user_photos', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['is_active']) && ! $validated['is_active'] && $user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }

        $user->update($validated);

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        // Don't allow deleting yourself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Tidak bisa menghapus akun sendiri.']);
        }

        if ($user->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->photo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->photo_path);
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
