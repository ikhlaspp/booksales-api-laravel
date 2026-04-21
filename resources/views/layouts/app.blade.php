<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Booksales App')</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        nav { background-color: #343a40; padding: 15px 20px; }
        nav a { color: white; text-decoration: none; margin-right: 20px; font-weight: bold; }
        nav a:hover { text-decoration: underline; color: #ffc107; }
        .container { padding: 20px; max-width: 800px; margin: 0 auto; background: white; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <nav>
        <a href="{{ url('/') }}">Home</a>
        <a href="{{ url('/genres') }}">Genres</a>
        <a href="{{ url('/authors') }}">Authors</a>
        <a href="{{ url('/books') }}">Books</a>
    </nav>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>