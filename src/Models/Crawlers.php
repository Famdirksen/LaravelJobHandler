<?php

namespace Famdirksen\LaravelJobHandler\Models;

use Famdirksen\LaravelJobHandler\Exceptions\CrawlerAlreadyActivatedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Crawlers extends Model
{
   use SoftDeletes;

   protected $fillable = [
       'name',
       'description'
   ];


   public function activate() {
       if($this->enabled) {
           throw new CrawlerAlreadyActivatedException();
       }

       $this->enabled = true;

       return $this->save();
   }
   public function deactivate() {
       if(!$this->enabled) {
           throw new CrawlerAlreadyActivatedException();
       }

       $this->enabled = false;

       return $this->save();
   }
}
