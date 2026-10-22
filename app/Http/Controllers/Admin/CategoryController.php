<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // Affiche les catégories et le nombre de plats associés.
    public function index()
    {
        $categories = Category::withCount('menus')->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    // Affiche le formulaire de création d'une catégorie.
    public function create()
    {
        return view('admin.categories.create');
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $data['slug'] = Str::slug($request->name);
        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'La catégorie a bien été créée.');
    }

    
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
        ]);

        $data['slug'] = Str::slug($request->name);
        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'La catégorie a bien été modifiée.');
    }

  
    public function destroy(Category $category)
    {
        
        if ($category->menus()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Impossible de supprimer cette catégorie : elle contient encore des plats. Supprimez ou déplacez ces plats d\'abord.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'La catégorie a bien été supprimée.');
    }
}
