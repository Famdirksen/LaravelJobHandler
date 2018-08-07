<?php

namespace Famdirksen\LaravelJobHandler\Models;

use Illuminate\Database\Eloquent\Model;

class CrawlerStatus extends Model
{
    protected $table = 'crawler_statuses';

    public function logs()
    {
        return $this->hasMany('Famdirksen\LaravelJobHandler\Models\CrawlerStatusLogs', 'status_id', 'id');
    }


    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($item) {
            $item->logs()
                ->delete();
        });
    }
}
