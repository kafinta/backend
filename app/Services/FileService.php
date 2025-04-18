<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Upload a file to storage
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory The directory to store the file in
     * @param string|null $filename Custom filename (optional)
     * @return string|null The path to the stored file or null if upload failed
     */
    public function uploadFile(UploadedFile $file, string $directory, ?string $filename = null): ?string
    {
        try {
            // Log file details
            Log::info('Uploading file', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'directory' => $directory
            ]);

            // Validate file
            if (!$file->isValid()) {
                Log::error('Invalid file upload', ['error' => $file->getError()]);
                return null;
            }

            // Generate secure filename if not provided
            if (!$filename) {
                $filename = $this->generateSecureFilename($file);
            }

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            // Verify file was stored successfully
            if (!$path) {
                Log::error('Failed to store file', [
                    'directory' => $directory,
                    'filename' => $filename
                ]);
                return null;
            }

            // Verify file exists in storage
            if (!Storage::disk('public')->exists($path)) {
                Log::error('File not found after upload', ['path' => $path]);
                return null;
            }

            // Return standardized path with /storage/ prefix
            $fullPath = '/storage/' . $path;

            Log::info('File uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'path' => $fullPath
            ]);

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Exception during file upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Generate a secure filename for an uploaded file
     *
     * @param UploadedFile $file The uploaded file
     * @return string The generated filename
     */
    public function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->timestamp;
        $uuid = (string) Str::uuid();
        
        return "{$timestamp}_{$uuid}.{$extension}";
    }

    /**
     * Delete a file from storage
     *
     * @param string $path The path to the file
     * @return bool Whether the file was deleted successfully
     */
    public function deleteFile(string $path): bool
    {
        try {
            // Remove /storage/ prefix if present
            $path = str_replace('/storage/', '', $path);

            // Check if file exists
            if (!Storage::disk('public')->exists($path)) {
                Log::warning('File not found for deletion', ['path' => $path]);
                return false;
            }

            // Delete file
            $result = Storage::disk('public')->delete($path);

            if ($result) {
                Log::info('File deleted successfully', ['path' => $path]);
            } else {
                Log::error('Failed to delete file', ['path' => $path]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception during file deletion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Move a file to a new location
     *
     * @param string $currentPath The current path of the file
     * @param string $newDirectory The new directory to move the file to
     * @param string|null $newFilename The new filename (optional)
     * @return string|null The new path or null if move failed
     */
    public function moveFile(string $currentPath, string $newDirectory, ?string $newFilename = null): ?string
    {
        try {
            // Remove /storage/ prefix if present
            $currentPath = str_replace('/storage/', '', $currentPath);

            // Check if file exists
            if (!Storage::disk('public')->exists($currentPath)) {
                Log::warning('File not found for moving', ['path' => $currentPath]);
                return null;
            }

            // Generate new filename if not provided
            if (!$newFilename) {
                $extension = pathinfo($currentPath, PATHINFO_EXTENSION);
                $newFilename = $this->generateSecureFilename(null) . '.' . $extension;
            }

            // Move file
            $newPath = Storage::disk('public')->putFileAs(
                $newDirectory,
                Storage::disk('public')->path($currentPath),
                $newFilename
            );

            // Verify file was moved successfully
            if (!$newPath) {
                Log::error('Failed to move file', [
                    'from' => $currentPath,
                    'to' => "{$newDirectory}/{$newFilename}"
                ]);
                return null;
            }

            // Delete original file
            Storage::disk('public')->delete($currentPath);

            // Return standardized path with /storage/ prefix
            $fullPath = '/storage/' . $newPath;

            Log::info('File moved successfully', [
                'from' => $currentPath,
                'to' => $fullPath
            ]);

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Exception during file move', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get file information
     *
     * @param string $path The path to the file
     * @return array|null File information or null if file not found
     */
    public function getFileInfo(string $path): ?array
    {
        try {
            // Remove /storage/ prefix if present
            $path = str_replace('/storage/', '', $path);

            // Check if file exists
            if (!Storage::disk('public')->exists($path)) {
                Log::warning('File not found for info', ['path' => $path]);
                return null;
            }

            // Get file info
            $size = Storage::disk('public')->size($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            $lastModified = Storage::disk('public')->lastModified($path);

            return [
                'path' => '/storage/' . $path,
                'filename' => basename($path),
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'mime_type' => $mimeType,
                'last_modified' => date('Y-m-d H:i:s', $lastModified)
            ];
        } catch (\Exception $e) {
            Log::error('Exception getting file info', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Format file size in human-readable format
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
