<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 11/19/2014
 * Time: 3:51 PM
 */
class BusController extends BaseController
{

    private $direction = "2";
    private $sessionId = 'b4m3oc55whw2s155nwedfw45';


    public function getDistance()
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 300);
        //set POST variables

        $url = 'http://www.sbstransit.com.sg/journeyplan/RouteInformation.aspx?';
        //url body: qdirect=1&qservno=199
        $urlSuffix = '&qpoint=NO%20LOOP&dispno=10&qstart=TAMPINES%20INT&qend=TAMPINES%20INT';


        $cUrls = array();

        //create the multi handler
        $multiHandler = curl_multi_init();

        $services = BusService::all();

        foreach ($services as $service) {
            if (is_numeric($service->bus_service_no)) {
                $serviceNumber = str_pad($service->bus_service_no, 3, '0', STR_PAD_LEFT);
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
                    CURLOPT_COOKIE => 'ASP.NET_SessionId=' . $this->sessionId . '; path=/;',
                    //CURLOPT_POST => 1,
                    //CURLOPT_POSTFIELDS => 'txtbox=' . $postalCode,
                );

                $urlQuery = $url . "qdirect=" . $this->direction . "&qservno=" . $serviceNumber . $urlSuffix;

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
            $records[] = $this->extractData(curl_multi_getcontent($ch['ch']), $ch['route']);
            curl_multi_remove_handle($multiHandler, $ch['ch']);
        }

        curl_multi_close($multiHandler);

        $param['records'] = $records;

        return View::make('result', $param);


    }


    /**
     * @param $html
     * @param $route
     * @return array
     */
    private function extractData($html, $route)
    {

        $record = array('route' => $route, 'entries' => array());

        $HTMLRoot = new DOMDocument;
        try {
            libxml_use_internal_errors(true);
            $HTMLRoot->loadHTML($html);
            libxml_use_internal_errors(false);

        } catch (ErrorException $e) {
            die ($route . $html);

        }


        //get <table id='dgResult'>
        $TableNode = $HTMLRoot->getElementById('dgResult');
        //get all <tr>
        if (empty($TableNode)) {
            return $record;
        } else {
            $items = $TableNode->getElementsByTagName('tr');
        }


        //$stopIds = $this->readStopIds($route);

        $entries = array();

        for ($i = 1; $i < $items->length; $i++) {
            try {
                $entries[] = $this->recordData($items->item($i)->childNodes, $route);
            } catch (ErrorException $e) {

            }

        }

        $record['entries'] = $entries;


        return $record;
    }

    private function recordData($nodes, $route)
    {
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = trim($node->nodeValue);
            if (!empty($nodeValue)) {
                $data[] = $nodeValue;
            }
        }

        if (!empty($data) && count($data) == 5) {
            $busStopDistance = new BusStopDistance;
            $busStopDistance->bus_service_no = $route;
            $busStopDistance->bus_stop_name = $data[2];
            if (is_numeric($data[0])) {
                $busStopDistance->distance_km = $data[0];
            }
            $busStopDistance->direction = $this->direction;
            $busStopDistance->save();
            return $data[0];
        } else {
            return null;
        }

    }

    public function readStopIds($route)
    {
        $file = public_path() . DIRECTORY_SEPARATOR . "bus-services" . DIRECTORY_SEPARATOR . $route . '.json';
        $fileObj = json_decode(file_get_contents($file));
        $directionName = $this->direction;
        //var_dump($fileObj->$directionName->stops);
        return $fileObj->$directionName->stops;

    }

    public function testConnection()
    {
        $url = "http://www.sbstransit.com.sg/journeyplan/RouteInformation.aspx?qdirect=1&qservno=010&qpoint=NO%20LOOP&dispno=10&qstart=TAMPINES%20INT&qend=TAMPINES%20INT";
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;

    }

    public function mapStopID($direction)
    {
        $routes = BusStopDistance::select('bus_service_no')
            ->where('direction', '=', $direction)
            ->groupBy('bus_service_no')
            ->get();
        foreach ($routes as $route) {
            $routeNum = $route->bus_service_no;
            $file = file_get_contents(public_path() . DIRECTORY_SEPARATOR . 'bus-services' . DIRECTORY_SEPARATOR . $routeNum . '.json');
            $stopIds = json_decode($file)->$direction->stops;

            //var_dump($stopIds);
            $busStops = BusStopDistance::where('bus_service_no', '=', $routeNum)
                ->where('direction', '=', $direction)
                ->orderBy('distance_km')
                ->get();

            //var_dump(DB::getQueryLog());
            $index = 0;
            foreach ($busStops as $busStop) {
                if ($index < count($stopIds)) {
                    $busStop->bus_stop_id = $stopIds[$index];
                    $busStop->save();
                }
                $index++;
            }
            //die();
        }

    }


}