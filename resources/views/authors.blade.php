@extends('layouts.app')

@section('title', 'Daftar Author')

@section('content')
    <h1>Daftar Author</h1>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Author</th>
                <th>Foto</th>
                <th>Biografi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($authors as $author)
            <tr>
                <td>{{ $author->id }}</td>
                <td>{{ $author->name }}</td>
                <td>{{ $author->photo }}</td>
                <td>{{ $author->bio }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

