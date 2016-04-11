<?php
/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 11/8/2015
 * Time: 11:45 PM
 * Fixed by Maximiliano Jose Sorribas
 * Date: 08/04/2016
 * Changes: Se agrego unit y se paso a double los floats fields para que valide bien el tipo de dato.
 */

namespace hotelbeds\hotel_api_sdk\model;

class Geolocation extends ApiModel
{
    CONST KM='km';
    CONST M='m';

    public function __construct()
    {
        $this->validFields = [
            "longitude" => "double",
            "latitude" => "double",
            "radius" => "integer",
            "unit" => "string",
            "secondaryLatitude" => "double",
            "secondaryLongitude" => "doluble"
        ];
    }
}