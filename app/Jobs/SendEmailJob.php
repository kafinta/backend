<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, then 60s, then 120s between retries
    }

    protected $mailable;
    protected $recipient;
    protected $emailType;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(Mailable $mailable, string $recipient, string $emailType, int $userId = null)
    {
        $this->mailable = $mailable;
        $this->recipient = $recipient;
        $this->emailType = $emailType;
        $this->userId = $userId;

        // Set queue priority based on email type
        $this->onQueue($this->getQueueName($emailType));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->recipient)->send($this->mailable);

            Log::info('Email sent successfully via queue', [
                'email_type' => $this->emailType,
                'recipient' => $this->recipient,
                'user_id' => $this->userId,
                'attempt' => $this->attempts()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email via queue', [
                'email_type' => $this->emailType,
                'recipient' => $this->recipient,
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email job failed permanently', [
            'email_type' => $this->emailType,
            'recipient' => $this->recipient,
            'user_id' => $this->userId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // You could send an alert to administrators here
        // or store failed emails in a separate table for manual retry
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            // Prevent duplicate emails from being sent simultaneously
            new WithoutOverlapping($this->recipient . '_' . $this->emailType)
        ];
    }

    /**
     * Determine which queue to use based on email type
     */
    private function getQueueName(string $emailType): string
    {
        return match($emailType) {
            'verification', 'password_reset' => 'high',     // Critical emails
            'welcome', 'notification' => 'default',        // Standard emails
            'order_confirmation' => 'high',                 // Important emails
            'seller_status' => 'default',                   // Standard emails
            default => 'default'
        };
    }
}
