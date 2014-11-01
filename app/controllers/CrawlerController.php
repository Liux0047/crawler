<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 10/30/2014
 * Time: 2:25 PM
 */
class CrawlerController extends BaseController
{

    private $postalStart = 389372;
    private $postalEnd = 389974;

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

    public function getNea($sessionId = null)
    {
        //set POST variables
        $url = 'https://eservices.nea.gov.sg/TR/action/QRsearchaction';
        $cUrls = array();

        //create the multi handler
        $multiHandler = curl_multi_init();

        for ($postalCodeInt = $this->postalStart; $postalCodeInt < $this->postalEnd; $postalCodeInt++) {
            $postalCode = str_pad($postalCodeInt, 6, "0", STR_PAD_LEFT);
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
            curl_multi_add_handle($multiHandler, $ch);


            $cUrlRecord['curl'] = $ch;
            $cUrlRecord['postalCode'] = $postalCode;
            $cUrls[] = $cUrlRecord;
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
            $records[] = $this->extractData(curl_multi_getcontent($ch['curl']), $ch['postalCode']);
            curl_multi_remove_handle($multiHandler, $ch['curl']);
        }

        curl_multi_close($multiHandler);

        $param['records'] = $records;

        return View::make('result', $param);


    }


    private function extractData($html, $postalCode)
    {

        $dataArray = [];
        $tableTag = "<TABLE  id=\"showtab\"  border=0 cellSpacing=0 cellPadding=0 ";
        $tableStart = strpos($html, $tableTag);
        if ($tableStart) {
            $table = substr($html, $tableStart, strpos($html, "</TABLE>", $tableStart) - $tableStart);


            $DOM = new DOMDocument;
            $DOM->loadHTML($table);

            //get all <tr>
            $items = $DOM->getElementsByTagName('tr');

            //display all H1 text
            for ($i = 1; $i < $items->length; $i++) {
                $dataArray[] = $this->recordData($items->item($i)->childNodes, $postalCode);

            }
        }


        return $dataArray;
    }

    private function recordData($nodes, $postalCode)
    {
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = trim($node->nodeValue);
            if (!empty($nodeValue)) {
                $data[] = $nodeValue;
            }
        }

        if (!empty($data) && count($data) >= 4 && strpos($data[1], $postalCode)) {
            $trackRecord = new NeaTrackRecord;
            $trackRecord->premises = $data[1];
            $trackRecord->license_ref_no = $data[2];
            $trackRecord->licensee = $data[3];
            $trackRecord->postal_code = $postalCode;
            $trackRecord->save();
            return $data[1] . ": " . $data[2] . "-" . $data[3];
        } else {
            return null;
        }

    }

}