<?php
	
// Configure error reporting
//error_reporting(E_ALL);
//ini_set('log_errors', 'On');
//ini_set('display_errors', 'On');	

// Performance counter
$executionStartTime = microtime(true);

// Set these variables to compare against the expected result
$output = null;
$profileArray = null;

// Return a 500 if all else fails
$output['status']['code'] = "500";
$output['status']['name'] = "critical failure";
$output['status']['message'] = "An unexpected error has occured.";
$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
$output['data'] = null;

// Verify there's a file to read
if (!file_exists("../json/rest_countries_all.json")) {
  $output['status']['code'] = "400";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("REST Countries Data File Access Error", 0);

}
else {
  // Return a 500 if all else fails
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "An unexpected error has occured.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;

  // Verify requests are available
  if (!isset($_POST['cca2']) || empty($_POST['cca2']) || !isset($_POST['countryName']) || empty($_POST['countryName'])){
    // The request params are missing or empty
    $output['status']['code'] = "500";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with the application.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log("REST Countries Data File Request Variables Error", 0);
  }
  else {

    // Reformat the file json into an associative array
    $profileData = json_decode(file_get_contents("../json/rest_countries_all.json"), true);
    
    // Verify the read operation has worked
    if (empty($profileData)) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the REST Countries data.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("REST Countries Data File Read Error", 0);
    }
    else {

      // Go through each entry to check both cca2
      foreach ($profileData as $i) {
        if ($i['cca2'] ==  $_REQUEST['cca2']) {
          $profileArray = $i;
          $output['status']['code'] = "200";
          $output['status']['name'] = "ok";
          $output['status']['message'] = null;
          $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
          break;
        }

        // Or check by countryName
        elseif ($i['name'] == $_REQUEST['countryName']) {
          $profileArray = $i;
          $output['status']['code'] = "200";
          $output['status']['name'] = "ok";
          $output['status']['message'] = null;
          $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
          break;
        }
      }

      if (!empty($profileArray)) {
        // Operate on the api response data
        // String reformatting
        if (isset($profileArray['capitalInfo']['latlng'])) {
          $profileArray['capitalInfo']['message'] = "Lat ".$profileArray['capitalInfo']['latlng'][0]." Lng ".$profileArray['capitalInfo']['latlng'][1];
        }

        // String reformatting
        if (isset($profileArray['borders'])) {
          if (count($profileArray['borders']) === 1) {
              $profileArray['borders'] = $profileArray['borders'][0];
          } else {
              $profileArray['borders'] = implode(', ', $profileArray['borders']);
          }
        }

        $output['data'] = $profileArray;
      }
    }
  }
}
// Final check there's data available
// If the cca2 and countryName are not found,  this is hit
if (empty($output)) {
  $output['status']['code'] = "400";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the data from the REST Countries data.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("REST Countries Data File Entry Not Found", 0);
}

if ($output['status']['name'] == "critical failure") {
  error_log("REST Countries File Critical Failure");
}

// Set the response header
header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output);
