<?php

namespace Famdirksen\LaravelJobHandler\Http\Controllers;

use Carbon\Carbon;
use Famdirksen\LaravelJobHandler\Exceptions\ClearAllLogsException;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatus;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatusLogs;

class CrawlLogController
{
    public function clearAllLogs()
    {
        $enabled = false;
        $config = config('laravel-job-handler.clear-log-after-seconds', null);

        if (!is_null($config)) {
            $enabled = true;
        }

        if ($enabled) {
            $this->clearAllLogsBefore(Carbon::now()->subSeconds($config));
        } else {
            //deleting of old logs is disabled
        }
    }
    protected function clearAllLogsBefore(Carbon $carbon)
    {
        $date_string = $carbon->copy()->format('Y-m-d H:i:s');

        CrawlerStatus::where('created_at', '<=', $date_string)
            ->orWhereNull('created_at')
            ->delete();
        CrawlerStatusLogs::where('created_at', '<=', $date_string)
            ->orWhereNull('created_at')
            ->delete();
    }
}
