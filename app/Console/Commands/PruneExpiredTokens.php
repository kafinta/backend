<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class PruneExpiredTokens extends Command
{
    protected $signature = 'tokens:prune';
    protected $description = 'Prune expired tokens';

    public function handle()
    {
        $expiration = config('sanctum.expiration');
        
        if ($expiration) {
            $tokens = PersonalAccessToken::where('created_at', '<', 
                Carbon::now()->subMinutes($expiration)
            )->delete();
            
            $this->info('Expired tokens pruned successfully.');
        }
    }
} 