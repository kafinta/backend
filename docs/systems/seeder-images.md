# Seeder Images System

## Overview

The seeder images system ensures that images used in database seeders are version-controlled and available in both development and production environments.

## Directory Structure

```
database/
  seeders/
    images/
      categories/
        appliances.jpg
        decor.jpg
        furniture.jpg
        ...
      locations/
        living.jpg
        bedroom.jpg
        kitchen.jpg
        ...
      subcategories/
        (any subcategory images)
```

## How It Works

1. **Seeder Images Storage**: All images used by seeders are stored in `database/seeders/images/`
2. **Automatic Copying**: Before seeding, images are copied from seeder directory to `storage/app/public/`
3. **Version Control**: Seeder images are committed to git, ensuring availability in production
4. **Smart Copying**: Only copies images that don't exist or have changed (hash comparison)

## Commands

### Copy Images Manually
```bash
# Copy all seeder images to storage
php artisan seed:copy-images

# Force overwrite existing images
php artisan seed:copy-images --force
```

### Automatic Copying
Images are automatically copied when running:
```bash
# Full database seeding (includes image copying)
php artisan db:seed

# Individual seeders also copy their images
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=LocationSeeder
```

## Adding New Images

### For Categories
1. Add image to `database/seeders/images/categories/`
2. Update `CategorySeeder.php` to reference the image:
   ```php
   Category::create([
       'name' => 'New Category',
       'image_path' => '/storage/categories/new-image.jpg'
   ]);
   ```

### For Locations
1. Add image to `database/seeders/images/locations/`
2. Update `LocationSeeder.php` to reference the image:
   ```php
   [
       'name' => 'New Location',
       'image_path' => '/storage/locations/new-image.jpg'
   ]
   ```

### For Other Models
1. Create directory: `database/seeders/images/model-name/`
2. Add images to the directory
3. Update the seeder to copy images (see CategorySeeder example)
4. Reference images in seeder data

## Implementation Details

### Individual Seeders
Each seeder that uses images has a `copySeederImages()` method:

```php
private function copySeederImages(): void
{
    $sourceDir = database_path('seeders/images/categories');
    $targetDir = storage_path('app/public/categories');

    // Create target directory if it doesn't exist
    if (!File::exists($targetDir)) {
        File::makeDirectory($targetDir, 0755, true);
    }

    // Copy images with hash comparison
    if (File::exists($sourceDir)) {
        $files = File::files($sourceDir);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
            
            // Only copy if file doesn't exist or is different
            if (!File::exists($targetPath) || File::hash($file->getPathname()) !== File::hash($targetPath)) {
                File::copy($file->getPathname(), $targetPath);
                $this->command->info("Copied image: {$filename}");
            }
        }
    }
}
```

### Global Command
The `seed:copy-images` command handles all image types:
- Automatically detects image directories
- Copies all images with smart comparison
- Provides detailed output
- Supports force overwrite option

## Benefits

1. **Version Control**: Images are tracked in git
2. **Production Ready**: Images available in production deployments
3. **Consistency**: Same images across all environments
4. **Efficiency**: Only copies changed images
5. **Automation**: Integrated with seeding process
6. **Flexibility**: Easy to add new image types

## Production Deployment

When deploying to production:

1. Images are already in the repository
2. Run `php artisan db:seed` - images are automatically copied
3. No manual image upload required
4. Consistent seeding across environments

## File Naming Conventions

- Use descriptive, lowercase names
- Use hyphens for spaces: `game-room.jpg`
- Include file extensions: `.jpg`, `.png`, `.jpeg`, `.webp`
- Keep names consistent with model names where possible

## Troubleshooting

### Images Not Copying
- Check if source directory exists: `database/seeders/images/type/`
- Verify file permissions on storage directory
- Run with `--force` to overwrite existing files

### Images Not Displaying
- Ensure storage link is created: `php artisan storage:link`
- Check image paths in database match actual files
- Verify web server can serve files from `storage/app/public/`

### Missing Images in Production
- Ensure images are committed to git
- Run `php artisan seed:copy-images` after deployment
- Check storage directory permissions
