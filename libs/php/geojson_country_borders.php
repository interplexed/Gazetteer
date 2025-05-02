<?php
	
// Configure error reporting
//error_reporting(E_ALL);
//ini_set('log_errors', 'On');
//ini_set('display_errors', 'On');	

// Start recording the time taken
$executionStartTime = microtime(true);

// Return a 500 if all else fails
$output['status']['code'] = "500";
$output['status']['name'] = "critical failure";
$output['status']['message'] = "An unexpected error has occured.";
$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
$output['data'] = null;

// Handle a missing resource
if (!file_exists("../json/countryBorders.geo.json")) {
		$output['status']['code'] = "500";
		$output['status']['name'] = "failure";
		$output['status']['message'] = "There seems to be an internal problem with the application.";
		$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
		$output['data'] = null;
		error_log("Geojson Country Borders File Missing", 0);
	}

	else {
		// Verify requests are available
		if (!isset($_POST['cca2']) || empty($_POST['cca2']) || !isset($_POST['countryName']) || empty($_POST['countryName'])){
			// The request params are missing or empty
			$output['status']['code'] = "500";
			$output['status']['name'] = "failure";
			$output['status']['message'] = "There seems to be a internal problem with the application.";
			$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
			$output['data'] = null;
			error_log("Geojson Country Borders Request Variables Error", 0);
		}
		else {

		// Reformat the file json into an associative array
			$countryData = json_decode(file_get_contents("../json/countryBorders.geo.json"), true);
			
			// Check the read operation was successful
			if (empty($countryData)) {
				$output['status']['code'] = "400";
				$output['status']['name'] = "failure";
				$output['status']['message'] = "There appears to be a problem with the country border data.";
				$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
				error_log("Geojson Country Borders Read File Error", 0);

			} else {

				// Create the variable to check against the expected behaviour
				$resultFeature=null;

				// Match and obtain the geometry of the country by cca2 or countryName
				foreach ($countryData['features'] as $feature) {
					if ($feature['properties']['iso_a2'] === $_REQUEST['cca2']) {
						$resultFeature = $feature;
						$output['status']['code'] = "200";
						$output['status']['name'] = "ok";
						$output['status']['message'] = null;
						$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
						$output['data'] = $resultFeature;
						break;

					} elseif ($feature['properties']['name'] === $_REQUEST['countryName']) {
						$resultFeature = $feature;
						$output['status']['code'] = "200";
						$output['status']['name'] = "ok";
						$output['status']['message'] = null;
						$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
						$output['data'] = $resultFeature;
						break;
					}
				}

				// Verify geojson data was obtained 
				if (empty($resultFeature)) {
						$output['status']['code'] = "400";
						$output['status']['name'] = "failure";
						$output['status']['message'] = "There appears to be a problem with the country border data.";
						$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
						$output['data'] = null;
						error_log("Geojson Country Borders No Match Made", 0);
				}
			}
		}
	}

if ($output['status']['name'] == "critical failure") {
	error_log("Geojson Country Borders Critical Failure");
}

// Set the return header
header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output);

