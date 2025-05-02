<?php

// Configure error reporting
//error_reporting(E_ALL);
//ini_set('log_errors', 'On');
//ini_set('display_errors', 'On');;

// Performance counter
$executionStartTime = microtime(true);

// Return a 500 if all else fails
$output['status']['code'] = "500";
$output['status']['name'] = "critical failure";
$output['status']['message'] = "An unexpected error has occured.";
$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
$output['data'] = null;

// Verify requests are available
if (!isset($_POST['lat']) || empty($_POST['lat']) || !isset($_POST['lng']) || empty($_POST['lng'])){
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("OpenWeatherMap API Request Variables Error", 0);
}

// Check for no capital city latlng
if ($_POST['lat'] == 0 && $_POST['lng'] == 0){
  // Return no data
  $output['status']['code'] = "200";
  $output['status']['name'] = "ok";
  $output['status']['message'] = null;
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = [];
  error_log('Caught dummy latlng', 0);
}
else {

  $env = parse_ini_file(__DIR__ . '/../../.env');
  $header = $env['OPENWEATHERMAP'];

  $url='http://api.openweathermap.org/data/2.5/forecast?lat=' . $_REQUEST['lat'] . '&lon=' . $_REQUEST['lng'] . '&units=imperial&appid='.$header;

  // Set up curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // Return as a string
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                        // Leave commented, not expecting any redirection (3xx)
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);                           // Leave commented, set 0 for dev debugging
  //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);                        // No ssl to verify

  // Execute curl
  $result=curl_exec($ch);
  
  // Gain the result code from curl
  $result_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  // Check for curl errors, create log entry
  if ($result === false) {
    $curl_err = "OpenWeatherMap API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the OpenWeatherMap API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log($curl_err, 0);

  }
  else {
    // Could be 301, 302, and so on
    if ($result_code !== 200) {
      $result_code_log = "OpenWeatherMap API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }
  
    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the OpenWeatherMap API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("OpenWeatherMap API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {

      // Handle errors in the api response
      if ($decode['cod'] != "200") {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the OpenWeatherMap API.";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        error_log("OpenWeatherMap API Error: " . $decode['message'], 0);
      }
      else {
        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "ok";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";


        try {
          $forecastArray = [];
          $tempData = array();
          $tempData['list'] = array();
          $tempForecast = array();
          foreach ($decode['list'] as &$forecast) {

            if (isset($forecast['dt_txt'])) {
              $objDate = new DateTime($forecast['dt_txt']); // utcdate
              $fmtDate = $objDate->format('D M j');
              $fmtTime = $objDate->format('g:i A');
              $tempForecast['time'] = $fmtTime;
              } 
            
            if (isset($forecast['weather'][0]['icon'])) {
              $tempForecast['icon'] = $forecast['weather'][0]['icon'];
            }
            if (isset($forecast['weather'][0]['description'])) {
              $tempForecast['description'] = $forecast['weather'][0]['description'];
            }
            if (isset($forecast['main']['temp'])) {
              // Conversion from Fahrenheit to Celcius
              $tempForecast['avg'] = round(($forecast['main']['temp'] -32)*5/9);
            }
            if (isset($forecast['pop'])) {
              $tempForecast['percent'] = strval(round($forecast['pop']*100))."%";
            }

            $found = false;
            foreach ($forecastArray as &$fc) {
                if ($fc[0] == $fmtDate) {
                    $fc[] = $tempForecast;
                    $found = true;
                    break;
                }
            }
        
            if (!$found) {
                $forecastArray[] = [$fmtDate, $tempForecast];
            }
        }
        
        // Remove leading empty entries
        while (!empty($forecastArray) && count($forecastArray[0]) == 1) {
          array_shift($forecastArray);
        }
        
        // Remove trailing empty entries
        for ($i = count($forecastArray) - 1; $i >= 0; $i--) {
            if (count($forecastArray[$i]) != 1) {
            //if (count($forecastArray[$i]) != 1) {
                break;
            } else {
                array_pop($forecastArray);
            }
        }
        $output['data'] = $forecastArray;
        }
        catch(Exception $exception) {
          $err = $exception->getMessage();
          $output['status']['code'] = "400";
          $output['status']['name'] = "failure";
          $output['status']['message'] = "There seems to be a problem with the data from the OpenWeatherMap API.";
          $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
          $output['data'] = null;
          error_log("OpenWeatherMap API Processing Error: " . $err, 0);
        }
      }
    }    
  }
}

if ($output['status']['name'] == "critical failure") {
  error_log("OpenWeatherMap API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 

/* Example of error API response:
  "status":
  {
    "cod":"400",
    "message":"wrong latitude"
  }
*/

/*
 Example of API response:
{
  "cod": "200",
  "message": 0,
  "cnt": 40,
  "list": [
    {
      "dt": 1715277600,
      "main": {
        "temp": 289.64,
        "feels_like": 289.25,
        "temp_min": 286.99,
        "temp_max": 289.64,
        "pressure": 1018,
        "sea_level": 1018,
        "grnd_level": 952,
        "humidity": 73,
        "temp_kf": 2.65
      },
      "weather": [
        {
          "id": 804,
          "main": "Clouds",
          "description": "overcast clouds",
          "icon": "04d"
        }
      ],
      "clouds": {
        "all": 99
      },
      "wind": {
        "speed": 0.98,
        "deg": 324,
        "gust": 0.47
      },
      "visibility": 10000,
      "pop": 0.2,
      "sys": {
        "pod": "d"
      },
      "dt_txt": "2024-05-09 18:00:00"
    },
    {
      "dt": 1715288400,
      "main": {
        "temp": 286.68,
        "feels_like": 286.23,
        "temp_min": 284.53,
        "temp_max": 286.68,
        "pressure": 1019,
        "sea_level": 1019,
        "grnd_level": 953,
        "humidity": 82,
        "temp_kf": 2.15
      },
      "weather": [
        {
          "id": 803,
          "main": "Clouds",
          "description": "broken clouds",
          "icon": "04n"
        }
      ],
      "clouds": {
        "all": 53
      },
      "wind": {
        "speed": 1.73,
        "deg": 245,
        "gust": 1.29
      },
      "visibility": 10000,
      "pop": 0,
      "sys": {
        "pod": "n"
      },
      "dt_txt": "2024-05-09 21:00:00"
    },

[...]

  ],
  "city": {
    "id": 3163858,
    "name": "Zocca",
    "coord": {
      "lat": 44.34,
      "lon": 10.99
    },
    "country": "IT",
    "population": 4593,
    "timezone": 7200,
    "sunrise": 1715226944,
    "sunset": 1715279355
  }
}
*/



