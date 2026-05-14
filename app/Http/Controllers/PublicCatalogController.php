<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['author', 'genre'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhereHas('author', function($sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('genre', function($sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('genre_id')) {
            $query->where('genre_id', $request->genre_id);
        }

        return response()->json($query->paginate($request->per_page ?? 12));
    }
}
