<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-queue {--timeout=60 : Maximum execution time in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the email queue with priority handling';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');

        $this->info('Starting email queue processing...');
        $this->info("Timeout: {$timeout} seconds");

        // Process high priority queue first (verification, password reset, orders)
        $this->info('Processing high priority emails...');
        Artisan::call('queue:work', [
            '--queue' => 'high',
            '--timeout' => $timeout,
            '--tries' => 3,
            '--max-jobs' => 10,
            '--stop-when-empty' => true
        ]);

        // Then process default queue (welcome, notifications, etc.)
        $this->info('Processing standard priority emails...');
        Artisan::call('queue:work', [
            '--queue' => 'default',
            '--timeout' => $timeout,
            '--tries' => 3,
            '--max-jobs' => 20,
            '--stop-when-empty' => true
        ]);

        $this->info('Email queue processing completed.');

        return Command::SUCCESS;
    }
}
