<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageSeederService
{
    /**
     * Copy seeder images to public storage
     *
     * @param string $type - categories, locations, subcategories
     * @return array
     */
    public function copySeederImages(string $type): array
    {
        $sourceDir = database_path("seeders/images/{$type}");
        $targetDir = storage_path("app/public/{$type}");
        
        if (!File::exists($sourceDir)) {
            Log::warning("Seeder images directory not found: {$sourceDir}");
            return [
                'success' => false,
                'message' => "Seeder images directory not found for {$type}",
                'copied' => 0
            ];
        }

        // Ensure target directory exists
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $files = File::files($sourceDir);
        $copiedCount = 0;
        $errors = [];

        foreach ($files as $file) {
            try {
                $filename = $file->getFilename();
                $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
                
                // Only copy if file doesn't exist or is different
                if (!File::exists($targetPath) || File::size($file->getPathname()) !== File::size($targetPath)) {
                    File::copy($file->getPathname(), $targetPath);
                    $copiedCount++;
                    
                    Log::info("Copied seeder image: {$filename} to {$type}");
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to copy {$file->getFilename()}: " . $e->getMessage();
                Log::error("Failed to copy seeder image", [
                    'file' => $file->getFilename(),
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) 
                ? "Successfully copied {$copiedCount} images for {$type}"
                : "Copied {$copiedCount} images with " . count($errors) . " errors",
            'copied' => $copiedCount,
            'total' => count($files),
            'errors' => $errors
        ];
    }

    /**
     * Copy all seeder images (categories, locations, subcategories)
     *
     * @return array
     */
    public function copyAllSeederImages(): array
    {
        $types = ['categories', 'locations', 'subcategories'];
        $results = [];
        $totalCopied = 0;
        $totalErrors = [];

        foreach ($types as $type) {
            $result = $this->copySeederImages($type);
            $results[$type] = $result;
            $totalCopied += $result['copied'];
            $totalErrors = array_merge($totalErrors, $result['errors']);
        }

        return [
            'success' => empty($totalErrors),
            'message' => empty($totalErrors)
                ? "Successfully copied {$totalCopied} images across all types"
                : "Copied {$totalCopied} images with " . count($totalErrors) . " total errors",
            'total_copied' => $totalCopied,
            'results' => $results,
            'errors' => $totalErrors
        ];
    }

    /**
     * Get the public URL for a seeder image
     *
     * @param string $type
     * @param string $filename
     * @return string
     */
    public function getImageUrl(string $type, string $filename): string
    {
        return Storage::url("{$type}/{$filename}");
    }

    /**
     * Check if seeder images exist for a type
     *
     * @param string $type
     * @return array
     */
    public function checkSeederImages(string $type): array
    {
        $sourceDir = database_path("seeders/images/{$type}");
        $targetDir = storage_path("app/public/{$type}");
        
        $sourceExists = File::exists($sourceDir);
        $targetExists = File::exists($targetDir);
        
        $sourceCount = $sourceExists ? count(File::files($sourceDir)) : 0;
        $targetCount = $targetExists ? count(File::files($targetDir)) : 0;
        
        return [
            'type' => $type,
            'source_exists' => $sourceExists,
            'target_exists' => $targetExists,
            'source_count' => $sourceCount,
            'target_count' => $targetCount,
            'needs_copy' => $sourceExists && ($sourceCount !== $targetCount),
            'source_path' => $sourceDir,
            'target_path' => $targetDir
        ];
    }

    /**
     * Get status of all seeder image types
     *
     * @return array
     */
    public function getSeederImagesStatus(): array
    {
        $types = ['categories', 'locations', 'subcategories'];
        $status = [];
        
        foreach ($types as $type) {
            $status[$type] = $this->checkSeederImages($type);
        }
        
        return $status;
    }

    /**
     * Clean up orphaned images in public storage that don't exist in seeders
     *
     * @param string $type
     * @return array
     */
    public function cleanupOrphanedImages(string $type): array
    {
        $sourceDir = database_path("seeders/images/{$type}");
        $targetDir = storage_path("app/public/{$type}");
        
        if (!File::exists($sourceDir) || !File::exists($targetDir)) {
            return [
                'success' => false,
                'message' => "Source or target directory not found",
                'deleted' => 0
            ];
        }

        $sourceFiles = collect(File::files($sourceDir))->map(fn($file) => $file->getFilename());
        $targetFiles = File::files($targetDir);
        
        $deletedCount = 0;
        $errors = [];

        foreach ($targetFiles as $file) {
            $filename = $file->getFilename();
            
            // If file doesn't exist in seeder images, delete it
            if (!$sourceFiles->contains($filename)) {
                try {
                    File::delete($file->getPathname());
                    $deletedCount++;
                    Log::info("Deleted orphaned image: {$filename} from {$type}");
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete {$filename}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => empty($errors),
            'message' => "Deleted {$deletedCount} orphaned images from {$type}",
            'deleted' => $deletedCount,
            'errors' => $errors
        ];
    }
}
