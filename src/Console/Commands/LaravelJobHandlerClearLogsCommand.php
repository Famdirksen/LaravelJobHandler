<?php

namespace Famdirksen\LaravelJobHandler\Console\Commands;

use Famdirksen\LaravelJobHandler\Http\Controllers\CrawlController;
use Famdirksen\LaravelJobHandler\Http\Controllers\CrawlLogController;
use Illuminate\Console\Command;

class LaravelJobHandlerClearLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ljh:clear_logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all the logs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $clc = new CrawlLogController();
        $clc->clearAllLogs();
    }
}
