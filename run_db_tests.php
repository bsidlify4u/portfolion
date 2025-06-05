<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Colors for output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m"
];

echo "{$colors['blue']}Running database tests for multiple database engines...{$colors['reset']}\n\n";

// Define the test classes to run
$testClasses = [
    'Tests\Database\ConnectionTest',
    'Tests\Database\SchemaTest',
    'Tests\Database\QueryBuilderTest',
    'Tests\Database\ModelTest',
    'Tests\Database\MigrationTest'
];

// Create a temporary PHPUnit configuration file
$configFile = 'phpunit-db-tests.xml';
$configXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Database">
            <directory suffix="Test.php">./tests/Database</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="mysql"/>
    </php>
</phpunit>
XML;

file_put_contents($configFile, $configXml);

// Build the PHPUnit command
$command = "vendor/bin/phpunit --configuration {$configFile}";

// Execute the command
echo "Executing: {$command}\n\n";
system($command, $exitCode);

// Clean up
unlink($configFile);

// Exit with the same code as PHPUnit
exit($exitCode); 