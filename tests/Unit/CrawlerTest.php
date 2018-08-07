<?php

namespace Famdirksen\LaravelJobHandler\Tests\Unit;

use Carbon\Carbon;
use Famdirksen\LaravelJobHandler\Exceptions\CrawlerAlreadyActivatedException;
use Famdirksen\LaravelJobHandler\Exceptions\CrawlerAlreadyDeactivatedException;
use Famdirksen\LaravelJobHandler\Http\Controllers\CrawlController;
use Famdirksen\LaravelJobHandler\Http\Controllers\CrawlLogController;
use Famdirksen\LaravelJobHandler\LaravelJobHandlerServiceProvider;
use Famdirksen\LaravelJobHandler\Models\Crawlers;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatus;
use Famdirksen\LaravelJobHandler\Models\CrawlerStatusLogs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as TestCase;

class CrawlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../src/migrations');
    }
    protected function getPackageProviders($app): array
    {
        return [
            LaravelJobHandlerServiceProvider::class,
        ];
    }


    protected function getCrawlerData()
    {
        return new Crawlers([
            'name' => 'My first crawler',
            'description' => 'This is a test crawler'
        ]);
    }




    /** @test */
    public function it_can_create_a_crawler()
    {
        $crawler = $this->getCrawlerData();

        $this->assertNull($crawler->id);

        $crawler->save();

        $this->assertNotNull($crawler->id);
    }
    /** @test */
    public function it_can_activate_and_deactivate_a_crawler()
    {
        $crawler = $this->getCrawlerData();

        $this->assertNull($crawler->id);
        $this->assertNull($crawler->enabled);

        $crawler->save();
        $crawler = $crawler->fresh();

        $this->assertNotNull($crawler->id);
        $this->assertTrue($crawler->enabled == 0);

        $crawler->activate();

        $this->assertTrue($crawler->enabled);
    }
    /** @test */
    public function it_can_get_a_crawler()
    {
        $crawler = $this->getCrawlerData();

        $crawler->save();

        $crawler->activate();

        $cc = new CrawlController();

        $cc->setupCrawler($crawler->id);

        $this->assertTrue($cc->doneCrawler('Runned succesfully'));
    }
    /** @test */
    public function it_can_fail_a_crawler()
    {
        $crawler = $this->getCrawlerData();

        $crawler->save();

        $crawler->activate();

        $cc = new CrawlController();
        $cc->setupCrawler($crawler->id);

        $this->expectException(\Exception::class);

        $cc->failCrawler('Failing...');
    }
    /** @test */
    public function it_can_not_activate_a_activated_crawler()
    {
        $crawler = $this->getCrawlerData();

        $crawler->save();

        $this->assertTrue(!$crawler->enabled);
        $crawler->activate();
        $this->assertTrue($crawler->enabled);

        //check if it can be activated again
        $this->expectException(CrawlerAlreadyActivatedException::class);

        $crawler->activate();
    }
    /** @test */
    public function it_can_not_deactivate_a_deactivated_crawler()
    {
        $crawler = $this->getCrawlerData();

        $crawler->save();

        $this->assertTrue(!$crawler->enabled);

        //check if it can be activated again
        $this->expectException(CrawlerAlreadyDeactivatedException::class);

        $crawler->deactivate();
    }



    /** @test */
    public function it_can_run_the_first_time()
    {
        $crawler = $this->getCrawlerData();

        $crawler->time_between = 5;

        $crawler->save();

        $crawler->activate();

        $cc = new CrawlController();
        $cc->setCrawlerId($crawler->id);

        $check = $cc->canCrawlerRunAfterPeriod();

        $this->assertTrue($check['retry_in'] == 0);
        $this->assertTrue($check['status']);
    }
    /** @test */
    public function it_can_specify_a_time_between_jobs()
    {
        $crawler = $this->getCrawlerData();

        $crawler->time_between = 5;

        $crawler->save();

        $crawler->activate();

        $cc = new CrawlController();
        $cc->setupCrawler($crawler->id);
        $cc->doneCrawler();

        $check = $cc->canCrawlerRunAfterPeriod();

        $this->assertTrue($check['retry_in'] == 5);
        $this->assertFalse($check['status']);
    }



    /** @test */
    public function it_deletes_old_logs_after_1_week()
    {
        $crawler = $this->getCrawlerData();

        $crawler->time_between = 5;

        $crawler->save();

        $crawler->activate();

        $cc = new CrawlController();
        $cc->setupCrawler($crawler->id);
        $cc->doneCrawler();

        $knownDate = Carbon::now()->addSeconds(config('laravel-job-handler.clear-log-after-seconds', 60*60*24*7));
        Carbon::setTestNow($knownDate);

        $cc->doneCrawler();

        $count_before['crawler_statuses'] = CrawlerStatus::count();
        $count_before['crawler_status_logs'] = CrawlerStatusLogs::count();

        $clc = new CrawlLogController();
        $clc->clearAllLogs();

        $count_after['crawler_statuses'] = CrawlerStatus::count();
        $count_after['crawler_status_logs'] = CrawlerStatusLogs::count();

        $this->assertTrue($count_after['crawler_statuses'] == 1);
        $this->assertTrue(CrawlerStatusLogs::whereNotIn('status_id', CrawlerStatus::get(['id']))->count() == 0);

        //check if there are not results
        foreach(['crawler_statuses', 'crawler_status_logs'] as $key) {
            $this->assertTrue($count_before[$key] > $count_after[$key], 'Failed: '.$key.' ('.$count_before[$key].' > '.$count_after[$key].')');
        }
    }
}
