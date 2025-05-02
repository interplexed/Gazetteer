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
  if (!isset($_POST['lat']) || empty($_POST['lat']) || !isset($_POST['lng']) || empty($_POST['lng'])){
    // The request params are missing or empty
    $output['status']['code'] = "500";
    $output['status']['name'] = "failure";
    $output['status']['message'] = "here seems to be a problem with the application.";
    $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    error_log("OpenCage API Request Variables Error", 0);
  }
  else {
    
    $env = parse_ini_file(__DIR__ . '/../../.env');
    $header = $env['OPENCAGE'];
    
    $url='https://api.opencagedata.com/geocode/v1/json?q=' . $_REQUEST['lat'] . '%2C' . $_REQUEST['lng'] . '&key='.$header;

      
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
      $curl_err = "OpenCage API cURL Error: #" . curl_errno($ch) . " - " . curl_error($ch) . " - " . $result_code;
    }

    // Close the curl execution
    curl_close($ch);

    // Handle error conditions from curl
    if(isset($curl_err)){
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with accessing the OpenCage API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log($curl_err, 0);
    }
    else {
      // Could be 301, 302, and so on
      if ($result_code !== 200) {
      $result_code_log = "OpenCage API HTTP: " . $result_code;
      error_log($result_code_log, 0);
    }

    // Reformat the api json into an associative array
    $decode = json_decode($result,true);

    // Check for json data issues
    if (json_last_error() !== JSON_ERROR_NONE) {
      $output['status']['code'] = "400";
      $output['status']['name'] = "failure";
      $output['status']['message'] = "There seems to be a problem with the data from the OpenCage API.";
      $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
      $output['data'] = null;
      error_log("OpenCage API JSON Error: #" . json_last_error() . " - " . json_last_error_msg(), 0);
    }

    else {
      // Handle errors in the api response
      if ($decode['status']['code'] != "200") {
        $output['status']['code'] = "400";
        $output['status']['name'] = "failure";
        $output['status']['message'] = "There seems to be a problem with the OpenCage API.";
        $output['status']['executedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        error_log("OpenCage API Error: " . $decode['status']['message'], 0);
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
  error_log("OpenCage API Critical Failure");
}

header('Content-Type: application/json; charset=UTF-8');

echo json_encode($output); 

/* Example of error API response
{
  "documentation": "https://opencagedata.com/api",
  "licenses": [
    {
      "name": "see attribution guide",
      "url": "https://opencagedata.com/credits"
    }
  ],
  "results": [],
  "status": {
    "code": 400,
    "message": "not a valid version"
  },
  "stay_informed": {
    "blog": "https://blog.opencagedata.com",
    "mastodon": "https://en.osm.town/@opencage"
  },
  "thanks": "For using an OpenCage API",
  "timestamp": {
    "created_http": "Sun, 09 Jun 2024 11:17:50 GMT",
    "created_unix": 1717931870
  },
  "total_results": 0
}
*/

/*
Example of API response:
{
  "documentation": "https://opencagedata.com/api",
  "licenses": [
    {
      "name": "see attribution guide",
      "url": "https://opencagedata.com/credits"
    }
  ],
  "rate": {
    "limit": 2500,
    "remaining": 2474,
    "reset": 1717977600
  },
  "results": [
    {
      "annotations": {
        "DMS": {
          "lat": "52° 23' 16.01880'' N",
          "lng": "9° 44' 0.38184'' E"
        },
        "MGRS": "32UND4991404423",
        "Maidenhead": "JO42uj83ab",
        "Mercator": {
          "x": 1083521.518,
          "y": 6836676.75
        },
        "NUTS": {
          "NUTS0": {
            "code": "DE"
          },
          "NUTS1": {
            "code": "DE9"
          },
          "NUTS2": {
            "code": "DE92"
          },
          "NUTS3": {
            "code": "DE929"
          }
        },
        "OSM": {
          "edit_url": "https://www.openstreetmap.org/edit?node=9041448608#map=17/52.38778/9.73344",
          "note_url": "https://www.openstreetmap.org/note/new#map=17/52.38778/9.73344&layers=N",
          "url": "https://www.openstreetmap.org/?mlat=52.38778&mlon=9.73344#map=17/52.38778/9.73344"
        },
        "UN_M49": {
          "regions": {
            "DE": "276",
            "EUROPE": "150",
            "WESTERN_EUROPE": "155",
            "WORLD": "001"
          },
          "statistical_groupings": [
            "MEDC"
          ]
        },
        "callingcode": 49,
        "currency": {
          "alternate_symbols": [],
          "decimal_mark": ",",
          "html_entity": "€",
          "iso_code": "EUR",
          "iso_numeric": "978",
          "name": "Euro",
          "smallest_denomination": 1,
          "subunit": "Cent",
          "subunit_to_unit": 100,
          "symbol": "€",
          "symbol_first": 0,
          "thousands_separator": "."
        },
        "flag": "🇩🇪",
        "geohash": "u1qfj2zsvwd6ntczum3r",
        "qibla": 131.65,
        "roadinfo": {
          "drive_on": "right",
          "road": "Philipsbornstraße",
          "speed_in": "km/h"
        },
        "sun": {
          "rise": {
            "apparent": 1717902060,
            "astronomical": 0,
            "civil": 1717899120,
            "nautical": 1717894500
          },
          "set": {
            "apparent": 1717962000,
            "astronomical": 0,
            "civil": 1717964940,
            "nautical": 1717969620
          }
        },
        "timezone": {
          "name": "Europe/Berlin",
          "now_in_dst": 1,
          "offset_sec": 7200,
          "offset_string": "+0200",
          "short_name": "CEST"
        },
        "what3words": {
          "words": "monopoly.tables.cats"
        }
      },
      "bounds": {
        "northeast": {
          "lat": 52.387833,
          "lng": 9.7334894
        },
        "southwest": {
          "lat": 52.387733,
          "lng": 9.7333894
        }
      },
      "components": {
        "ISO_3166-1_alpha-2": "DE",
        "ISO_3166-1_alpha-3": "DEU",
        "ISO_3166-2": [
          "DE-NI"
        ],
        "_category": "building",
        "_normalized_city": "Hanover",
        "_type": "building",
        "city": "Hanover",
        "city_district": "Vahrenwald-List",
        "continent": "Europe",
        "country": "Germany",
        "country_code": "de",
        "county": "Region Hannover",
        "house_number": "2",
        "office": "Design Offices",
        "political_union": "European Union",
        "postcode": "30165",
        "road": "Philipsbornstraße",
        "state": "Lower Saxony",
        "state_code": "NI",
        "suburb": "Vahrenwald"
      },
      "confidence": 10,
      "distance_from_q": {
        "meters": 6
      },
      "formatted": "Design Offices, Philipsbornstraße 2, 30165 Hanover, Germany",
      "geometry": {
        "lat": 52.387783,
        "lng": 9.7334394
      }
    }
  ],
  "status": {
    "code": 200,
    "message": "OK"
  },
  "stay_informed": {
    "blog": "https://blog.opencagedata.com",
    "mastodon": "https://en.osm.town/@opencage"
  },
  "thanks": "For using an OpenCage API",
  "timestamp": {
    "created_http": "Sun, 09 Jun 2024 13:20:45 GMT",
    "created_unix": 1717939245
  },
  "total_results": 1
}
*/

