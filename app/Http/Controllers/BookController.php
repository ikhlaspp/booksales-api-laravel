<?php

namespace App\Http\Controllers;

use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with(['author', 'genre'])->get();
        return view('books', compact('books'));
    }
}
