<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 12/5/2014
 * Time: 4:06 PM
 */
class SMRTController extends BaseController
{

    public function getDistance()
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 300);
        //set POST variables

        $url = 'http://www.smrt.com.sg/eBusGuideWebService.aspx?CallType=details&';
        //BusNo=67
        $urlSuffix = '&callback=jquery';


        $cUrls = array();

        //create the multi handler
        $multiHandler = curl_multi_init();

        $services = array('BPS1','RWS8','61','67','75','77','106','167','169','171','172','173','176','177','178','180','184','187','188','188E','188R','189','190','300','302','307','529','530','531','541','546','547','587','588','589','590','598','599','700','700A','800','803','804','806','811','812','825','850E','851','852','853','853C','854','854E','855','856','857','858','859','859A','859B','860','868','882','900A','900','901','902','903','904','911','912','913','920','922','925','925C','926','927','941','945','947','950','951E','960','961','961C','962','963','963E','963R','964','965','966','969','970','971E','972','975','980','981','982E','985','990','NR1','NR2','NR3','NR5','NR6','NR7','NR8');

        foreach ($services as $service) {

            $serviceNumber = $service;
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

            $urlQuery = $url . "BusNo=" . $serviceNumber . $urlSuffix;

            $ch = curl_init($urlQuery);
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($multiHandler, $ch);

            $chArray = array('ch' => $ch, 'route' => $serviceNumber);
            $cUrls[] = $chArray;

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

        return View::make('SMRT-result', $param);


    }


    private function extractData($json, $route)
    {
        $record = array('route' => $route, 'entries' => array());
        $returnStr = substr($json, 7, strlen($json) - 8);
        if ($returnStr[0] != "{") {
            return $record;
        }

        try {
            $returnObj = json_decode($returnStr);
            $result = $returnObj->Result;

        }
        catch (ErrorException  $e){
            return $record;
        }

        $forth = $result->Forth;
        foreach ($forth as $stop) {
            $entries[] = $this->recordData($stop, $route, 1);
        }

        if (isset($result->Back)) {
            $back = $result->Back;
            foreach ($back as $stop) {
                $entries[] = $this->recordData($stop, $route, 2);
            }

        }

        $record['entries'] = $entries;
        return $record;


    }

    private function recordData($stop, $route, $direction)
    {
        $busStopDistance = new busStopDistanceSMRT;
        $busStop = explode(' - ', $stop->BusStop);

        $busStopDistance->bus_service_no = $route;
        $busStopDistance->bus_stop_id = $busStop[0];
        $busStopDistance->bus_stop_name = $busStop[1];
        $busStopDistance->distance_km = $stop->Distance;
        $busStopDistance->direction = $direction;
        $busStopDistance->save();

        return $stop->Distance;


    }

} 