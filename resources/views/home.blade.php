<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Portfolion Framework' }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }
        header {
            background-color: #343a40;
            color: #fff;
            padding: 1rem 0;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        .navbar ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .navbar ul li {
            margin-left: 20px;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
        }
        .hero {
            background-color: #007bff;
            color: #fff;
            padding: 3rem 0;
            text-align: center;
        }
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background-color: #fff;
            color: #007bff;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #f0f0f0;
        }
        .features {
            padding: 3rem 0;
        }
        .features h2 {
            text-align: center;
            margin-bottom: 2rem;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .feature-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .feature-card h3 {
            margin-top: 0;
        }
        footer {
            background-color: #343a40;
            color: #fff;
            padding: 1.5rem 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <h1>Portfolion</h1>
                <ul>
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('about') }}">About</a></li>
                    <li><a href="{{ url('contact') }}">Contact</a></li>
                    <li><a href="https://github.com/yourusername/portfolion">GitHub</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h2>{{ $title }}</h2>
            <p>{{ $description ?? 'A lightweight, modern PHP framework for building web applications and APIs.' }}</p>
            <a href="{{ url('docs') }}" class="btn">Get Started</a>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Features</h2>
            <div class="feature-grid">
                @foreach ($features as $feature)
                <div class="feature-card">
                    <h3>{{ $feature }}</h3>
                    <p>Experience the power and simplicity of the {{ $feature }} in Portfolion Framework.</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} Portfolion Framework. All rights reserved.</p>
        </div>
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html> 