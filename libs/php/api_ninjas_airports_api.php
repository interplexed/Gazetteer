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
    error_log("API Ninjas Airports API Request Variables Error", 0);
  }
  else {

  $env = parse_ini_file(__DIR__ . '/../../.env');
  $header = $env['APININJAS'];

  $url='https://api.api-ninjas.com/v1/airports?country=' . $_REQUEST['cca2'];
  
  // Set up curl		
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // Return as a string
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                        // Leave commented, not expecting any redirection (3xx)
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);                           // Leave commented, set 0 for dev debugging
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // Leave true in production, ensures ssl validity
  $headers = array("X-Api-Key: ".$header);
  curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

  // Execute curl
  $result=curl_exec($ch);
  
  // Gain the result code from curl
  $result_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  // Check for curl errors, create log entry
  if ($result === false) {
    $curl_err = "API Ninjas Airports API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the API Ninjas Airports API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log($curl_err, 0);

  }
  else {
    // Could be 301, 302, and so on
    if ($result_code !== 200) {
      $result_code_log = "API Ninjas Airports API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }

    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the API Ninjas Airports API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("API Ninjas Airports API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {

      // Handle errors in the api response
      if (isset($decode['message']) || isset($decode['error'])) {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the API Ninjas Airports API.";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        switch (true) {
          case isset($decode['message']): error_log("API Ninjas Airports API Error: " . $decode['message'], 0);break;
          case isset($decode['error']): error_log("API Ninjas Airports API Error: " . $decode['error'], 0);break;
          //default: // Do nothing
        }
      }
      else {
        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "ok";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = $decode;
      }
    }
  }
}

if ($output['status']['name'] == "critical failure") {
  error_log("API Ninjas Airports API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 


/* Examples of three error API responses:
{"message":"Forbidden"}
{"error":"Invalid parameters."}
{"message":"Endpoint not found. Please check your spelling and try again."}
*/

/*
 Example of API response:
[{
  "icao": "LOAA",
  "iata": "",
  "name": "Ottenschlag Airport",
  "city": "Ottenschlag",
  "region": "Lower-Austria",
  "country": "AT",
  "elevation_ft": "2867",
  "latitude": "48.418598175",
  "longitude": "15.2166996002",
  "timezone": "Europe/Vienna"
},
{...}]
*/

