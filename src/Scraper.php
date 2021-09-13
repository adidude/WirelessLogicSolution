#!/usr/bin/env php
<?php
namespace Tools;
require "vendor/autoload.php";

class Scraper
{
    public function getPrices($data)
    {
        # TODO: Using the columns variable to tell when annual prices begin to be read. Ideally this would be dynamically calculated.
        $columns = 3;
        $tracker = 0;
        $prices = [];
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
        return $prices;
    }
}
?>