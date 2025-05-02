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

// Handle a missing resource
if (!file_exists("../json/countryborders.geo.json")) {
    $output['status']['code'] = "500";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be an internal problem with the application.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log("Geojson Country Select File Missing", 0);

  }
  else {

    // Reformat the file json into an associative array
    $countryData = json_decode(file_get_contents("../json/countryborders.geo.json"), true);
    
    // Handle issues with data in file
    if (empty($countryData)) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be an internal problem with the application";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("Geojson Country Select Read File Error", 0);

    } else {

      // Build an array of objects with each feature's cca2,countryName
      // Note some particularities were identified, eg where provided boundaries were not well supported in apis
      $country = [];
      foreach ($countryData['features'] as $feature) {
        if ($feature['properties']['name'] === 'N. Cyprus' || $feature['properties']['name'] === 'Somaliland') {continue;}
        if ($feature['properties']['name'] === 'Kosovo') {$feature['properties']['iso_a2'] = 'XK';}
        $temp = null;
        $temp['cca2'] = $feature['properties']['iso_a2'];
        $temp['countryName'] = $feature['properties']['name'];
        array_push($country, $temp);
      }

      // Sort on name property of each object
      usort($country, function ($item1, $item2) {
        return $item1['countryName'] <=> $item2['countryName'];
      });

      // Prepare a success json response for ajax
      $output['status']['code'] = "200";
      $output['status']['name'] = "ok";
      $output['status']['message'] = null;
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = $country;
    }
  }

if ($output['status']['name'] == "critical failure") {
    error_log("Geojson Country Select Critical Failure");
}

// Set the return header
header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output);

