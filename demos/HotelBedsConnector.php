<?php
/*
 * Clase HotelBedsConnector
 * 
 * 
 */
class HotelBedsConnector
{
    public function generar_XSignature($apiKey, $secretKey){
        $xsignature = hash("sha256", $apiKey.$secretKey.time());
        return $xsignature;
    }
    
    /*
     * Metodo que envia el Request al servidor de HB y obtiene un listado de 
     * Paises con su codigo y nombre.
     * $pais: string; Codigo de Pais.
     */
    public function getListadoPaises(){
        $isoCode = 1;
        $description = 0;
        $url = $GLOBALS["urlHotelContentApi"].'/1.0/locations/countries?fields=description&language=ENG&from=1&to=250';   
        $data = $this->sendRequestHB($url);    
        return $data;
    }
    /*
     * Metodo que envia el Request al servidor de HB y obtiene un listado de 
     * Destinos por Codigo de PAIS.
     * $pais: string; Codigo de Pais.
     */
    public function getListadoDestinosporPais($pais){
            $url  = $GLOBALS["urlHotelContentApi"].'/1.0/locations/destinations?fields=name%2CdestinationName%2Ccountry&countryCodes='.$pais.'&language=ENG&from=1&to=20';
            $data = $this->sendRequestHB($url);  
            return $data;
    }
    /*
     * Metodo que obtiene el estado del Servidor de HB
     */
    public function getEstadoHB(){
        $url = $GLOBAL["urlHotelApi"]. '/1.0/status';
        $data = $this->sendRequestHB($url);
        return $data;
    }
    /*
     * Metodo que obtiene el listado de Destinos por nombre.
     * Averiguar si Funciona en produccion, esta muy buena.
     */
    public function getDestinoporNombre($localidad){
        $url = $GLOBALS["urlHotelContentApi"].
                '/1.0/locations/destination?query=*'.
                $localidad.
                '*&language=ENG';
        $data = $this->sendRequestHB($url);
        return $data;
    }
    
    public function getListadoHotelesporGeoposicion($latitud,$longitud,$radio){
        $url = $GLOBALS["urlHotelApi"].'/1.0/hotels/geolocation?latitude='.$latitud.'&longitude='.$longitud.'&radius='.$radio.'&unit=km';
        $data = $this->sendRequestHB($url);
        return $data;    
    }

    public function getListadoHotelesporZona($zona){
        //http://testapi.hotelbeds.com/hotel-content-api/search/Destination?query=*Rosario*&language=ENG
        //$url='http://testapi.hotelbeds.com/hotel-content-api/search/Zone?query=*Rosario*&language=ENG';
        $url = $GLOBALS["urlHotelApi"].'/1.0/hotels/destination/?Zone='.$zona;
        $data = $this->sendRequestHB($url);
        return $data;   
    }
    
    public function getListadoHotelesporCodigoLocalidad($personas, $codigo,$localizacion){
        
        $xml = $this->generar_availability_xml($personas, 1,2,0,'2016-05-01','2016-05-10',$codigo, $localizacion);        
        $url = $GLOBALS["urlHotelApi"].'/1.0/hotels';        
        $data = $this->sendRequestPOSTHB($url,$xml);
        return $data;   
    }
    /*
     * Funcion que genera el xml parcial para enviar posicion y obtener datos 
     * respecto a la misma.Basicamente es un filtro por ubicacion
     */
    function generar_geolocalizacion_xmlparcial($localizacion){
        //inicializo $xml_geo_content     
        $xml_geo_content ='';
        //armo el xmlparcial "$xml_geo_content" recorriendo el array
        foreach ($localizacion as $key =>$valor) 
        {   	            
            $xml_geo_content = $xml_geo_content .' '.$key.'="'.$valor.'" ';
        }
        $xml_geo =trim('<geolocation '.$xml_geo_content.'/>');
        //debe devolver algo asi:
        //<geolocation longitude="2.646633999999949" latitude="39.57119" radius="20" unit="km"/>
         return $xml_geo;
    }
    
    /*
     * Metodo que permite armar un xml parcial de los ocupantes y habitaciones
     */
    function generar_ocupantes_xmlparcial($personas,$habitaciones=1,$cantidad_adultos=1,$cantidad_hijos=0){
            //aca recorre un array $personas que contendra
            //el tipo y edad de las personas que viajan.
            //type string ,valido "AD" o "CH"
            //age integer
            
            //inicializo xml_pax     
            $xml_pax ='';
            //armo el xmlparcial "pax" recorriendo el array
            for ($i = 0; $i < count($personas); $i++)
            {                  
                $xml_pax = $xml_pax . '<pax type="'.$personas[$i]['type'].'" age="'.$personas[$i]['age'].'"/>';
            }
            //Concateno paxes con pax
            $xml_paxes ='<paxes>'.$xml_pax.'</paxes>';
            //Concateno paxes con occupancies
            $xml_occupancies =   '<occupancies>
                                        <occupancy rooms="'.$habitaciones.'" adults="'.$cantidad_adultos.'" children="'.$cantidad_hijos.'">
                                            '.$xml_paxes.'
                                        </occupancy>
                                  </occupancies>';

            return $xml_occupancies;
    }
            
    function generar_availability_xml($personas,$cant_habitaciones, $cant_adultos, $cant_hijos, $fecha_desde, $fecha_hasta, $codigo, $localizacion){
        $header = trim('<availabilityRQ xmlns="http://www.hotelbeds.com/schemas/messages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">');
        $xml = $header;
        //agrego la estadia al xml si vienen las fechas
        //el formato de las fechas es aaaa-mm-dd
        if((!empty($fecha_desde)) && (!empty($fecha_hasta))){
            $estadia =trim('<stay checkIn="'.$fecha_desde.'" checkOut="'.$fecha_hasta.'"/>');
            $xml = $xml.$estadia;
        }
        //agrego los ocupantes
        if((!empty($personas)) && ($cant_habitaciones>=1) && ($cant_adultos>=1) && ($cant_hijos>=0)){
            $ocupantes = $this->generar_ocupantes_xmlparcial($personas,$cant_habitaciones,$cant_adultos,$cant_hijos);
            $xml = $xml.$ocupantes;
        }
        //agrego el destino al xml si viene el codigo de destino.
        //El formato de Codigo de destino es Alfanumerico sin espacios.Ej RO6 es Rosario
        if(!empty($codigo)&& ($localizacion==FALSE)){
            $destino = '<destination code="'.$codigo.'"/>';
            $xml= $xml.$destino;            
        }
        if(!empty($localizacion)&& ($codigo==FALSE)){
            $geoposicion = $this->generar_geolocalizacion_xmlparcial($localizacion);                    
            $xml = $xml.$geoposicion;     
        }
        $footer = '</availabilityRQ>';    
        $xml = $xml . $footer;
        //Devuelvo el xml armado.
        return $xml;
    }
    function generar_booking_xml($personas,$cant_habitaciones, $cant_adultos, $cant_hijos, $fecha_desde, $fecha_hasta, $codigo, $localizacion){
        $header = trim('<bookingRQ xmlns="http://www.hotelbeds.com/schemas/messages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">');
        $xml = $header;
	$xml_holder='<holder name="HOLDER NAME" surname="HOLDER SURNAME"/>';
	$xml_client_reference='<clientReference>My booking reference</clientReference>';
	$xml_rooms='<rooms>
		<room rateKey="99658|TPL-E10|OP-TODOS|1|ST|BB-E10||1~2~1|20150728#1590638725">
			<paxes>
				<pax roomId="1" type="AD" age="30" name="ADULT NAME" surname="ADULT SURNAAME"/>
				<pax roomId="1" type="AD" age="30" name="ADULT NAME" surname="ADULT SURNAME"/>
				<pax roomId="1" type="CH" age="8" name="CHILDREN NAME" surname="CHILDREN SURNAME"/>
			</paxes>
		</room>
	</rooms>';
	$xml_payment='<paymentData>
		<paymentCard>
			<cardHolderName>AUTHORISED</cardHolderName>
			<cardType>VI</cardType>
			<cardNumber>4444333322221111</cardNumber>
			<expiryDate>0620</expiryDate>
			<cardCVC>123</cardCVC>
		</paymentCard>
		<contactData>
			<email>integration@test.com</email>
			<phoneNumber>654654654</phoneNumber>
		</contactData>
	</paymentData>';

        $footer = '</bookingRQ>';    
        $xml = $xml . $footer;
        //Devuelvo el xml armado.
        return $xml;
    }
    /*
     * Metodo que envia el Request al servidor de HB
     * $url: string; url del webservice.
     */
    function sendRequestHB($url){
            //Inicializo una insancia de Curl
            $curl = curl_init();
            //Configuro el type, el mismo determinara que tipo de datos va
            //  a devolver el response.
            ////Puede ser json o xml.
            $type = 'application/xml';
            $method  ='GET';
            //armo la cabecera 
            $header[] = "Accept: ".$type;
            $header[] = "Accept-Encoding: gzip, deflate, sdch";
            $header[] = "Content-Type: ".$type;
            $header[] = "Accept-Charset: utf-8";
            $header[]="Api-Key :". $GLOBALS["apiKey"];
            $header[]="X-Signature :".$GLOBALS["xsignature"];
            $header[] = "Accept-Language: es-419,es;q=0.8";

            // Setup CURL
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_HEADER, true);  
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_ENCODING, "gzip");
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($curl, CURLINFO_HEADER_OUT, false);
        
            $result = curl_exec($curl);
            //$request = curl_getinfo($curl, CURLINFO_HEADER_OUT); 
            curl_close($curl);
            //$log = array($url.'|'. $request.'|'. $result.'|'. $method.'|');
            //print_r($header);
            return $result;        
    }
    
    /*
     * Metodo que envia el Request al servidor de HB
     * $url: string; url del webservice.
     */
    function sendRequestPostHB($url,$xml){
            //Inicializo una instancia de Curl
            $curl = curl_init();
            //Configuro el Type,  el mismo determinara que tipo de datos va  a devolver el response.
            ////Puede ser json o xml.
            $type = 'application/xml';
            $method  ='POST';
            //armo la cabecera 
            $header[] = "Accept: ".$type;
            $header[] = "Accept-Encoding: gzip";
            $header[] = "Content-Type: ".$type;
            $header[] = "Accept-Charset: utf-8";
            $header[]="Api-Key :". $GLOBALS["apiKey"];
            $header[]="X-Signature :".$GLOBALS["xsignature"];
            $header[] = "Accept-Language: es-419,es;q=0.8";
            
            // Setup CURL
            curl_setopt($curl, CURLOPT_POSTFIELDS, trim($xml));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            //curl_setopt($curl, CURLOPT_HEADER, true);  
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_ENCODING, "gzip");
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            //curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        
            $result = curl_exec($curl);
            //$request = curl_getinfo($curl, CURLINFO_HEADER_OUT); 
            curl_close($curl);
            //$log = array($url.'|'. $request.'|'. $result.'|'. $method.'|');
            //$this->guardar_xml($result) ;
            return $result;        
    }
    /*
     * Funcion que guardara en un archivo xml el string que se pase.
     */
    function guardar_xml($xml){
        $fp = fopen('data.xml', 'w');
        fwrite($fp, strval($xml));
        fclose($fp);
    }
    
    
    /*
     * Funcion que permitie obtener contenido del atributo de archivo xml.
     */
    public function obtener_atributo_xml($archivo,$nombre,$atributo){
        $data = array();
        $i=0;
        $xmlReader = new XMLReader();
        $xmlReader->open($archivo);
        while($xmlReader->read()) {
                // check to ensure nodeType is an Element not attribute or #Text  
            if($xmlReader->nodeType == XMLReader::ELEMENT) {
                if($xmlReader->localName == $nombre) {
                     $data[$i] = $xmlReader->getAttribute($atributo);
                     $i =$i+1;
                }
            }
        }
        return $data;
    }
}
?>
