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
if (!isset($_POST['cca2']) || empty($_POST['cca2'])){
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the Geonames Racecourse Search.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("Geonames Racecourse Search Request Variables Error", 0);
}
else {

  $env = parse_ini_file(__DIR__ . '/../../.env');
  $header = $env['GEONAMES'];

  $url='http://api.geonames.org/searchJSON?q=racecourse&featureclass=racetrack&country=' . $_REQUEST['cca2'] . '&maxRows=60&username='.$header;

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
    $curl_err = "Geonames Racecourse Search API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
    error_log($curl_err, 0);
  }
  
  // Close the curl execution
  curl_close($ch);

    // Handle error conditions from curl
    if(isset($curl_err)){
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with accessing the Geonames Racecourse Search API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
    }
    else {
      // Could be 301, 302 and so on
      if ($result_code !== 200) {
        $result_code_log = "Geonames Racecourse Search API HTTP: " . $result_code;
        error_log($result_code_log, 0);
      }
  
    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

  // Check for json data issues
  if (json_last_error() !== JSON_ERROR_NONE) {
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with the data from the Geonames Racecourse Search API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log("Geonames Racecourse Search API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
  }
  else {

      // Handle errors in the api response
      if (isset($decode['status'])) {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the Geonames Racecourse Search API.";
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
  error_log("Geonames Racecourse Search API Critical Failure");
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

/* Example of API response:
{
  {
  "totalResultsCount": 72,
  "geonames": [
    {
      "adminCode1": "ENG",
      "lng": "-0.67849",
      "geonameId": 8224080,
      "toponymName": "Ascot Racecourse",
      "countryId": "2635167",
      "fcl": "S",
      "population": 0,
      "countryCode": "GB",
      "name": "Ascot Racecourse",
      "fclName": "spot, building, farm",
      "adminCodes1": {
        "ISO3166_2": "ENG"
      },
      "countryName": "United Kingdom",
      "fcodeName": "racetrack",
      "adminName1": "England",
      "lat": "51.4143",
      "fcode": "RECR"
    },
    {
      "adminCode1": "ENG",
      "lng": "-2.94652",
      "geonameId": 8224085,
      "toponymName": "Aintree Racecourse",
      "countryId": "2635167",
      "fcl": "S",
      "population": 0,
      "countryCode": "GB",
      "name": "Aintree Racecourse",
      "fclName": "spot, building, farm",
      "adminCodes1": {
        "ISO3166_2": "ENG"
      },
      "countryName": "United Kingdom",
      "fcodeName": "racetrack",
      "adminName1": "England",
      "lat": "53.4765",
      "fcode": "RECR"
    },
    {
      "adminCode1": "WLS",
      "lng": "-2.6886",
      "geonameId": 8378820,
      "toponymName": "Chepstow Racecourse",
      "countryId": "2635167",
      "fcl": "S",
      "population": 0,
      "countryCode": "GB",
      "name": "Chepstow Racecourse",
      "fclName": "spot, building, farm",
      "adminCodes1": {
        "ISO3166_2": "WLS"
      },
      "countryName": "United Kingdom",
      "fcodeName": "racetrack",
      "adminName1": "Wales",
      "lat": "51.6564",
      "fcode": "RECR"
    },
    {...}
  ]
*/

