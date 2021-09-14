#!/usr/bin/env php
<?php
/**
 * This file scrapes package data from the Videx website
 * 
 * Aditya Kumar Menon <menon.or.adithya@gmail.com>
 */
namespace Tools;
require dirname(__DIR__)."\\vendor\autoload.php";

use Goutte\Client;
use \stdClass;

class Scraper
{
    /**
     * Constructor retrieves the data from videx, this allows the data to be accessed from anywhere.
     */
    public function __construct()
    {
        $client = new Client();
        $this->data = $client->request("GET","https://videx.comesconnected.com");
    }

    /**
     * Grabs all packages from Videx
     * 
     * @return json[]
     */
    function grabPackages()
    {
        $client = new Client();

        #Data is grabbed and stored for sorting data.
        $crawl = $client->request('GET','https://videx.comesconnected.com');

        #All data is collected in seperate arrays for combining
        $options = $this->getData(".header.dark-bg");
        $descriptions = $this->getData(".package-name");
        $discounts = $this->getData(".package-price p");
        $annualPrices = $this->getPrices();
        
        return $this->stitchPackages($options, $descriptions, $annualPrices, $discounts);
    }

    /**
     * Retrieves the prices the various packages available
     * 
     * @return int[]
     */
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


    /**
     * Grabs data from the website and return an array string of results based on query.
     * 
     * @param string $query This is a CSS style selector
     * 
     * @return string[]
     */
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

    /**
     * Creates a json object from the variables it is provided.
     * 
     * @param stdClass  $package        The stdClass that serves as a temporary store prior to json conversion
     * @param string    $option         The Option title
     * @param string    $description    The description of the package
     * @param int       $price          The annual price of a package
     * @param string    $discount       The discount obtained in Pound Sterling
     * 
     * @return json
     */
    public function createJSON($package, $option, $description, $price, $discount)
    {
        # Fills a stdObj and turns it into a json for output.
        # TODO: $option and $description have incorrect numeric values for considering monthly plans as annual plans
        $package->optionTitle = $option;
        $package->description = $description;
        $package->price = $price;
        $package->discount = $discount;
        return json_encode($package, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Combines arrays of data into an array of json objects
     * 
     * @param string[]  $options        The packages available
     * @param string[]  $descriptions   The descriptions of the packages
     * @param int[]     $annualPrices   The prices of the packages
     * @param string[]  $discounts      The discounts for each package (if applicable)
     * 
     * @return json[]
     */
    public function stitchPackages($options, $descriptions, $annualPrices, $discounts)
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
                $jsonPacker[] = $this->createJSON($pack, $options[$i], $descriptions[$i], $annualPrices[$i], '');
            }
            else
            {
                $jsonPacker[] = $this->createJSON($pack, $options[$i], $descriptions[$i], $annualPrices[$i], $discounts[$i - $columns]);
            }
        }
        return $jsonPacker;
    }

    /**
     * Sorts a json array by descending price using a bubblesort algorithm
     *
     * @param json[] An array of json objects
     */
    public function sortByPrice($jsonArray)
    {
        $arrSize = count($jsonArray);

            for($i = 0; $i < $arrSize; $i++) 
            {
                for ($j = 0; $j < $arrSize - $i - 1; $j++) 
                {
                    $curr = json_decode($jsonArray[$j]);
                    $next = json_decode($jsonArray[$j+1]);
                    // Swap if the element found is lower than the next element
                    if ($next->price > $curr->price)
                    {
                        $temp = $jsonArray[$j+1];
                        $jsonArray[$j+1] = $jsonArray[$j];
                        $jsonArray[$j] = $temp;
                    }
                }
            }
        return $jsonArray;
    }
}
?>