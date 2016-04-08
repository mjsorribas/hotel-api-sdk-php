<?php
require __DIR__ .'/vendor/autoload.php';

use hotelbeds\hotel_api_sdk\HotelApiClient;
use hotelbeds\hotel_api_sdk\model\Destination;
use hotelbeds\hotel_api_sdk\model\Occupancy;
use hotelbeds\hotel_api_sdk\model\Pax;
use hotelbeds\hotel_api_sdk\model\Rate;
use hotelbeds\hotel_api_sdk\model\Stay;
use hotelbeds\hotel_api_sdk\types\ApiVersion;
use hotelbeds\hotel_api_sdk\types\ApiVersions;
use hotelbeds\hotel_api_sdk\messages\AvailabilityRS;

$reader = new Zend\Config\Reader\Ini();
$config   = $reader->fromFile(__DIR__.'/HotelApiClient.ini');
$cfgApi = $config["apiclient"];

$apiClient = new HotelApiClient($cfgApi["url"],
                                $cfgApi["apikey"],
                                $cfgApi["sharedsecret"],
                                new ApiVersion(ApiVersions::V1_0),
                                $cfgApi["timeout"]);

$rqData = new \hotelbeds\hotel_api_sdk\helpers\Availability();
$rqData->stay = new Stay(DateTime::createFromFormat("Y-m-d", "2016-04-15"),
                         DateTime::createFromFormat("Y-m-d", "2016-04-20"));

$rqData->destination = new Destination("RO6");
$occupancy = new Occupancy();
$occupancy->adults = 2;
$occupancy->children = 0;
$occupancy->rooms = 1;

//$occupancy->paxes = [ new Pax(Pax::AD, 30, "Mike", "Doe"), new Pax(Pax::AD, 27, "Jane", "Doe"), new Pax(Pax::CH, 8, "Mack", "Doe") ];
$occupancy->paxes = [ new Pax(Pax::AD, 30, "Mike", "Doe"), new Pax(Pax::AD, 27, "Jane", "Doe")];

$rqData->occupancies = [ $occupancy ];

try {
    $availRS = $apiClient->availability($rqData);
} catch (\hotelbeds\hotel_api_sdk\types\HotelSDKException $e) {
    //$auditData = $e->getAuditData();
    echo 'Error al obtener datos';
    //error_log( $e->getMessage() );
    //error_log( "Audit remote data = ".json_encode($auditData->toArray()));
    exit();
} catch (Exception $e) {
    error_log( $e->getMessage() );
    exit();
}

// Check availability is empty or not!
if (!$availRS->isEmpty()) {
   print_r($availRS->hotels->toArray());
} else {
   echo "There are no results!";
}

?>