<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CopySeederImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:copy-images {--force : Force overwrite existing images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy seeder images to storage directory for database seeding';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Copying seeder images to storage...');

        $force = $this->option('force');
        $imageTypes = ['categories', 'locations', 'subcategories'];
        $totalCopied = 0;

        foreach ($imageTypes as $type) {
            $copied = $this->copyImagesForType($type, $force);
            $totalCopied += $copied;
        }

        if ($totalCopied > 0) {
            $this->info("Successfully copied {$totalCopied} images.");
        } else {
            $this->info('No images needed copying.');
        }

        return Command::SUCCESS;
    }

    /**
     * Copy images for a specific type (categories, locations, etc.)
     */
    private function copyImagesForType(string $type, bool $force = false): int
    {
        $sourceDir = database_path("seeders/images/{$type}");
        $targetDir = storage_path("app/public/{$type}");
        $copied = 0;

        if (!File::exists($sourceDir)) {
            $this->warn("Seeder images directory not found: {$sourceDir}");
            return 0;
        }

        // Create target directory if it doesn't exist
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            $this->info("Created directory: {$targetDir}");
        }

        $files = File::files($sourceDir);

        if (empty($files)) {
            $this->warn("No images found in: {$sourceDir}");
            return 0;
        }

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

            $shouldCopy = $force ||
                         !File::exists($targetPath) ||
                         File::hash($file->getPathname()) !== File::hash($targetPath);

            if ($shouldCopy) {
                File::copy($file->getPathname(), $targetPath);
                $this->line("Copied {$type} image: {$filename}");
                $copied++;
            }
        }

        if ($copied === 0) {
            $this->info("All {$type} images are already up to date.");
        }

        return $copied;
    }
}
