<?php

namespace Famdirksen\LaravelJobHandler\Models;

use Famdirksen\LaravelJobHandler\Exceptions\CrawlerAlreadyActivatedException;
use Famdirksen\LaravelJobHandler\Exceptions\CrawlerAlreadyDeactivatedException;
use Illuminate\Database\Eloquent\Model;

class Crawlers extends Model
{
    protected $table = 'crawlers';

    protected $fillable = [
       'name',
       'description'
    ];



    public function runs()
    {
        return $this->hasMany('Famdirksen\LaravelJobHandler\Models\CrawlerStatus', 'crawler_id', 'id');
    }
    public function last_run()
    {
        return $this->hasOne('Famdirksen\LaravelJobHandler\Models\CrawlerStatus', 'crawler_id', 'id')
            ->orderBy('created_at', 'DESC');
    }


    public function getLastRunnedAtAttribute()
    {
        if ($this->last_run)
        {
            return $this->last_run->created_at;
        }

        return null;
    }

    public function activate()
    {
        if ($this->enabled) {
            throw new CrawlerAlreadyActivatedException();
        }

        $this->enabled = true;

        return $this->save();
    }
    public function deactivate()
    {
        if (!$this->enabled) {
            throw new CrawlerAlreadyDeactivatedException();
        }

        $this->enabled = false;

        return $this->save();
    }
}
