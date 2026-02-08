<?php

namespace App\Console\Commands;

use App\Jobs\PullGoogleCalendarChangesJob;
use App\Models\GoogleCalendarConnection;
use Illuminate\Console\Command;

class PullGoogleCalendarChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-calendar:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull changes from Google Calendar for all active connections';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connections = GoogleCalendarConnection::enabled()->get();

        if ($connections->isEmpty()) {
            $this->info('No active Google Calendar connections found.');

            return self::SUCCESS;
        }

        $this->info("Dispatching pull jobs for {$connections->count()} connection(s)...");

        foreach ($connections as $connection) {
            PullGoogleCalendarChangesJob::dispatch($connection);
            $this->line("  Dispatched job for user #{$connection->user_id} ({$connection->calendar_id})");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
