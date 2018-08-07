<?php

namespace Famdirksen\LaravelJobHandler\Tests\Unit;

use Famdirksen\LaravelJobHandler\Exceptions\CrawlerAlreadyActivatedException;
use Famdirksen\LaravelJobHandler\Http\Controllers\CrawlController;
use Famdirksen\LaravelJobHandler\LaravelJobHandlerServiceProvider;
use Famdirksen\LaravelJobHandler\Models\Crawlers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as TestCase;

class CrawlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        $this->refreshDatabase();

        $this->loadMigrationsFrom(__DIR__ . '/../../src/migrations');
    }
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
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
        $this->expectException(CrawlerAlreadyActivatedException::class);

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
}
