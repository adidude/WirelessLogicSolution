#!/usr/bin/env php
<?php

require dirname(__DIR__)."\\vendor\autoload.php";
use PHPUnit\Framework\TestCase;
use Tools\Scraper;
use Goutte\Client;

class ScraperTest extends TestCase
{   
    public function setUp() : void
    {
        $client = new Client();
        $this->crawler = $client->request("GET","https://videx.comesconnected.com");
        
        $this->scraper = new Scraper();
    }

    public function tearDown() : void
    {
        unset($this->crawler);
        unset($this->scraper);
    }

    public function testPriceFetch()
    {
        $answer = [72,120,192,66,108,174];
        $output = $this->scraper->getPrices($this->crawler);
        $this->assertNotNull($output);
        $this->assertCount(6, $output, "Expected 6 packages");
        $this->assertContainsOnly("int",$output,"Expecting to recieve array with integers");
        
        #Check if all prices are positive
        for ($i=0; $i < 6; $i++) { 
            $this->assertGreaterThan(0,$output[$i]);
        }

        #Potentially unwanted test
        $this->assertEquals($answer, $output, "The annual prices are [72,120,192,66,108,174] As long as prices have not changed");
    }
}
?>