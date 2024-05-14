#!/usr/bin/env bash
echo "Running composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "Seeding locations..."
php artisan db:seed --class=LocationSeeder

echo "Seeding categories..."
php artisan db:seed --class=CategorySeeder

echo "Seeding subcategories..."
php artisan db:seed --class=SubcategorySeeder