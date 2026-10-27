<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    // Affiche la liste des plats.
    public function index()
    {
        $menus = Menu::with('category')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.menus.index', compact('menus'));
    }

    // Affiche le formulaire de création d'un plat.
    public function create()
    {
        $categories = Category::all();

        return view('admin.menus.create', compact('categories'));
    }

    // Valide et enregistre un nouveau plat.
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menus', 'public');
        }

        $data['is_available'] = $request->has('is_available');

        Menu::create($data);

        return redirect()->route('admin.menus.index')
            ->with('success', 'Le plat a bien été créé.');
    }

    // Affiche le formulaire de modification d'un plat.
    public function edit(Menu $menu)
    {
        $categories = Category::all();

        return view('admin.menus.edit', compact('menu', 'categories'));
    }

    // Valide et met à jour un plat.
    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('menus', 'public');

            if ($menu->image && Storage::disk('public')->exists($menu->image)) {
                Storage::disk('public')->delete($menu->image);
            }

            $data['image'] = $newImage;
        }

        $data['is_available'] = $request->has('is_available');
        $menu->update($data);

        return redirect()->route('admin.menus.index')
            ->with('success', 'Le plat a bien été modifié.');
    }

    // Supprime un plat et son image éventuelle.
    public function destroy(Menu $menu)
    {
        if ($menu->image && Storage::disk('public')->exists($menu->image)) {
            Storage::disk('public')->delete($menu->image);
        }

        $menu->delete();

        return redirect()->route('admin.menus.index')
            ->with('success', 'Le plat a bien été supprimé.');
    }

    // Inverse la disponibilité d'un plat.
    public function toggle(Menu $menu)
    {
        $menu->is_available = ! $menu->is_available;
        $menu->save();

        return back()->with('success', 'La disponibilité du plat a été mise à jour.');
    }
}
