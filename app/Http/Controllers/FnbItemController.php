<?php

namespace App\Http\Controllers;

use App\Models\FnbItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FnbItemController extends Controller
{
    public function index()
    {
        return Inertia::render('Master/FnbItems', [
            'items' => FnbItem::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('fnb_images', 'public');
            $validated['image_path'] = $path;
        }

        FnbItem::create($validated);
        return back()->with('success', 'Menu F&B berhasil ditambahkan.');
    }

    public function update(Request $request, FnbItem $fnbItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp'
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($fnbItem->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($fnbItem->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($fnbItem->image_path);
            }
            $path = $request->file('image')->store('fnb_images', 'public');
            $validated['image_path'] = $path;
        }

        $fnbItem->update($validated);
        return back()->with('success', 'Menu F&B berhasil diupdate.');
    }

    public function destroy(FnbItem $fnbItem)
    {
        if ($fnbItem->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($fnbItem->image_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($fnbItem->image_path);
        }
        $fnbItem->delete();
        return back()->with('success', 'Menu F&B berhasil dihapus.');
    }
}
