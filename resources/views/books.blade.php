@extends('layouts.app')

@section('title', 'Daftar Buku')

@section('content')
    <h1>Daftar Buku</h1>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Judul</th>
                <th>Deskripsi</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Foto Sampul</th>
                <th>Genre</th>
                <th>Author</th>
            </tr>
        </thead>
        <tbody>
            @foreach($books as $book)
            <tr>
                <td>{{ $book->id }}</td>
                <td>{{ $book->title }}</td>
                <td>{{ $book->description }}</td>
                <td>{{ $book->price }}</td>
                <td>{{ $book->stock }}</td>
                <td>{{ $book->cover_photo }}</td>
                <td>{{ $book->genre ? $book->genre->name : '-' }}</td>
                <td>{{ $book->author ? $book->author->name : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection