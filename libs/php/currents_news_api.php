<?php

// Import a datetime formatting function
require_once "timeElapsedStringHelper.php";

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
if (!isset($_POST['category']) || empty($_POST['category']) || !isset($_POST['cca2']) || empty($_POST['cca2'])){
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("Currents API Request Variables Error", 0);
}
else {

  $env = parse_ini_file(__DIR__ . '/../../.env');
  $header = $env['CURRENTSAPI'];

  $url='https://api.currentsapi.services/v1/search?category=' . $_POST['category'] . '&type=1&limit=10&country=' . $_POST['cca2'] . '&apiKey='.$header;

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
  $result = curl_exec($ch);

  // Gain the result code from curl
  $result_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  // Check for curl errors, create log entry
  if ($result === false) {
    $curl_err = "Currents API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the Currents API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log($curl_err, 0);

  }
  else {
    // Could be 301, 302 and so on
    if ($result_code !== 200) {
      $result_code_log = "Currents API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }

    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the Currents API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("Currents API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {

      // Handle errors in the api response
      if ($decode['status'] !== "ok") {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the Currents API.";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        error_log("Currents API Error: " . $decode['status'] . " - " . $decode['msg'], 0);
      }
      else {

        // Insert a formatted date to the response using the imported function 'time_elapsed_string'
        foreach ($decode['news'] as &$article) {
          try {
            $article['pub_fmt'] = TimeElapsedString($article['published']);
          }
          catch (Exception $eexception) {
            error_log("Currents API Error - Time Elapsed Function Error:" . " - " . $exception->getMessage(), 0);
          }
        }

        // Remove the pass by reference variable used in the above foreach loop
        unset($article);

        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "success";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = $decode;
      }
    }
  }
}

if ($output['status']['name'] == "critical failure") {
  error_log("Currents API Critical Failure");
}

// Set the response headers
header('Content-Type: application/json; charset=UTF-8');

// Return the json response
echo json_encode($output);


/* Example of error API response:
{
  "status":"500",
  "msg":"Requested URL \\/v0\\/search not found",
  "news":null
}
*/


/* Example of normal API response:
{
  "status": "ok",
  "news": [
    {
      "id": "93dfd700-33a9-4482-9fec-b8d0929b9e71",
      "title": "Aaron Hickey: Scotland full-back to miss Euro 2024 finals",
      "description": "Aaron Hickey will miss Scotland's Euro 2024 campaign after being unable to recover from a hamstring injury.",
      "url": "https://www.bbc.com/sport/football/articles/cg33d9lydnlo",
      "author": "@BBCSport",
      "image": "https://ichef.bbci.co.uk/news/1024/branded_sport/b449/live/0215fe90-1544-11ef-9374-79ed0315cfd8.jpg",
      "language": "en",
      "category": [
        "regional"
      ],
      "published": "2024-05-20 17:23:48 +0000"
    },
    {
      "id": "748f5def-b30c-43bf-8cc6-6e3146ead0d4",
      "title": "5G drones aim to transform mountain rescue",
      "description": "A trial in Tarfside in the Angus hills is testing the feasibility of creating pop-up mobile phone networks using drones.",
      "url": "https://www.bbc.co.uk/news/av/uk-scotland-69041066",
      "author": "@BBCNews",
      "image": "https://ichef.bbci.co.uk/news/1024/branded_news/11294/production/_133329207_p0hz6lr2.jpg",
      "language": "en",
      "category": [
        "regional"
      ],
      "published": "2024-05-20 17:16:47 +0000"
    },
[...]
*/
