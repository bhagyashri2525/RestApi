<?php

namespace App\Services\Event;
use Bitly;

class BitlyService
{

    public function shortenUrl($url){
        return Bitly::getUrl($url);
    }

}