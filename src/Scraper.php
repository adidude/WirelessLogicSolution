#!/usr/bin/env php
<?php

require "vendor/autoload.php";

use Goutte\Client;
$client = new Client();
$crawler = $client->request('GET','https://videx.comesconnected.com');

class Scraper
{
    public function getPrices($data, &$prices)
    {
        # TODO: Using the columns variable to tell when annual prices begin to be read. Ideally this would be dynamically calculated.
        $columns = 3;
        $tracker = 0;
        $data->filter('.price-big')->each(function ($val) use(&$tracker, &$columns, &$prices)
        {
            # Converting string to int to allow for price sorting operation
            $valStr = $val->text();
            $intVal = (int)substr($valStr, 2, strlen($valStr) - 1);
            # The first few values are monthly, this calculates the annual rate.
            if ($tracker < $columns)
            {
                $prices[] = $intVal * 12;
                $tracker++;
            }
            else
            {
                $prices[] = $intVal;
            }
        });
    }
}

$scrape = new Scraper();
$prices = [];
$scrape->getPrices($crawler, $prices);

echo print_r($prices);

?>