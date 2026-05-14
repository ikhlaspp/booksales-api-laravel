<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $query = Author::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $author = Author::create($validatedData);

        return response()->json([
            'message' => 'Author created successfully',
            'data' => $author
        ], 201);
    }

    public function show($id)
    {
        $author = Author::find($id);
        
        if (!$author) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($author);
    }

    public function update(Request $request, $id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'photo' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $author->update($validatedData);

        return response()->json([
            'message' => 'Author updated successfully',
            'data' => $author
        ]);
    }

    public function destroy($id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $author->delete();

        return response()->json([
            'message' => 'Author deleted successfully'
        ]);
    }
}
