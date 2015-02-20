<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 2/20/2015
 * Time: 10:17 AM
 */
class TransitLinkController extends Controller
{
    public function getTransitLinkDistance()
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 300);
        //set POST variables

        $url = 'http://www.transitlink.com.sg/eservice/eguide/service_route.php?service=';


        $cUrls = array();

        //create the multi handler
        $multiHandler = curl_multi_init();

        //$services = BusService::where('bus_service_no', '=', 10)->get();
        $services = BusService::all();

        foreach ($services as $service) {
            if (is_numeric($service->bus_service_no)) {
                $serviceNumber = $service->bus_service_no;
                //open connection
                $options = array(
                    CURLOPT_CUSTOMREQUEST => "GET", //set request type post or get
                    CURLOPT_POST => false,        //set to GET
                    CURLOPT_RETURNTRANSFER => true,     // return web page
                    CURLOPT_HEADER => false,    // don't return headers
                    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                    CURLOPT_ENCODING => "",       // handle all encodings
                    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0", // who am i
                    CURLOPT_AUTOREFERER => true,     // set referer on redirect
                    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                    CURLOPT_TIMEOUT => 120,      // timeout on response
                    CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
                    CURLOPT_SSL_VERIFYPEER => false,
                    //CURLOPT_COOKIEFILE => public_path() . "/cookies.txt", //set cookie file
                    //CURLOPT_COOKIEJAR => public_path() . "/cookies.txt", //set cookie jar
                    //CURLOPT_COOKIE => 'ASP.NET_SessionId=' . $this->sessionId . '; path=/;',
                    //CURLOPT_POST => 1,
                    //CURLOPT_POSTFIELDS => 'txtbox=' . $postalCode,
                );

                $urlQuery = $url . $serviceNumber;

                $ch = curl_init($urlQuery);
                curl_setopt_array($ch, $options);
                curl_multi_add_handle($multiHandler, $ch);

                $chArray = array('ch' => $ch, 'route' => $service->bus_service_no);
                $cUrls[] = $chArray;
            }
        }

        $running = null;
        //execute the handles
        do {
            $status = curl_multi_exec($multiHandler, $running);
            // Check for errors
            if ($status > 0) {
                // Display error message
                echo "ERROR!\n " . curl_multi_strerror($status);
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $running);

        $records = array();
        foreach ($cUrls as $ch) {
            $records[] = $this->extractTransitLinkData(curl_multi_getcontent($ch['ch']), $ch['route']);
            curl_multi_remove_handle($multiHandler, $ch['ch']);
        }

        curl_multi_close($multiHandler);

        $param['records'] = $records;

        return View::make('result', $param);
    }


    public function extractTransitLinkData($html, $route)
    {
        $record = array('route' => $route, 'entries' => array());

        //get the first route
        $dir1Header = "<form method=\"post\" action=\"fare_info.php\" name=\"calculateFareDir1\" id=\"calculateFareDir1\" onSubmit=\"return verifyRoute(1);\">";
        $dir1HeaderStart = stripos($html, $dir1Header);
        $dir1EndTag = "</form>";
        $dir1EndPos = stripos($html, $dir1EndTag, $dir1HeaderStart);

        //get all <tr>
        if ($dir1HeaderStart) {
            $dir1Form = substr($html, $dir1HeaderStart, $dir1EndPos - $dir1HeaderStart);

            $trTag = "</tr>";
            $trTagStart = strlen($dir1Header);
            $trTagEnd = 1;

            while ($trTagEnd != false) {
                $trTagEnd = stripos($dir1Form, $trTag, $trTagStart + strlen($trTag));
                $trTagStr = substr($dir1Form, $trTagStart + strlen($trTag), $trTagEnd - $trTagStart - strlen($trTag));

                if (substr(trim($trTagStr), 0, 4) == "<tr>"){
                    $trTagStr = trim(substr(trim($trTagStr), 4));
                }


                if (strlen($trTagStr)){
                    $distanceTr = new DOMDocument;
                    //echo $trTagStr. "BREAK<br>";

                    try {
                        libxml_use_internal_errors(true);
                        $distanceTr->loadHTML($trTagStr);
                        libxml_use_internal_errors(false);

                    } catch (ErrorException $e) {
                        echo "dir1HeaderStart<br>" . $e->getMessage() . "<br>";
                        die ($route . $html);

                    }

                    $items = $distanceTr->getElementsByTagName('td');

                    try {
                        $record['entries'][] = $this->recordData1($items, $route);

                    } catch (ErrorException $e) {

                    }

                }

                $trTagStart = $trTagEnd;
            }


        }




        //get the second route if any
        $dir2Header = "<form method=POST name=\"calculateFareDir2\" action=\"fare_info.php\" onSubmit=\"return verifyRoute(2);\">";
        $dir2HeaderStart = stripos($html, $dir2Header);
        $dir2EndTag = "</form>";
        $dir2EndPos = stripos($html, $dir2EndTag, $dir2HeaderStart);

        if ($dir2HeaderStart) {
            $dir2Form = substr($html, $dir2HeaderStart, $dir2EndPos - $dir2HeaderStart);

            $dir2FormHTML = new DOMDocument;
            try {
                libxml_use_internal_errors(true);
                $dir2FormHTML->loadHTML($dir2Form);
                libxml_use_internal_errors(false);

            } catch (ErrorException $e) {
                die ($route . $html);

            }

            //get all <tr>
            if (empty($dir2FormHTML)) {
                return $record;
            } else {
                $items = $dir2FormHTML->getElementsByTagName('tr');
            }


            for ($i = 1; $i < $items->length; $i++) {
                try {
                    $record['entries'][] = $this->recordData2($items->item($i)->childNodes, $route);
                } catch (ErrorException $e) {

                }

            }

        }


        return $record;

    }

    private function recordData1($nodes, $route){
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = trim($node->nodeValue);
            $data[] = $nodeValue;

        }

        if (!empty($data) && count($data) > 2 && is_numeric($data[0])) {

            $busStopDistance = new BusStopDistance;
            $busStopDistance->bus_service_no = $route;
            $busStopDistance->bus_stop_id = substr($data[1], -5);
            if (is_numeric($data[0])) {
                $busStopDistance->distance_km = $data[0];
            }
            $busStopDistance->direction = 1;
            $busStopDistance->save();
            return $data[0];
        } else {
            return null;
        }
    }

    private function recordData2($nodes, $route)
    {
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = trim($node->nodeValue);
            $data[] = $nodeValue;
            /*
            if (!empty($nodeValue)) {
                $data[] = $nodeValue;
            } */
        }

        //echo var_dump($data);
        if (!empty($data) && count($data) > 2 && is_numeric($data[0])) {

            $busStopDistance = new BusStopDistance;
            $busStopDistance->bus_service_no = $route;
            $busStopDistance->bus_stop_id = substr($data[2], -5);
            if (is_numeric($data[0])) {
                $busStopDistance->distance_km = $data[0];
            }
            $busStopDistance->direction = 2;
            $busStopDistance->save();
            return $data[0];
        } else {
            return null;
        }

    }


}