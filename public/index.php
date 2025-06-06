<?php

// Simple welcome page that doesn't rely on the framework's routing

echo "<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Portfolion Framework</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #0066cc;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
            color: #0066cc;
        }
        p {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #0066cc;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin: 10px;
        }
        .btn:hover {
            background-color: #0052a3;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='logo'>P</div>
        <h1>Welcome to Portfolion Framework</h1>
        <p>Your application is now set up and ready to use.</p>
        <div>
            <a href='test.php' class='btn'>Run Diagnostics</a>
        </div>
    </div>
</body>
</html>";
