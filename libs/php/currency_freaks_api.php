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
if (!isset($_POST['currency']) || empty($_POST['currency'])){
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("Currency Freaks API Request Variables Error", 0);
}
else {

  $env = parse_ini_file(__DIR__ . '/../../.env');
  $header = $env['CURRENCYFREAKS'];

  $url = 'https://api.currencyfreaks.com/v2.0/rates/latest?symbols='.$_REQUEST['currency'].'&apikey='.$header;

  // Set up curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // Return as a string
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                        // Leave commented, not expecting any redirection (3xx)
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);                           // Leave commented, set 0 for dev debugging
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // Leave true in production, ensures ssl validity

  // Execute curl
  $result=curl_exec($ch);
  
  // Gain the result code from curl
  $result_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  // Check for curl errors, create log entry
  if ($result === false) {
    $curl_err = "Currency Freaks API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the Currency Freaks API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log($curl_err, 0);
  }
  else {
    // Could be 301, 302, and so on
    if ($result_code !== 200) {
      $result_code_log = "Currency Freaks API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }

    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the Currency Freaks API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("Currency Freaks API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {
      // Handle errors in the api response
      if (isset($decode['error'])) {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the Currency Freaks API.";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        error_log("Currency Freaks API Error: " . print_r($decode,true), 0);
      }
      else {
        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "ok";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = [$decode];
      }
    }
  }
}
if ($output['status']['name'] == "critical failure") {
  error_log("Currency Freaks API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 



/* Example of error API response:
{
  "timestamp": 1717881052360,
  "status": 404,
  "error": "No Handler Found Exception",
  "message": "Required URL is not found. [Contact tech. support for assistance at support@currencyfreaks.com]",
  "path": "/v2.0/rates/last"
}


Example of API response:
{
 "date":"2024-06-08 00:00:00+00",
 "base":"USD",
 "rates":{
     "GBP":"0.7859299270038088"
     }
}
*/