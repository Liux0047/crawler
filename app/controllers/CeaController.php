<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 3/13/2015
 * Time: 11:15 PM
 */
class CeaController extends BaseController
{

    public function getData()
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 300);
        //set POST variables

        $url = 'https://www.cea.gov.sg/cea/app/newimplpublicregister/searchPublicRegister.jspa';


        $cUrls = array();

        //create the multi handler
        $multiHandler = curl_multi_init();

        for ($i = 96885557; $i < 96885577; $i++) {
            $numbers[] = '' . $i;
        }

        foreach ($numbers as $number) {
            //open connection
            $options = array(
                CURLOPT_CUSTOMREQUEST => "POST", //set request type post or get
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
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => 'slsMblNum=' . $number . '&type=searchSls&slsName=&slsEaName=&slsRegNo=&answer=',
            );

            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($multiHandler, $ch);


            $chArray = array('ch' => $ch, 'number' => $number);
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


        foreach ($cUrls as $ch) {
            echo curl_multi_getcontent($ch['ch']) . $ch['number'];
            curl_multi_remove_handle($multiHandler, $ch['ch']);
        }

        curl_multi_close($multiHandler);


    }
}