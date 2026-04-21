@extends('layouts.app')

@section('title', 'Daftar Genre')

@section('content')
    <h1>Daftar Genre</h1>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Genre</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($genres as $genre)
            <tr>
                <td>{{ $genre->id }}</td>
                <td>{{ $genre->name }}</td>
                <td>{{ $genre->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

