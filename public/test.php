<?php

// Simple test script that doesn't rely on the framework's routing

echo "<!DOCTYPE html>
<html>
<head>
    <title>Portfolion Framework Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0066cc;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Portfolion Framework Test</h1>";

// Test PHP version
$phpVersion = phpversion();
$phpVersionOk = version_compare($phpVersion, '8.0.0', '>=');
echo "<h2>PHP Version Check</h2>";
echo "<p>Current PHP version: {$phpVersion} - ";
echo $phpVersionOk 
    ? "<span class='success'>OK</span>" 
    : "<span class='error'>Error: PHP 8.0.0 or higher required</span>";
echo "</p>";

// Test required extensions
echo "<h2>Required Extensions</h2>";
echo "<table>
        <tr>
            <th>Extension</th>
            <th>Status</th>
        </tr>";

$requiredExtensions = [
    'pdo',
    'pdo_mysql',
    'pdo_sqlite',
    'json',
    'mbstring',
    'openssl',
    'tokenizer',
    'xml',
    'fileinfo'
];

$allExtensionsOk = true;
foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    if (!$loaded) {
        $allExtensionsOk = false;
    }
    echo "<tr>
            <td>{$extension}</td>
            <td>" . ($loaded ? "<span class='success'>Loaded</span>" : "<span class='error'>Not Loaded</span>") . "</td>
          </tr>";
}
echo "</table>";

// Test directory permissions
echo "<h2>Directory Permissions</h2>";
echo "<table>
        <tr>
            <th>Directory</th>
            <th>Status</th>
        </tr>";

$directories = [
    'storage',
    'storage/cache',
    'storage/logs',
    'storage/framework',
    'public'
];

$allDirsOk = true;
foreach ($directories as $directory) {
    $exists = is_dir($directory);
    $writable = $exists && is_writable($directory);
    if (!$writable) {
        $allDirsOk = false;
    }
    echo "<tr>
            <td>{$directory}</td>
            <td>";
    if (!$exists) {
        echo "<span class='error'>Directory does not exist</span>";
    } else if (!$writable) {
        echo "<span class='error'>Not writable</span>";
    } else {
        echo "<span class='success'>OK</span>";
    }
    echo "</td></tr>";
}
echo "</table>";

// Test environment file
echo "<h2>Environment Configuration</h2>";
$envExists = file_exists('../.env');
echo "<p>.env file: " . ($envExists ? "<span class='success'>Found</span>" : "<span class='error'>Not found</span>") . "</p>";

// Summary
echo "<h2>Summary</h2>";
$allOk = $phpVersionOk && $allExtensionsOk && $allDirsOk && $envExists;
echo "<p>Overall status: " . ($allOk ? "<span class='success'>All checks passed!</span>" : "<span class='error'>Some checks failed</span>") . "</p>";

echo "<p>You can now proceed to set up your application.</p>";

echo "</div>
</body>
</html>"; 