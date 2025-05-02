<?php

// Import the geonames api error codes array
require_once "geonames_error_codes_helper.php";

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
if (!isset($_POST['city']) || empty($_POST['city']) || !isset($_POST['countryName']) || empty($_POST['countryName'])){
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("Geonames Wikipedia Search API Request Variables Error", 0);
}
else {

  $env = parse_ini_file(__DIR__ . '/../../.env');
  $header = $env['GEONAMES'];

  $url='http://api.geonames.org/wikipediaSearchJSON?q='. $_REQUEST['city'] . '&title=' . $_REQUEST['countryName'] . '&maxRows=15&username='.$header;

  // Set up curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // Return as a string
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                        // Leave commented, not expecting any redirection (3xx)
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);                           // Leave commented, set 0 for dev debugging
  //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);                        // No ssl to verify

  // Execute curl
  $result=curl_exec($ch);
  
  // Gain the result code from curl
  $result_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  // Check for curl errors, create log entry
  if ($result === false) {
    $curl_err = "Geonames Wikipedia Search API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
    error_log($curl_err, 0);
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the Geonames Wikipedia Search API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
  }
  else {
    // Could be 301, 302 and so on
    if ($result_code !== 200) {
      $result_code_log = "Geonames Wikipedia Search API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }
  
    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the Geonames Wikipedia Search API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("Geonames Wikipedia Search API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {
      
      // Handle errors in the api response
      if (isset($decode['status'])) {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the Geonames Wikipedia Search API.";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;

        $decode['status']['value'] ?
        error_log("Geonames Racecourse Search API Error: " . $decode['status']['message'] . " - " . $geonamesErrorCodes[$decode['status']['value']], 0)
        :
        error_log("Geonames Racecourse Search API Error: " . $decode['status']['message'] . " - GeonamesErrorCodes failed", 0);
      }
      else {
        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "ok";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = $decode['geonames'];
      }
    }
  }
}

if ($output['status']['name'] == "critical failure") {
  error_log("Geonames Wikipedia Search API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 

/* Example of error API response:
{
"status":{
  "message":"Please add a username to each call in order for geonames to be able to identify the calling application and count the credits usage.",
  "value":10
  }
}
*/

/* List of error codes:
10	Authorization Exception
11	record does not exist
12	other error
13	database timeout
14	invalid parameter
15	no result found
16	duplicate exception
17	postal code not found
18	daily limit of credits exceeded
19	hourly limit of credits exceeded
20	weekly limit of credits exceeded
21	invalid input
22	server overloaded exception
23	service not implemented
24	radius too large
27	maxRows too large
*/

/* Example of API response:
{
  "geonames": [
    {
      "summary": "Plouguerneau is a commune in the Finistère department of Brittany in north-western France.  (...)",
      "elevation": 63,
      "geoNameId": 6431005,
      "lng": -4.504167,
      "countryCode": "GB",
      "rank": 100,
      "thumbnailImg": "https://www.geonames.org/img/wikipedia/142000/thumb-141690-100.jpg",
      "lang": "en",
      "title": "Plouguerneau",
      "lat": 48.607778,
      "wikipediaUrl": "en.wikipedia.org/wiki/Plouguerneau"
    },
    {
      "summary": "The United Kingdom of Great Britain and Northern Ireland, commonly known as the United Kingdom (UK) or Britain,Usage is mixed. Some organisations, including the and , prefer to use Britain as shorthand for Great Britain is a sovereign country lying off the north-western coast of the European (...)",
      "elevation": 131,
      "geoNameId": 2635167,
      "feature": "country",
      "lng": -2.4333333333333336,
      "countryCode": "GB",
      "rank": 100,
      "thumbnailImg": "https://www.geonames.org/img/wikipedia/85000/thumb-84637-100.jpg",
      "lang": "en",
      "title": "United Kingdom",
      "lat": 53.55,
      "wikipediaUrl": "en.wikipedia.org/wiki/United_Kingdom"
    },
    {...}
  ]
*/

