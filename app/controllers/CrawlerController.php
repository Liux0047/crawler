<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 10/30/2014
 * Time: 2:25 PM
 */
class CrawlerController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | Default Crawler Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |	Route::get('/', 'HomeController@showWelcome');
    |
    */

    public function getNea($sessionId)
    {
        //set POST variables
        $url = 'https://eservices.nea.gov.sg/TR/action/QRsearchaction';
        $cUrls = array();

        //create the multi handler
        $multiHandler = curl_multi_init();

        for ($postalCodeInt = 0, $postalCodeInt < 2; $postalCodeInt++;) {
            $postalCode = str_pad($postalCodeInt, 6, "0", STR_PAD_LEFT);

            $fields = array(
                'txtbox' => urlencode($postalCode),
            );

            $cookieFile = "/cookies.txt";

            //open connection
            $options = array(
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_ENCODING => "",       // handle all encodings
                CURLOPT_USERAGENT => "spider", // who am i
                CURLOPT_AUTOREFERER => true,     // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                CURLOPT_TIMEOUT => 120,      // timeout on response
                CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
                CURLOPT_SSL_VERIFYPEER => false,
                //    CURLOPT_COOKIEFILE => $cookieFile,
                //    CURLOPT_COOKIEJAR => $cookieFile,
                CURLOPT_COOKIE => 'JSESSIONID=' . $sessionId . '; path=/;',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => 'txtbox=' . $postalCode,
            );

            $ch = curl_init($url);
            curl_setopt_array($ch, $options);

            $cUrls[] = $ch;

            curl_multi_add_handle($multiHandler, $ch);
        }

        $running = null;
        //execute the handles
        do {
            curl_multi_exec($multiHandler, $running);
        } while ($running > 0);

        foreach($cUrls as $ch) {
            $data[] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($multiHandler, $ch);
        }

        curl_multi_close($multiHandler);


    }

}