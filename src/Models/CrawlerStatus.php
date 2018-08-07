<?php

namespace Famdirksen\LaravelJobHandler\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrawlerStatus extends Model
{
   use SoftDeletes;

   protected $table = 'crawler_statuses';
}
