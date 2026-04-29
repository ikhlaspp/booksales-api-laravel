<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with(['author', 'genre'])->get();
        return response()->json($books);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'cover_photo' => 'nullable|string|max:255',
            'genre_id' => 'nullable|exists:genres,id',
            'author_id' => 'nullable|exists:authors,id',
        ]);

        $book = Book::create($validatedData);

        return response()->json([
            'message' => 'Book created successfully',
            'data' => $book->load(['author', 'genre'])
        ], 201);
    }

    public function show($id)
    {
        $book = Book::with(['author', 'genre'])->findOrFail($id);
        return response()->json($book);
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'cover_photo' => 'nullable|string|max:255',
            'genre_id' => 'nullable|exists:genres,id',
            'author_id' => 'nullable|exists:authors,id',
        ]);

        $book->update($validatedData);

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => $book->load(['author', 'genre'])
        ]);
    }

    public function destroy($id)
    {
        $book = Book::findOrFail($id);
        $book->delete();

        return response()->json(['message' => 'Book deleted successfully']);
    }
}
