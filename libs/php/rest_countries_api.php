<?php

// Configure error reporting
//error_reporting(E_ALL);
//ini_set('log_errors', 'On');
//ini_set('display_errors', 'On');

// Performance counter
$executionStartTime = microtime(true);

// Set this to compare against the expected result
$output = null;

// Return a 500 if all else fails
$output['status']['code'] = "500";
$output['status']['name'] = "failure";
$output['status']['message'] = "An unexpected error has occured.";
$output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
$output['data'] = null;

// Verify requests are available
if (!isset($_POST['cca2']) || empty($_POST['cca2'])) {
  // The request params are missing or empty
  $output['status']['code'] = "500";
  $output['status']['name'] = "failure";
  $output['status']['message'] = "There seems to be a problem with the application.";
  $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
  $output['data'] = null;
  error_log("REST Countries API Request Variables Error", 0);
}
else {

  $url='https://restcountries.com/v3.1/alpha/'. $_REQUEST['cca2'];
  
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
    $curl_err = "REST Countries API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
  }

  // Close the curl execution
  curl_close($ch);

  // Handle error conditions from curl
  if(isset($curl_err)){
    $output['status']['code'] = "400";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "There seems to be a problem with accessing the REST Countries API.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log($curl_err, 0);

  }
  else {
    // Could be 301, 302 and so on
    if ($result_code !== 200) {
      $result_code_log = "REST Countries API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }

    // Reformat the api json into an associative array
    $profileData = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the REST Countries API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("REST Countries API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }
    else {

      // Handle errors in the api response
      if (isset($profileData['status'])) {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the REST Countries API.  ";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        error_log("REST Countries API Error: " . $profileData['status'] . " - " . $profileData['message'], 0);
      }

      else {
        // Prepare a success json response for ajax
        $output['status']['code'] = "200";
        $output['status']['name'] = "ok";
        $output['status']['message'] = null;
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";

        // Operate on the api response data
        // String reformatting
        if (isset($profileData[0]['capitalInfo']['latlng'])) {
          $tmp = explode(",", $profileData[0]['capitalInfo']['latlng'][0]);
          $profileData[0]['capitalInfo']['message'] = "Lat ".$profileData[0]['capitalInfo']['latlng'][0]." Lng ".$profileData[0]['capitalInfo']['latlng'][1];
        }

        // String reformatting
        if (isset($profileData[0]['borders']) ) {
          if (count($profileData[0]['borders']) === 1) {
              $profileData[0]['borders'] = $profileData[0]['borders'][0];
          } else {
              $profileData[0]['borders'] = implode(', ', $profileData[0]['borders']);
          }
        }

        $output['data'] = $profileData;
      }
    }
  }
}

if ($output['status']['name'] == "critical failure") {
  error_log("REST Countries API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 

/*
Example of error API response:
  {"status":404,"message":"Not Found"}
*/

/*
Example of API response:
  {
    "name": {
      "common": "Moldova",
      "official": "Republic of Moldova",
      "nativeName": {
        "ron": {
          "official": "Republica Moldova",
          "common": "Moldova"
        }
      }
    },
    "tld": [
      ".md"
    ],
    "cca2": "MD",
    "ccn3": "498",
    "cca3": "MDA",
    "cioc": "MDA",
    "independent": true,
    "status": "officially-assigned",
    "unMember": true,
    "currencies": {
      "MDL": {
        "name": "Moldovan leu",
        "symbol": "L"
      }
    },
    "idd": {
      "root": "+3",
      "suffixes": [
        "73"
      ]
    },
    "capital": [
      "Chișinău"
    ],
    "altSpellings": [
      "MD",
      "Moldova, Republic of",
      "Republic of Moldova",
      "Republica Moldova"
    ],
    "region": "Europe",
    "subregion": "Eastern Europe",
    "languages": {
      "ron": "Romanian"
    },
    "translations": {
      "ara": {
        "official": "جمهورية مولدوڤا",
        "common": "مولدوڤا"
      },
      "bre": {
        "official": "Republik Moldova",
        "common": "Moldova"
      },
      "ces": {
        "official": "Moldavská republika",
        "common": "Moldavsko"
      },
      "cym": {
        "official": "Republic of Moldova",
        "common": "Moldova"
      },
      "deu": {
        "official": "Republik Moldau",
        "common": "Moldawien"
      },
      "est": {
        "official": "Moldova Vabariik",
        "common": "Moldova"
      },
      "fin": {
        "official": "Moldovan tasavalta",
        "common": "Moldova"
      },
      "fra": {
        "official": "République de Moldavie",
        "common": "Moldavie"
      },
      "hrv": {
        "official": "Moldavija",
        "common": "Moldova"
      },
      "hun": {
        "official": "Moldovai Köztársaság",
        "common": "Moldova"
      },
      "ita": {
        "official": "Repubblica di Moldova",
        "common": "Moldavia"
      },
      "jpn": {
        "official": "モルドバ共和国",
        "common": "モルドバ共和国"
      },
      "kor": {
        "official": "몰도바 공화국",
        "common": "몰도바"
      },
      "nld": {
        "official": "Republiek Moldavië",
        "common": "Moldavië"
      },
      "per": {
        "official": "جمهوری مولداوی",
        "common": "مولداوی"
      },
      "pol": {
        "official": "Republika Mołdawii",
        "common": "Mołdawia"
      },
      "por": {
        "official": "República da Moldávia",
        "common": "Moldávia"
      },
      "rus": {
        "official": "Молдова",
        "common": "Молдавия"
      },
      "slk": {
        "official": "Moldavská republika",
        "common": "Moldavsko"
      },
      "spa": {
        "official": "República de Moldova",
        "common": "Moldavia"
      },
      "srp": {
        "official": "Република Молдавија",
        "common": "Молдавија"
      },
      "swe": {
        "official": "Republiken Moldavien",
        "common": "Moldavien"
      },
      "tur": {
        "official": "Moldova Cumhuriyeti",
        "common": "Moldova"
      },
      "urd": {
        "official": "جمہوریہ مالدووا",
        "common": "مالدووا"
      },
      "zho": {
        "official": "摩尔多瓦共和国",
        "common": "摩尔多瓦"
      }
    },
    "latlng": [
      47,
      29
    ],
    "landlocked": true,
    "borders": [
      "ROU",
      "UKR"
    ],
    "area": 33846,
    "demonyms": {
      "eng": {
        "f": "Moldovan",
        "m": "Moldovan"
      },
      "fra": {
        "f": "Moldave",
        "m": "Moldave"
      }
    },
    "flag": "🇲🇩",
    "maps": {
      "googleMaps": "https://goo.gl/maps/JjmyUuULujnDeFPf7",
      "openStreetMaps": "https://www.openstreetmap.org/relation/58974"
    },
    "population": 2617820,
    "gini": {
      "2018": 25.7
    },
    "fifa": "MDA",
    "car": {
      "signs": [
        "MD"
      ],
      "side": "right"
    },
    "timezones": [
      "UTC+02:00"
    ],
    "continents": [
      "Europe"
    ],
    "flags": {
      "png": "https://flagcdn.com/w320/md.png",
      "svg": "https://flagcdn.com/md.svg",
      "alt": "The flag of Moldova is composed of three equal vertical bands of blue, yellow and red, with the national coat of arms centered in the yellow band."
    },
    "coatOfArms": {
      "png": "https://mainfacts.com/media/images/coats_of_arms/md.png",
      "svg": "https://mainfacts.com/media/images/coats_of_arms/md.svg"
    },
    "startOfWeek": "monday",
    "capitalInfo": {
      "latlng": [
        47.01,
        28.9
      ]
    },
    "postalCode": {
      "format": "MD-####",
      "regex": "^(?:MD)*(\\d{4})$"
    }
  },
{...},
{...},

*/

