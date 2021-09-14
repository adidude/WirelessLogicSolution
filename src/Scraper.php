#!/usr/bin/env php
<?php
namespace Tools;
require "vendor/autoload.php";

use Goutte\Client;
use \stdClass;

class Scraper
{
    public function __construct()
    {
        $client = new Client();
        $this->data = $client->request("GET","https://videx.comesconnected.com");
    }

    public function getPrices()
    {
        # TODO: Using the columns variable to tell when annual prices begin to be read. Ideally this would be dynamically calculated.
        $columns = 3;
        $tracker = 0;
        $prices = [];
        $this->data->filter('.price-big')->each(function ($val) use(&$tracker, &$columns, &$prices)
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
        return $prices;
    }

    public function getData($query)
    {
        $arrayToFill = [];
        $this->data->filter($query)->each(function ($val) use(&$arrayToFill)
        {
            # Getting the html response to add space between line breaks
            $hyperText = $val->html();
            if(str_contains($hyperText, "<br>"))
            {
                $hyperText = str_replace("<br>", " ", $hyperText);
                $arrayToFill[] = strip_tags($hyperText);
            }
            else
            {
                $arrayToFill[] = $val->text();
            }
            
        });
        return $arrayToFill;
    }

    function createJSON($package, $option, $description, $price, $discount)
    {
        # Fills a stdObj and turns it into a json for output.
        $package->optionTitle = $option;
        $package->description = $description;
        $package->price = $price;
        $package->discount = $discount;
        return json_encode($package, JSON_UNESCAPED_UNICODE);
    }

    function stitchPackages($options, $descriptions, $annualPrices, $discounts)
    {
        # TODO: Columns need to determined programatically
        $columns = 3;
        $noOfPackages = count($options);
        $jsonPacker = [];
        for ($i=0; $i < $noOfPackages; $i++)
        {
            $pack = new stdClass();
            if($i < $columns)
            {
                # Monthly plans have no discount and are initialised empty
                $jsonPacker[] = createJSON($pack, $options[$i], $descriptions[$i], $annualPrices[$i], '');
            }
            else
            {
                $jsonPacker[] = createJSON($pack, $options[$i], $descriptions[$i], $annualPrices[$i], $discounts[$i - $columns]);
            }
        }
        return $jsonPacker;
    }
}
?>