<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PackageController extends Controller
{
    public function index()
    {
        return Inertia::render('Master/Packages', [
            'packages' => \App\Models\Package::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        \App\Models\Package::create($validated);

        return back()->with('success', 'Paket berhasil ditambahkan.');
    }

    public function destroy(\App\Models\Package $package)
    {
        $package->delete();
        return back()->with('success', 'Paket berhasil dihapus.');
    }
}