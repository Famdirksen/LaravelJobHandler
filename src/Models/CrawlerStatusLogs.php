<?php

namespace Famdirksen\LaravelJobHandler\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrawlerStatusLogs extends Model
{
    protected $table = 'crawler_status_logs';
}
