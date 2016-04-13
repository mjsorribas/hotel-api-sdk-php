<?php
//cargo los archivos requeridos
require('HotelBedsConnector.php');
require('HotelBedsApiConf.php');

//Inicializo mi Clase
$myquery = new HotelBedsConnector();
//Genero el XSignature para la cabecera http del request
// Esto se debe hacer siempre luego de inicializar la clase 
// para que se pueda guardar la key de autenticacion en memoria.
$xsignature = trim($myquery->generar_XSignature($apiKey, $secretKey));


//echo $myquery->getListadoDestinosporPais('AR');
//echo $myquery->getEstadoHB();
//echo getListadoPaises();//Este metodo se probo y funciona correctamente.
//test 
$ubicacion = array('longitude' => floatval('-60.64836'),'latitude' =>floatval('-32.937182'),'radius' => 2,'unit' => 'km');
$personas=array(array('type' => 'AD','age' => 28),array('type' => 'AD','age' => 32));
//CUN es el codigo de Cancun , RO6 Codigo de Rosario
//$xml = trim($myquery->getListadoHotelesporCodigoLocalidad($personas,false, $ubicacion));
//$myquery->guardar_xml($myquery->getListadoHotelesporCodigoLocalidad($personas,false, $ubicacion));

$listado = $myquery->obtener_atributo_xml('data.xml', 'rate', 'rateKey');
        
print_r($listado);

?>
