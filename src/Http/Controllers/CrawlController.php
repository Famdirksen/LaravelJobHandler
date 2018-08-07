<?php

namespace Famdirksen\LaravelJobHandler\Http\Controllers;

use Famdirksen\LaravelJobHandler\Models\Crawlers;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatus;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatusLogs;

class CrawlController
{
    protected $crawler;
    protected $crawler_id;


    /**
     * Get the latest crawler data from the database
     */
    protected function getCrawler()
    {
        $this->crawler = Crawlers::findOrFail($this->crawler_id);
    }

    /**
     * Setup the crawler so it won't run twice at the same time
     *
     * @param $crawler_id
     */
    public function setupCrawler($crawler_id)
    {
        $this->crawler_id = $crawler_id;
        $times = config('laravel-job-handler.run_times', 10);

        for ($x = 0; $x <= $times; $x++) {
            //fetch the last data
            $this->getCrawler();

            if (!$this->crawler->enabled) {
                throw new \Exception('Crawler (#'.$this->crawler_id.') - crawler isnt enabled in database');
            }
            if ($this->crawler->latest_status == 4) {
                throw new \Exception('Crawler (#'.$this->crawler_id.') - last run had an error and the developer needs to reenable this crawler');
            }
            if ($this->crawler->latest_status == 3) {
                throw new \Exception('Crawler (#'.$this->crawler_id.') - last run had an error');
            }
            if ($this->crawler->latest_status == 2) {
                //Done running...
                break;
            }

            if(is_null($this->crawler->latest_status)) {
                //first time it runs...
                break;
            }

            if ($x == $times) {
                $this->failCrawler('Crawler (#'.$this->crawler_id.') - max execution time');
            }

            if ($this->crawler->status == 1) {
                if ($this->crawler->multiple) {
                    break;
                }

                sleep(config('laravel-job-handler.retry_in_seconds', 3)); //retry in 3 seconds
            }
        }

        $this->startCrawler();
    }
    /**
     * Start the crawler and save it to the database
     *
     * @param string $output
     */
    public function startCrawler($output = '')
    {
        return $this->addStatus(1, $output); //start running
    }
    /**
     * set the crawler as done so other scripts can run
     *
     * @param string $output
     */
    public function doneCrawler($output = '')
    {
        return $this->addStatus(2, $output); //done running
    }
    public function finish($output = '') {
        return $this->doneCrawler($output);
    }
    /**
     * crawler failed...
     *
     * @param string $output
     */
    public function failCrawler($output = '')
    {
        $this->addStatus(3, $output); //failed

        throw new \Exception($output.' - status 3');
    }
    /**
     * save the latest crawler status to the database
     *
     * @param $status
     * @param string $output
     * @return bool
     */
    protected function addStatus($status, $output = '')
    {
        $crawlerstatus = new CrawlerStatus();

        $crawlerstatus->crawler_id = $this->crawler_id;
        $crawlerstatus->status = $status;

        if ($crawlerstatus->save()) {
            $this->crawler->latest_status = $status;

            $this->crawler->save();

            if (!empty($output)) {
                $crawlerstatuslog = new CrawlerStatusLogs();

                $crawlerstatuslog->status_id = $crawlerstatus->id;
                $crawlerstatuslog->output = $output;

                $crawlerstatuslog->save();
            }

            return true;
        } else {
            throw new \Exception('Cannot save crawlerstatus to database...');
        }
    }
}
