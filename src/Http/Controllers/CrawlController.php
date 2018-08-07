<?php

namespace Famdirksen\LaravelJobHandler\Http\Controllers;

use Carbon\Carbon;
use Famdirksen\LaravelJobHandler\Exceptions\CrawlerException;
use Famdirksen\LaravelJobHandler\Exceptions\CrawlerNotReachedTimeBetweenJobsException;
use Famdirksen\LaravelJobHandler\Exceptions\CrawlerSaveException;
use Famdirksen\LaravelJobHandler\Models\Crawlers;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatus;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatusLogs;
use Illuminate\Support\Facades\Log;

class CrawlController
{
    protected $crawler;
    protected $crawler_id;
    protected $override_fail_status = false;
    protected $logging = false;
    protected $logs = [];


    public function __construct()
    {
        $this->startLogging();
    }
    public function __destruct()
    {
        $this->stopLogging();
    }

    public function overrideFailStatus(bool $state) {
        $this->log('Setup overrideFailStatus to: '.$state);

        $this->override_fail_status = $state;
    }

    /**
     * Get the latest crawler data from the database
     */
    protected function getCrawler()
    {
        if (empty($this->crawler) || $this->crawler->id != $this->crawler_id) {
            $this->log('Loading new crawler data');
            $this->crawler = Crawlers::findOrFail($this->crawler_id);
            $this->log('Loaded new crawler data');
        } else {
            $this->log('Refreshing crawler data');
            $this->crawler = $this->crawler->fresh();
            $this->log('Refreshed crawler data');
        }
    }

    /**
     * Set the crawler id
     *
     * @param $crawler_id
     */
    public function setCrawlerId($crawler_id)
    {
        $this->log('Setting crawler_id');
        $this->crawler_id = $crawler_id;
        $this->log('Set crawler_id');
    }

    /**
     * Return the crawler id
     *
     * @return mixed
     */
    public function getCrawlerId() {
        $this->log('Getting crawler_id');
        return $this->crawler_id;
    }

    /**
     * Check if the controller is setup correctly
     *
     * @return bool
     */
    protected function controllerIsSetup() {
        $this->log('Check if controllerIsSetup');

        if(!is_null($this->crawler_id)) {
            return true;
        }

        return false;
    }

    /**
     * Setup the crawler so it won't run twice at the same time
     *
     * @param $crawler_id
     */
    public function setupCrawler($crawler_id = null)
    {
        $this->log('Setup crawler');

        if(!is_null($crawler_id)) {
            $this->log('Setup crawler, crawler_id is not set');
            $this->setCrawlerId($crawler_id);
        }

        if($this->controllerIsSetup()) {
            $times = config('laravel-job-handler.run_times', 10);

            for ($x = 0; $x <= $times; $x++) {
                //fetch the last data
                $this->getCrawler();

                $this->log('Checking if crawler is enabled');
                if (!$this->crawler->enabled) {
                    $this->log('Crawler is not enabled');
                    throw new CrawlerException('Crawler (#' . $this->crawler_id . ') - crawler isnt enabled in database');
                }

                $this->log('Checking if crawler can be runned');
                $checkIfCrawlerCanBeRunned = $this->canCrawlerRunAfterPeriod();

                if ($checkIfCrawlerCanBeRunned['status']) {
                    $this->log('Checked if crawler can runned');
                    if (is_null($this->crawler->latest_status)) {
                        $this->log('Crawler can be runned, it the first time');

                        //first time it runs...
                        break;
                    }
                    if ($this->crawler->latest_status == 2) {
                        $this->log('Crawler can be runned, last crawler runned successfully');

                        //Done running...
                        break;
                    }



                    if ($this->crawler->latest_status == 3) {
                        if($this->override_fail_status) {
                            $this->log('Last crawler failed, but it is forced to run');

                            //override the failed state, this will force to rerun...
                            break;
                        }

                        $this->log('Last crawler failed, force run is not enabled');
                        throw new CrawlerException('Crawler (#' . $this->crawler_id . ') - last run had an error and override_fail_status is not enabled');
                    }
                } else {
                    $this->log('Crawler needs to wait ('.$checkIfCrawlerCanBeRunned['retry_in'].' seconds) before running again');
                    throw new CrawlerNotReachedTimeBetweenJobsException('Has to wait ' . $checkIfCrawlerCanBeRunned['retry_in'] . ' more seconds to run');
                }

                if ($x == $times) {
                    $this->log('Crawler exceeded the max execution time');
                    $this->failCrawler('Crawler (#' . $this->crawler_id . ') - max execution time');
                }

                if ($this->crawler->status == 1) {
                    if ($this->crawler->multiple_crawlers) {
                        $this->log('Crawler can run multiple crawlers at the same time');
                        break;
                    }

                    $wait = config('laravel-job-handler.retry_in_seconds', 3);

                    $this->log('Waiting for rechecking ('.$wait.' seconds) if crawler can be runned');

                    sleep($wait);
                }
            }

            $this->log('All setup, starting crawler');
            $this->startCrawler();
        } else {
            throw new CrawlerException('CrawlController is not setup correctly.');
        }
    }
    /**
     * Start the crawler and save it to the database
     *
     * @param string $output
     */
    public function startCrawler($output = '')
    {
        $this->log('Starting crawler');

        return $this->addStatus(1, $output); //start running
    }
    /**
     * set the crawler as done so other scripts can run
     *
     * @param string $output
     */
    public function doneCrawler($output = '')
    {
        $this->log('Crawler done');

        return $this->addStatus(2, $output); //done running
    }

    /**
     * Finishing the crawler
     *
     * @param string $output
     * @return bool
     */
    public function finish($output = '')
    {
        $this->log('Finishing crawler');

        return $this->doneCrawler($output);
    }
    /**
     * crawler failed...
     *
     * @param string $output
     */
    public function failCrawler($output = '')
    {
        $this->log('Crawler failed');

        $this->addStatus(3, $output); //failed

        throw new CrawlerException($output.' - status 3');
    }
    /**
     * Save the latest crawler status to the database
     *
     * @param $status
     * @param string $output
     * @return bool
     */
    protected function addStatus($status, $output = '')
    {
        $this->log('Registering status ('.$status.')');

        $crawlerstatus = new CrawlerStatus();

        $crawlerstatus->crawler_id = $this->crawler_id;
        $crawlerstatus->status = $status;

        if ($crawlerstatus->save()) {
            $this->log('Registered status ('.$status.')');
            $this->log('Setting crawler latest status ('.$status.') attribute');

            $this->crawler->latest_status = $status;

            $this->crawler->save();
            $this->log('Set crawler latest status ('.$status.') attribute');


            if (!empty($output)) {
                $formatted_logs[] = [
                    'status_id' => $crawlerstatus->id,
                    'output' => $output
                ];

                CrawlerStatusLogs::insert($formatted_logs);
            }

            if($status == 2) {
                $this->stopLogging($crawlerstatus->id);
            }

            $this->getCrawler();

            return true;
        } else {
            throw new CrawlerSaveException('Cannot save crawlerstatus to database...');
        }
    }
    protected function saveLog($crawlerstatus_id) {
        $formatted_logs = [];

        foreach($this->logs as $log) {
            $formatted_logs[] = [
                'status_id' => $crawlerstatus_id,
                'output' => $log
            ];
        }
        if(count($formatted_logs) > 0) {
            $this->log('Registering crawler logs');

            CrawlerStatusLogs::insert($formatted_logs);

            $this->log('Registered crawler logs (count: ' . count($formatted_logs) . ')');
        } else {
            $this->log('Log output is not set, skipping inserting');
        }
    }

    /**
     * This will define when the job can be runned again
     *
     * @return array
     */
    public function canCrawlerRunAfterPeriod()
    {
        $this->getCrawler();

        if (is_null($this->crawler->time_between)) {
            $this->log('Not time_between specified');

            return $this->canCrawlerRunAfterPeriodStatus(true);
        } else {
            $seconds = $this->crawler->time_between;
        }

        if (!is_null($this->crawler->last_runned_at)) {
            if ($this->crawler->last_runned_at <= Carbon::now()->subSeconds($seconds)) {
                return $this->canCrawlerRunAfterPeriodStatus(true);
            }

            return $this->canCrawlerRunAfterPeriodStatus(false, Carbon::parse($this->crawler->last_runned_at)->diffInSeconds(Carbon::now()->subSeconds($seconds)));
        } else {
            //crawler never runned, so it can run now
            return $this->canCrawlerRunAfterPeriodStatus(true);
        }
    }

    /**
     * Return the status for canCrawlerRunAfterPeriod method
     *
     * @param $status
     * @param int $retry_in
     * @return array
     */
    public function canCrawlerRunAfterPeriodStatus($status, $retry_in = 0)
    {
        return [
            'status' => $status,
            'retry_in' => $retry_in
        ];
    }



    protected function startLogging()
    {
        $this->logging = true;
        $this->log('Started logging');
    }
    protected function stopLogging($crawlerstatus_id = null)
    {
        $this->log('Stop logging');
        $this->logging = false;

        if(!is_null($crawlerstatus_id)) {
            $this->saveLog($crawlerstatus_id);
        }
    }
    protected function log($item = '')
    {
        if($this->logging)
        {
            $log = $item.' (crawler_id: '.$this->crawler_id.')';

            $this->logs[] = $log;
            Log::info($log);
        }
    }
}
