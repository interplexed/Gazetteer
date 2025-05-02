<?php

// Configure error reporting
//error_reporting(E_ALL);
//ini_set('log_errors', 'On');
//ini_set('display_errors', 'On');

// Performance counter
$executionStartTime = microtime(true);

// Return a 500 if all else fails
$output['status']['code'] = "500";
$output['status']['name'] = "critical failure";
$output['status']['message'] = "An unexpected error has occured.";
$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
$output['data'] = null;

// Verify requests are available
if (!isset($_POST['cca2']) || empty($_POST['cca2'])){
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("Radio Browser API Request Variables Error", 0);
}
else {

  // Further: implement a reverse DNS lookup to get available server names, as recommended in the developer guide 'https://api.radio-browser.info/'
  $url='https://de1.api.radio-browser.info/json/stations/bycountrycodeexact/'. $_REQUEST['cca2'] . '?hidebroken=true&order=votes&reverse=true&limit=25';

  // Set up curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // Return as a string
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                        // Leave commented, not expecting any redirection (3xx)
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);                           // Leave commented, set 0 for dev debugging
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // Leave true in production, ensures ssl validity

  // Execute curl
  $result=curl_exec($ch);
  
  // Gain the result code from curl
  $result_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  // Check for curl errors, create log entry
  if ($result === false) {
    $curl_err = "Radio Browser API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the Radio Browser API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log($curl_err, 0);

  }
  else {
    // Could be 301, 302 and so on
    if ($result_code !== 200) {
      $result_code_log = "Radio Browser API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }
  
    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the Radio Browser API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("Radio Browser API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {

        // Handle errors in the api response
        // It appears to be unavailable, an empty list is returned instead
        
        // Operate on the api response data
        // Insert spaces next to commas in a string
        foreach ($decode as &$station) {
          if ($station['tags']) {
            $station['tags'] = str_replace(',',', ',$station['tags']);
          }
        }

        // Remove the pass by reference variable used in the above foreach loop
        unset($article);

        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "ok";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = $decode;
    }
  }
}

if ($output['status']['name'] == "critical failure") {
  error_log("Radio Browser API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 



/* Example of error API response:

*/

/* Example of API response:
[
  {
    "changeuuid": "febb83a7-0423-4961-9c93-9e8df9f274b9",
    "stationuuid": "7fe99458-b6f2-4af0-95bc-e05977964622",
    "serveruuid": "e0c1446a-e937-47a0-b821-c31b93bde840",
    "name": "\tBBC Radio 5 Live Proper",
    "url": "http://as-hls-ww-live.akamaized.net/pool_904/live/ww/bbc_radio_five_live/bbc_radio_five_live.isml/bbc_radio_five_live-audio%3d96000.norewind.m3u8",
    "url_resolved": "http://as-hls-ww-live.akamaized.net/pool_904/live/ww/bbc_radio_five_live/bbc_radio_five_live.isml/bbc_radio_five_live-audio%3d96000.norewind.m3u8",
    "homepage": "https://www.bbc.co.uk/sounds/schedules/bbc_radio_five_live",
    "favicon": "https://static.files.bbci.co.uk/sounds/web/sounds-web/img/sounds-apple-touch-icon.ead169771d.png",
    "tags": "",
    "country": "The United Kingdom Of Great Britain And Northern Ireland",
    "countrycode": "GB",
    "iso_3166_2": null,
    "state": "",
    "language": "",
    "languagecodes": "",
    "votes": 132,
    "lastchangetime": "2023-11-05 13:36:05",
    "lastchangetime_iso8601": "2023-11-05T13:36:05Z",
    "codec": "UNKNOWN",
    "bitrate": 0,
    "hls": 1,
    "lastcheckok": 1,
    "lastchecktime": "2024-05-03 21:12:55",
    "lastchecktime_iso8601": "2024-05-03T21:12:55Z",
    "lastcheckoktime": "2024-05-03 21:12:55",
    "lastcheckoktime_iso8601": "2024-05-03T21:12:55Z",
    "lastlocalchecktime": "2024-05-03 01:22:09",
    "lastlocalchecktime_iso8601": "2024-05-03T01:22:09Z",
    "clicktimestamp": "2024-05-03 22:29:31",
    "clicktimestamp_iso8601": "2024-05-03T22:29:31Z",
    "clickcount": 48,
    "clicktrend": 13,
    "ssl_error": 0,
    "geo_lat": null,
    "geo_long": null,
    "has_extended_info": false
  },
  {
    "changeuuid": "3594120d-99d0-4e13-9d49-e30bc9401e46",
    "stationuuid": "178a4fb9-2c63-407f-a567-f138bdaa94c6",
    "serveruuid": "cb2c3b7a-a530-4965-bd0f-42b972b2e547",
    "name": " ATCC UK - London/Scottish Control",
    "url": "https://audio.soundsofatc.com/atcc",
    "url_resolved": "https://audio.soundsofatc.com/atcc",
    "homepage": "https://www.soundsofatc.com/",
    "favicon": "",
    "tags": "",
    "country": "The United Kingdom Of Great Britain And Northern Ireland",
    "countrycode": "GB",
    "iso_3166_2": null,
    "state": "",
    "language": "",
    "languagecodes": "",
    "votes": 1,
    "lastchangetime": "2024-04-17 00:08:58",
    "lastchangetime_iso8601": "2024-04-17T00:08:58Z",
    "codec": "MP3",
    "bitrate": 0,
    "hls": 0,
    "lastcheckok": 1,
    "lastchecktime": "2024-05-03 13:01:18",
    "lastchecktime_iso8601": "2024-05-03T13:01:18Z",
    "lastcheckoktime": "2024-05-03 13:01:18",
    "lastcheckoktime_iso8601": "2024-05-03T13:01:18Z",
    "lastlocalchecktime": "2024-05-03 08:39:43",
    "lastlocalchecktime_iso8601": "2024-05-03T08:39:43Z",
    "clicktimestamp": "2024-05-03 16:11:20",
    "clicktimestamp_iso8601": "2024-05-03T16:11:20Z",
    "clickcount": 5,
    "clicktrend": 0,
    "ssl_error": 0,
    "geo_lat": null,
    "geo_long": null,
    "has_extended_info": false
  },
[...]
*/

