#!/bin/bash

# Portfolion Framework Setup Script

echo "===== Portfolion Framework Setup ====="
echo ""

# Run diagnostic command
echo "Running diagnostic command..."
php portfolion diagnostic

# Run cache test command
echo ""
echo "Running cache test command..."
php portfolion cache:test

# Create queue tables
echo ""
echo "Creating queue tables..."
php portfolion queue:migrate

# Dispatch a test job
echo ""
echo "Dispatching a test job..."
php portfolion queue:dispatch "Test job from setup script"

# Process a single job
echo ""
echo "Processing a single job..."
php portfolion queue:work --once

echo ""
echo "===== Setup Complete ====="
echo "The Portfolion framework is now ready to use."
echo "" 