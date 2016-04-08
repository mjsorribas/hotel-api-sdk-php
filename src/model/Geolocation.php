<?php
/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 11/8/2015
 * Time: 11:45 PM
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
            "secondaryLatitude" => "float",
            "secondaryLongitude" => "float"
        ];
    }
}