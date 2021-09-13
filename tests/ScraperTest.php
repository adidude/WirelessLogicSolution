#!/usr/bin/env php
<?php

require dirname(__DIR__)."\\vendor\autoload.php";
use PHPUnit\Framework\TestCase;
use Tools\Scraper;
use Goutte\Client;

class ScraperTest extends TestCase
{   
    public function testPriceFetch()
    {
        $client = new Client();
        $crawler = $client->request("GET","https://videx.comesconnected.com");
        $answer = [72,120,192,66,108,174];
        $scraper = new Scraper();
        $compute = $scraper->getPrices($crawler);
        $this->assertEquals($answer, $compute, "The annual prices are [72,120,192,66,108,174]");
    }
}
?>