<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Affiche le catalogue complet avec recherche et filtre par catégorie.
    public function index(Request $request)
    {
        $query = Menu::with('category')->orderBy('title');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($sub) use ($request) {
                $sub->where('slug', $request->category);
            });
        }

        if ($request->filled('q')) {
            $term = $request->q;

            $query->where(function ($sub) use ($term) {
                $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $menus = $query->paginate(12)->withQueryString();

        $categories = Category::orderByRaw('LOWER(name) ASC')->get();

        return view('menu.index', compact('menus', 'categories'));
    }

    // Affiche un plat et ses avis approuvés.
    public function show(Menu $menu)
    {
        $menu->load('category');

        $comments = $menu->comments()
            ->where('is_approved', true)
            ->with('user')
            ->latest()
            ->get();

        $averageRating = $menu->comments()
            ->where('is_approved', true)
            ->avg('rating');

        return view('menu.show', compact('menu', 'comments', 'averageRating'));
    }
}
