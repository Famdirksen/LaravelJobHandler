<?php

namespace Famdirksen\LaravelJobHandler\Jobs;

use Famdirksen\LaravelJobHandler\Http\Controllers\CrawlLogController;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LaravelJobHandlerClearLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->queue = config('laravel-job-handler.clear_log_via_job_queue', 'default');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $clc = new CrawlLogController();
        $clc->clearAllLogs(true);
    }
}
