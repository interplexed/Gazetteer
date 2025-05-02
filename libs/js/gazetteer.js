
// JavaScript and jQuery

// ----------------------------------------------------
// GLOBAL VARIABLES

let myMap;                // Leaflet map instance
let myMarker;             // Maybe not strictly needed as its generated
let myLatLng;             // Provide hardcoded default values in case of issues?
let profileArray;         // Country profile data fetched on load - REMOVE
let cityLatLng;           // Each country's captial city location
let cityLayer;            // capital city markers layer
let airportsLayer;        // Airports markers layer
let racecoursesLayer;     // Racecourses markers layer
let bordersLayer;         // Country border geometry layer
let countryFeatureGroup;  // GeoJSON object. Will replace countyJson?
let popupMessage;         // Text applied to different markers
let markerStyle;          // Color and icon applied to different markers
let weatherData;          // Retrieved json ready for presenting
let currencyData;         // Retrieved json ready for presenting
let wikiData;             // Retrieved json ready for presenting
let radioData;            // Retrieved json ready for presenting
let newsData;             // Retrieved json ready for presenting
let errorMessage;         // Messages supplied to displayErrorPage



// ----------------------------------------------------
// LEAFLET: GET JAVASCRIPT NAVIGATOR.GEOLOCATION

function getLocation() {
    // Verify that a geolocation service is available via the client device/system
    if (navigator.geolocation) {
      return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
          // Handle resolved promise
          position => resolve(position),
          // Handle rejected promise
          error => reject(error),
          // Just a timeout in options
          {timeout: 7000}
        )
      }).catch(
        (error) => {
        if(error.code == 1) {
          appErrorLogger('geolocation','warning',"Location permission was blocked for the app, but it's required for it to work.");
        } else if(error.code == 2) {
          appErrorLogger('geolocation','warning',"The location service can't be reached, or there is no network connectivity. On mobile, check that location is enabled.");
        } else if(error.code == 3) {
          appErrorLogger('geolocation','warning',"The app could not get the location data and the attempt timed out. If your device is using VPN software, try temporarily disconnecting.");
        } else {
          appErrorLogger('geolocation','warning',"For some reason, getting your location has failed.");
        }
      });
    }
    else {
      appErrorLogger('geolocation', 'warning', "Geolocation is not supported")
    }
}




// ----------------------------------------------------
// HELPER: CLEAR THE CURRENT MODAL'S GRID PRESENTATION DATA

function clearGrid(grid) {
  if (document.getElementById(grid).innerHTML) {
    document.getElementById(grid).innerHTML = '';
    }
}




// ----------------------------------------------------
// LEAFLET: MARKER ICON STYLING VIA THE EXTRA-MARKERS PLUGIN

var airportIcon = L.ExtraMarkers.icon({
    icon: 'fa-plane',
    markerColor: 'violet',
    shape: 'circle',
    prefix: 'fa'
});

var racecourseIcon = L.ExtraMarkers.icon({
    icon: 'fa-trophy',
    markerColor: 'orange',
    shape: 'circle',
    prefix: 'fa'
});

var cityIcon = L.ExtraMarkers.icon({
    icon: 'fa-star',
    markerColor: 'green-light',
    shape: 'penta',
    prefix: 'fa'
});

var defaultIcon = L.ExtraMarkers.icon({
    icon: 'fa-gem',
    markerColor: 'cyan',
    shape: 'square',
    prefix: 'fa'
});




// ----------------------------------------------------
// LEAFLET: CREATE INDIVIDUAL MARKERS WITH ICONS AND POPUPS

function createMarker(myLatLng,popupMessage=null,markerStyle=null) {
    let coord;

    switch (markerStyle) {
      case 'airportMarker': markerStyle = airportIcon;break;
      case 'racecourseMarker': markerStyle = racecourseIcon;break;
      case 'cityMarker': markerStyle = cityIcon;break;
      default: markerStyle = defaultIcon;
    }

    if (Array.isArray(myLatLng) == false) {
      coord = "Lat: "+ myLatLng.lat.toFixed(6)+"<br>Lng: "+myLatLng.lng.toFixed(6);
    }
    else {
      coord = "Lat: "+ myLatLng[0].toFixed(6)+"<br>Lng: "+myLatLng[1].toFixed(6);
    }

    if (popupMessage != null) {
      popupMessage = '<b>'+popupMessage+'</b><br>'
      myMarker = L.marker(myLatLng,{icon:markerStyle}).bindPopup(popupMessage+coord);
      }
    else {
      myMarker = L.marker(myLatLng, {icon: new markerStyle()}).bindPopup(coord);
      }
    return myMarker
}




// ----------------------------------------------------
// LEAFLET: USE THE COUNTRY PROFILE TO CREATE THE CAPITAL CITY LAYER

async function buildCitiesLayer(profileArray) {

    let cityLayer = L.layerGroup();

    // Use the profile data for the capital city if available
    if (profileArray['capital'] && profileArray['capitalInfo']['latlng']) {
      // Add markers for the captial city and set a popup message with capital_name
      cityLatLng = profileArray['capitalInfo']['latlng'];
      let popupMessage = profileArray['capital']

      markerStyle = 'cityMarker';
      let cityMarker = createMarker(cityLatLng,popupMessage,markerStyle);
      cityMarker.addTo(cityLayer);
      return [cityLayer,cityLatLng]
    }
    else {
      // Continue and return an empty layer
      return [cityLayer,cityLatLng=[0,0]]
    }

}




// ----------------------------------------------------
// LEAFLET: USE API NINJAS AIRPORTS API TO CREATE THE AIRPORTS LAYER
// The api returned list is up to 30 airports - and there might be some well known ones missing

async function buildAirportsLayer(cca2) {

    let airports = await getAirports(cca2);

    var airportsLayer = L.markerClusterGroup({
      polygonOptions: {
      fillColor: 'grey', 
      color: '#fe019a', //bright pink
      weight: 3,
      opacity: 1,
      fillOpacity: 0.2
      }
    });

    // No need to present status messages for errors
    // The airports['data'] array can be empty
    // If error, the returned layer is still added to the map

    if (! airports['status']['message'] && airports['data'] != null) {

      let airportData = airports['data'];
 
      for (let i=0; i<airportData.length; i++) {
        // Add markers for airport latlngs and set popup message with airport_name airport_icao: city_name
        let airportLatLng = [Number(airportData[i]['latitude']),Number(airportData[i]['longitude'])];
        let popupMessage = airportData[i]['name']+ "<br>"+airportData[i]['city']+": "+airportData[i]['icao'];
        if (airportLatLng) {
          markerStyle='airportMarker';
          let airportMarker = createMarker(airportLatLng,popupMessage,markerStyle);
          airportsLayer.addLayer(airportMarker)        
        }
      }
    }
    return airportsLayer
}




// ----------------------------------------------------
// LEAFLET: USE GEONAMES RACECOURSE SEARCH API TO CREATE THE RACECOURSES LAYER

async function buildRacecoursesLayer(cca2) {

    let racecourses = await getRacecourses(cca2);

    var racecoursesLayer = L.markerClusterGroup({
      polygonOptions: {
      fillColor: 'grey', 
      color: '#fe019a', //bright pink
      weight: 3,
      opacity: 1,
      fillOpacity: 0.2
      }
    });

    // No need to present status messages for errors
    // The racecourses['data'] array can be empty
    // If error, the returned layer is still added to the map   

    if (!racecourses['status']['message'] && racecourses['data'] != null) {

      let racecourseData = racecourses['data'];

      for (let i=0; i<racecourseData.length; i++) {
        // Add markers for racecourse latlngs and set popup message with name, adminCode1
        let racecourseLatLng = [Number(racecourseData[i]['lat']),Number(racecourseData[i]['lng'])];
        let popupMessage = racecourseData[i]['name'];
        if (racecourseLatLng) {
          markerStyle='racecourseMarker';
          let racecourseMarker = createMarker(racecourseLatLng,popupMessage,markerStyle);
          racecoursesLayer.addLayer(racecourseMarker)        
        }
      }
    }
    return racecoursesLayer
}




// ----------------------------------------------------
// LEAFLET: USE GEOJSON DATA TO CREATE COUNTRY BORDERS LAYER

async function buildBordersLayer(cca2,countryName) {
    let countryFeature = await generateBorders(cca2,countryName);
    bordersLayer = L.geoJSON(countryFeature)
    return bordersLayer
}




async function processCca2(cca2,countryName){
  let errorMessage;

  // Rest countries has api server instability - comment out for production
  //profileArray = {}
  //profileArray['data'] = null;

  // Rest countries has api server instability - uncomment for production
  profileArray = await callRestCountriesApi(cca2);

  // Catch api failure, but try again with a local file of api data
  // Alternatively, use displayErrorPage
  if (!profileArray['data']) {
    //displayErrorPage('body',errorMessage=profileArray['status']['message']);
    profileArray = await generateProfileFromFile(cca2,countryName);
  }

  // 
  // Display full page error message if there is one
  if (!profileArray['data']) {
    profileArray['status']['message'] ?
    displayErrorPage('body',errorMessage=profileArray['status']['message'])
    :
    displayErrorPage('body',errorMessage=null);
  }

  else {
    // Focus on the data
    if (!profileArray['data'].length) {
      //console.log("converted from file",(profileArray));
      profileArray = profileArray['data'];
    }
    else {
      //console.log("converted from api",(profileArray));
      profileArray = profileArray['data'][0];
    }
  }

  // Final check for data
//  if (!profileArray) {
//    console.log(profileArray)
//    displayErrorPage('body',errorMessage=null);  
//    }
//  else {
      return profileArray
//  }
}




// ----------------------------------------------------
// LEAFLET: BUILD MAP LAYERS AND COUNTRY PROFILE

async function processProfile(profileArray,cca2,countryName){
    let errorMessage;

    // Set markers for capital city
    const cityResult = await buildCitiesLayer(profileArray);
    [cityLayer,cityLatLng] = cityResult;

    // Set markers for airports
    airportsLayer = await buildAirportsLayer(cca2);
    
    // Set markers for racecourses
    racecoursesLayer = await buildRacecoursesLayer(cca2);

    // Set geometry for the borders - uses the countryname from oCage
    bordersLayer = await buildBordersLayer(cca2,countryName);

    // Check these layers before destructured assignment
    if (!cityLayer || !airportsLayer || !racecoursesLayer || !bordersLayer) {
      displayErrorPage('body',errorMessage=null);  
    }
    else {
      return [cityLatLng,cityLayer,airportsLayer,racecoursesLayer,bordersLayer];
    }
}

  



  // ----------------------------------------------------
  // LEAFLET: BUILD REMAINING EASYBUTTONS FROM API CALLS

  async function processApi(profileArray,cityLatLng){

    // Gain weather data for capital city
      weatherData = await callOpenWeatherMapForecastApi(cityLatLng);
    
    // Gain currency data
    let curr = Object.keys(profileArray['currencies'])
    currencyData = await callCurrencyFreaksApi(curr[0]);

    // Gain wikipedia data 
    wikiData = await callWikipediaSearchApi(profileArray['capital'],profileArray['name'].common);
    
    // Gain wikipedia data 
    radioData = await callRadioBrowserApi(profileArray['cca2']);
    
    // Gain wikipedia data
    let category = 'regional';
    newsData = await callCurrentsApi(profileArray['cca2'],category,profileArray['name'].common);

    if (!weatherData || !currencyData || !wikiData || !radioData || !newsData) {
      displayErrorPage('body',errorMessage=null);  
    }
    else {
      return [weatherData,currencyData,wikiData,radioData,newsData];
    }
}




// ----------------------------------------------------
// LEAFLET: CREATE COUNTRY SELECT DROPDOWN FROM THE GEOJSON FILE DATA

async function buildDropdown(cca2){
    let countryArray = {};
    let countrySelect = document.getElementById('countrySelect');
    
    countryArray = await generateCountrySelect();
    if (countryArray) {
      // Build html select element options using countryArray
      for (let c=0; c<countryArray.length; c++) {  
        let option = document.createElement('option');
        // Set the visible text to be the country's name
        option.setAttribute('value', countryArray[c]['cca2']);
        // Set each option's 'value' to be the country's two letter code
        option.innerHTML = countryArray[c]['countryName'];
        // Add an HTML classname for CSS styling
        option.className = 'countrySelectItem';
        countrySelect.append(option);
        
      }
    
    // Configure the dropdown to the current location's country code 
    countrySelect.value = cca2;
    }
}




// ----------------------------------------------------
// APPLICATION: CREATE MAP, LAYERS AND EASYBUTTONS

async function mapSetup(profileArray,cityLayer,airportsLayer,racecoursesLayer,bordersLayer,weatherData,currencyData,wikiData,radioData,newsData){

    // Use OpenStreetMap (OSM) for a map tile provider (streets)
    let osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    
    // Use OpenTopoMap (OTM) for a map tile provider (topology)
    let otm = L.tileLayer('https://tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: '&copy; <a href="https://www.opentopomap.org/copyright">OpenTopoMap</a>'
    });

    // Use ArcGISOnline (AGOS) for a map tile provider (streets)
    let agos = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", {
      maxZoom: 17,
      attribution: "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012"
    });
    
    // Use ArcGISOnline (AGOA) for a map tile provider (aerial)
    let agoa = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
      maxZoom: 17,
      attribution: "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012"
    });
    
    // Use ArcGISOnline (AGOR) for a map tile provider (relief)
    let agor = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}", {
      maxZoom: 17,
      attribution: "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012"
    });
   
    // Use CartoDB (CART) for a map tile provider (relief)
    let cart = L.tileLayer("https://basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png", {
      maxZoom: 17,
      attribution: "Tiles &copy; CartoDB"
    });

    // Obtain a good centre point
    let centerLatLng = bordersLayer.getBounds().getCenter();
    
    // Init map, set the initial coordinates, associate the maps and overlays
    myMap = L.map('map').setView(centerLatLng,7,[osm, otm, agos, agoa, agor, cart, cityLayer, airportsLayer, bordersLayer]);

    // Define bases and overlays
    const baseMaps = {
        "OpenStreetMap": osm,
        "OpenTopMap": otm,
        "ArcGISOnline-Streets": agos,
        "ArcGISOnline-Aerial": agoa,
        "ArcGISOnline-Relief": agor,
        "CartoDB-Light": cart

    };
    const overlayMaps = {
        "Capital City": cityLayer,
        "Airports": airportsLayer,
        "Racecourses": racecoursesLayer
    };

    // Set a base map to the tile
    cart.addTo(myMap);
    
    // Set all overlays to the tile
    cityLayer.addTo(myMap);
    airportsLayer.addTo(myMap);
    racecoursesLayer.addTo(myMap);

    // Set borders to the tile
    bordersLayer.addTo(myMap);

    myMap.on('dblclick', function(event){
      myMap.clicked = 0;
      // How to zoom in to location of dblclick, not just bounds.center?
      myMap.zoomIn();
    })

    // Present the map
    myMap.flyToBounds(bordersLayer.getBounds());

    // Combine layers and map for selector on map
    let layerControl = L.control.layers(baseMaps, overlayMaps);//.addTo(myMap);
    layerControl.addTo(myMap);

    // Implement debouncing https://stackoverflow.com/questions/29035896/leaflet-dont-fire-a-click-event-function-on-double-click
    myMap.clicked = 0;

    // Distance scale bar on map
    L.control.scale({imperial: true, metric: true}).addTo(myMap);


    // EasyButton for Info
    L.easyButton('&#x1F30E;', function(){ //americas globe
        $("#infoModal").modal("show");
        presentCountryProfile(profileArray);
      },"Country Profile").addTo(myMap);
      

    // EasyButton for Weather Forecast
    L.easyButton('&#x26c5;', function(){ //sun,cloud
        $("#weatherModal").modal("show");
        presentWeatherData(weatherData,profileArray['capital'],profileArray['name'].common);
      },"Weather").addTo(myMap);


    // EasyButton for Currency
    L.easyButton('&#x1F4B1;', function(){ //currency exchange
        $("#currencyModal").modal("show");
        let curr = Object.keys(profileArray['currencies'])
        presentCurrencyData(currencyData,curr[0],profileArray['name'].common);
      },"Currency").addTo(myMap);


    // EasyButton for Wikipedia
    L.easyButton('&#x1F4DA;', function(){ //books
        $("#wikiModal").modal("show");
        presentWikipediaData(wikiData,profileArray['name'].common);
      },"Wikipedia").addTo(myMap);
      
      
    //EasyButton for Radio
    L.easyButton('&#x1F4FB;', function(){ //radio
        $('.modal').on('shown.bs.modal', function() {
          $(".modal-body").css("padding-top",'0px');
          });
        $("#radioModal").modal("show");
        presentRadioData(radioData,profileArray['name'].common);
      },"Radio Browser").addTo(myMap);
      

    //EasyButton for News
    L.easyButton('&#x1F4F0;', function(){ //newspaper
        $("#newsModal").modal("show");
        presentNewsData(newsData,profileArray['name'].common);
      },"News").addTo(myMap);

    return myMap,myMarker
}




// ----------------------------------------------------
// APPLICATION: INIT FUNCTION

async function init(){

   try {
      let errorMessage;

      // Obtain a device geolocation to inform OpenCage of the current country
      $('.load-dialog').text('Getting location...')
        let position = await getLocation();
        myLatLng = [position.coords.latitude,position.coords.longitude];

      $('.load-dialog').text('Processing location...')
      // Use device location to retrieve the cca2 and countryName
      let oCage = await queryOpenCageLatLng(myLatLng)

      // Catch null data response
      // Display full page error message if there is one
      if (!oCage['data']['results']) {
        oCage['status']['message'] ?
        displayErrorPage('body',errorMessage=oCage['status']['message'])
        :
        displayErrorPage('body',errorMessage=null);
      }  
      else {
        // Double check for empty data response
        // Display full page error message if there is one
        if (!oCage['data']['results'].length) {
          // Results array is empty
          displayErrorPage('body',errorMessage=null);
        }
        else {
          // Focus on the data
          oCage = oCage['data']['results'];

          let cca2 = oCage[0]['components']['ISO_3166-1_alpha-2'];
          let countryName = oCage[0]['components']['country'];

          // Populate the dropdown from the GeoJSON data
          await buildDropdown(cca2);

          // Build country profile
          $('.load-dialog').text('Building profile...')
          profileArray = await processCca2(cca2,countryName);

          // Build map layers 
          $('.load-dialog').text('Building layers...')
          const layersResult = await processProfile(profileArray,cca2,countryName);
          [cityLatLng,cityLayer,airportsLayer,racecoursesLayer,bordersLayer] = layersResult;

          // Build modals 
          $('.load-dialog').text('Retrieving data...')
          const apiResult = await processApi(profileArray,cityLatLng);
          [weatherData,currencyData,wikiData,radioData,newsData] = apiResult;

          // Start the map element, overlays and create the first marker
          $('.load-dialog').text('Building map...')
          myMap,myMarker = await mapSetup(profileArray,cityLayer,airportsLayer,racecoursesLayer,bordersLayer,weatherData,currencyData,wikiData,radioData,newsData)

        }
      }



      // ----------------------------------------------------
      // JQUERY: TRIGGER MAP REBULDING FROM DROPDOWN LIST COUNTRY SELECTION

      $("#countrySelect").on('change', function() {
        trigger(rebuild($(this)))
      });
    }
    catch (err) {
      //console.log("General failure: ", err);
      let errorMessage = "Unforeseen technical issues have occurred";
      appErrorLogger('init','error',errorMessage);
      //displayErrorPage('body',errorMessage);
    }
}




// ----------------------------------------------------
// UTILITY: REBUILD THE MAP BASED ON THE COUNTRY SELECTED

async function rebuild(ctx) { 
  let cca2Ctx = ctx.find('option:selected').val();
  let countryNameCtx = ctx.find('option:selected').text();

  // Build country profile
  $('.load-dialog').text('One moment...')
  //$('.load-dialog').text('Building profile...')
  profileArray = await processCca2(cca2Ctx,countryNameCtx);

  // Build map layers 
  $('.load-dialog').text('Building layers...')
  const layersResult = await processProfile(profileArray,cca2Ctx,countryNameCtx);
  [cityLatLng,cityLayer,airportsLayer,racecoursesLayer,bordersLayer] = layersResult;

  // Build modals 
  $('.load-dialog').text('Retrieving data...')
  const apiResult = await processApi(profileArray,cityLatLng);
  [weatherData,currencyData,wikiData,radioData,newsData] = apiResult;

  // Unset and then rebuild the map
  $('.load-dialog').text('Setting map...')
  myMap = myMap.off();
  myMap = myMap.remove();
  myMap,myMarker = await mapSetup(profileArray,cityLayer,airportsLayer,racecoursesLayer,bordersLayer,weatherData,currencyData,wikiData,radioData,newsData)
  
  // Fly to country
  myMap.flyToBounds(bordersLayer.getBounds());
  bordersLayer.addTo(myMap);
}




// ----------------------------------------------------
// GEOJSON: GAIN DATA FOR THE COUNTRY SELECT DROPDOWN

async function generateCountrySelect() {
  let errorMessage;

  return $.ajax({
    url: "../gazetteer/libs/php/geojson_country_select.php",
    method: 'POST',
    dataType: 'json',
    success: function(result) {
      console.log(JSON.stringify(result));

      if (result['status']['code'] != "200") {
        if (!result['status']['message']) {
          result['status']['message'] = "There's been an unexpected problem but it will be investigated";
        }
        errorMessage = result['status']['message'];
        displayErrorPage('body',errorMessage);
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      appErrorLogger('Geojson Country Select Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
    }
  }).then(response => response.data); 
}




// ----------------------------------------------------
// LEAFLET: ON FIRST LOAD CHECK OPENCAGE FOR THE CURRENT GEOLOCATION COUNTRY CODE

async function queryOpenCageLatLng(myLatLng) {
    let errorMessage;
    //("go ocage")

		return $.ajax({
			url: "../gazetteer/libs/php/open_cage_reverse_api.php",
			method: 'POST',
			dataType: 'json',
			data: {
        lat: myLatLng[0],
        lng: myLatLng[1]
			},
			success: function(result) {
        console.log(JSON.stringify(result));

        if (result['status']['code'] != "200") {
          if (!result['status']['message']) {
            result['status']['message'] = "There's been an unexpected problem but it will be investigated";
          }
          errorMessage = result['status']['message'];
          displayErrorPage('body',errorMessage);
        }

			},
			error: function(jqXHR, textStatus, errorThrown) {
          appErrorLogger('Opencage Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
			}
		});
}




// ----------------------------------------------------
// MAP LAYER: GENERATE FEATURE FROM COUNTRYBORDERS GEOJSON FILE

async function generateBorders(cca2, countryName) {
    let errorMessage;
    
    return $.ajax({
      url: "../gazetteer/libs/php/geojson_country_borders.php",
      method: 'POST',
      dataType: 'json',
      data: {
        cca2: cca2,
        countryName: countryName
      },
      success: function(result) {
        console.log(JSON.stringify(result));

        if (result['status']['code'] != "200") {
          if (!result['status']['message']) {
            result['status']['message'] = "There's been an unexpected problem but it will be investigated";
          }
          errorMessage = result['status']['message'];
          displayErrorPage('body',errorMessage);
        }

      },
      error: function(jqXHR, textStatus, errorThrown) {
        appErrorLogger('Geojson Country Borders Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
      }
    }).then(response => response.data); 
}





// ----------------------------------------------------
// MAP LAYER: API NINJAS AIRPORTS API CALL

async function getAirports(cca2) {

		return $.ajax({
			url: "../gazetteer/libs/php/api_ninjas_airports_api.php", 
			method: 'POST',
			dataType: 'json',
			data: {
        cca2: cca2
			},
			success: function(result) {
				console.log(JSON.stringify(result));

        // The api data will or won't be null, status messages are not required for presentation
     
			},
			error: function(jqXHR, textStatus, errorThrown) {
        appErrorLogger('Api Ninjas Airports Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
			}
    });
}



// ----------------------------------------------------
// MAP LAYER: GEONAMES RACECOURSE SEARCH API CALL

async function getRacecourses(cca2) {

    return $.ajax({
      url: "../gazetteer/libs/php/geonames_racecourses_search_api.php",
      method: 'POST',
      dataType: 'json',
      data: {
        cca2: cca2
      },
      success: function(result) {
        console.log(JSON.stringify(result));
                
        if (result.status.name == "ok") {

          // The api data will or won't be null, status messages are not required for presentation

        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        appErrorLogger('Geonames Racecourse Search Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
      }
    });
}




// ----------------------------------------------------
// PRESENT REST COUNTRIES DATA (COUNTRY INFO)

function presentCountryProfile(profileArray){

  // Update the modal heading
  let modalHeader = document.getElementById('infoModalTitle');
  modalHeader.innerHTML = `Profile for ${profileArray['name'].common}`;

  // Clear the presented data when navigating away
  $('#infoModal').on('hidden.bs.modal', () => {
    clearGrid('infoGrid');
  })

  $('#infoGrid').append(`<div class="row pt-2 pb-1"><div class="col-2 text-center"><i class="fa fa-star"></i></div><div class="col-5 text-start">Flag</div><div class="col-5 text-end"><img class="flag" src="${profileArray['flags']['png']}" alt="flag of ${profileArray['name'].common}"/> &nbsp<a href=${profileArray['flags']['png']} target='_blank'>&#x2197;</a></div></div>`);
  Object.values(profileArray['languages']).forEach(value => {$('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-language"></i></div><div class="col-5 text-start">Language</div><div class="col-5 text-end">${value}</div></div>`)});
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-building"></i></div><div class="col-5 text-start">Capital City</div><div class="col-5 text-end">${profileArray['capital']}</div></div>`);
  Object.keys(profileArray['currencies']).forEach(key => {$('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-money"></i></div><div class="col-5 text-start">Currency</div><div class="col-5 text-end">(${profileArray['currencies'][key]['symbol']}) ${key} ${profileArray['currencies'][key]['name']}</div></div>`)});
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-globe"></i></div><div class="col-5 text-start">Region</div><div class="col-5 text-end">${profileArray['region']}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-globe"></i></div><div class="col-5 text-start">Subregion</div><div class="col-5 text-end">${profileArray['subregion']}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-community"></i></div><div class="col-5 text-start">Population</div><div class="col-5 text-end">${Number(profileArray['population']).toLocaleString()}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-tree"></i></div><div class="col-5 text-start">Land Area</div><div class="col-5 text-end">${Number(profileArray['area']).toLocaleString()}km<sup>2</sup></div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-car"></i></div><div class="col-5 text-start">Drive On</div><div class="col-5 text-end">${profileArray['car']['side']}</div></div>`);
  profileArray['borders'] ? 
    $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-map-marker"></i></div><div class="col-5 text-start">Land Borders</div><div class="col-5 text-end content-overflow">${profileArray['borders']}</div></div>`) 
    : 
    $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-map-marker"></i></div><div class="col-5 text-start">Land Borders</div><div class="col-5 text-end">None</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-phone"></i></div><div class="col-5 text-start">Intl. Direct Dial</div><div class="col-5 text-end">${profileArray['idd']['root']+profileArray['idd']['suffixes'][0]}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-map-pin"></i></div><div class="col-5 text-start">Capital Coordinates</div><div class="col-5 text-end">${profileArray['capitalInfo']['message']}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-envelope-o"></i></div><div class="col-5 text-start">UN Member</div><div class="col-5 text-end">${profileArray['unMember']}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-1"><div class="col-2 text-center"><i class="fa fa-address-book"></i></div><div class="col-5 text-start">Independent</div><div class="col-5 text-end">${profileArray['independent']}</div></div>`);
  $('#infoGrid').append(`<div class="row pt-1 pb-2"><div class="col-2 text-center"><i class="fa fa-files-o"></i></div><div class="col-5 text-start">Designated Status</div><div class="col-5 text-end">${profileArray['status']}</div></div>`);
}




// ----------------------------------------------------
// REST COUNTRIES DATA FILE CALL
// Temporary use of all REST Countries v3 data saved to local file in order to mitigate api server issues and reduce load

async function generateProfileFromFile(cca2,countryName) {
  let errorMessage;

  return $.ajax({
    url: "../gazetteer/libs/php/rest_countries_file.php",
    method: 'POST',
    dataType: 'json',
    data: {
      cca2: cca2,
      countryName: countryName
    },
    success: function(result) {
      console.log(JSON.stringify(result));

      if (result['status']['code'] != "200") {
        if (!result['status']['message']) {
          result['status']['message'] = "There's been an unexpected problem but it will be investigated";
        }
        errorMessage = result['status']['message'];
        displayErrorPage('body',errorMessage);
      }

    },
    error: function(jqXHR, textStatus, errorThrown) {
      appErrorLogger('Rest Countries Datafile Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
    }
  });
}




// ----------------------------------------------------
// REST COUNTRIES API CALL

async function callRestCountriesApi(cca2) {
    let errorMessage;

    return $.ajax({
    url: "../gazetteer/libs/php/rest_countries_api.php",
    method: 'POST',
    dataType: 'json',
    data: {
      cca2: cca2.toLowerCase()
    },
    //timeout:3000,
    done: function(result) {
      console.log(JSON.stringify(result));

      // Strict error response was removed as fallback datafile can be used

    },
    fail: function(jqXHR, textStatus, errorThrown) {
      appErrorLogger('Rest Countries API Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
    }
  });
}


// ----------------------------------------------------
// PRESENT OPENWEATHERMAP DATA

function presentWeatherData(weatherData,cityName,countryName){

    // Update the modal heading
    let modalHeader = document.getElementById('weatherModalTitle');
    modalHeader.innerHTML = `Weather for ${cityName}, ${countryName}`;

    // Display a modal error message if there is one
    if (weatherData['status']['message'] && weatherData['data'] == null){
      displayErrorPage('#weatherGrid',weatherData['status']['message']);
    }

    // Catch no data available
    if (!weatherData['status']['message'] && weatherData['data'] != null) {
      if (!weatherData['data'].length) {
        // Avoid potential duplicated message
        clearGrid('weatherGrid');
        $('#weatherGrid').append(`<div class="noneFound">Weather data is currently unavailable for ${countryName}</div>`);
      }

      else {
        // Clear the presented data on navigating away
        $('#weatherModal').on('hidden.bs.modal', () => {
          clearGrid('weatherGrid');
        })

        // Focus on the data
        const items = weatherData['data'];

        // Handle duplicated dom elements
        if(document.querySelectorAll('.pagination')) {
          let containers = document.querySelectorAll('.pagination');
          containers.forEach(container => {container.remove();})
        }
      
        // Pagination prereqs
        let currentPage = 0;

        // Create the presentation of forecasts
        function showPage(page) {
          // Clear any existing table and create a copy of the original
          $('#weatherGrid').empty();
          let tempArray = [].concat(items[page]);

          // Check for string in array index 0 and remove
          if (typeof(tempArray[0]) === 'string'){
            [, ...tempArray] = tempArray;
          }

          const forecast = document.createElement('div');
          forecast.classList.add('row','ps-1','pt-2','pb-2','pe-1','row-cols-auto','flex-nowrap');
          forecast.id = 'forecast';
          weatherGrid.appendChild(forecast);

          tempArray.forEach((time) => {
            $('#forecast').append(`
              <div class="col text-center fcastDetails"><p>${time['time']}</p>
              <i class="owi owi-2x owi-${time['icon']}" aria-label="${time['description']}"></i>
              <p>${time['avg']}&#x2103;</p>
              <p>${time['percent']}</p>
              </div>
            `);
          });
  //            <p>${time['avg']}&#x00B0; <sup>C</sup></p>

          // Include row names
          $('#forecast').prepend(`
          <div class="col text-center"><p>Time</p>
            <p>Type</p>
            <p>Temp</p>
            <p>Rain</p>
          </div>
        `);

          // Lastly, style the button for the selected day
          updateActiveButtonStates();
        }
        
        // Function to create pagination buttons
        function createPageButtons() {
          const paginationContainer = document.createElement('div');
          paginationContainer.classList.add('row','flex-nowrap','pagination','weatherPaginate');
          let paginationDiv = document.getElementById('weatherPaginate').appendChild(paginationContainer);
          
          for (let i=0; i<items.length; i++) {
            const pageButton = document.createElement('div');
            pageButton.classList.add('col','text-center','page')

            // The items[page] array still includes the date string
            let x = items[i].length - 1;

            // Select a main icon to show based on what's available
            switch(true) {
              case items[i].length >= 8:
                //console.log("noon",items[i]);
                pageButton.innerHTML = `<div class="row"><div class="col">${items[i][0]}</div></div><div class="row"><div class="col-6"><i class="owi owi-3x owi-${items[i][5]['icon']}"></i></div><div class="col-6">${items[i][5]['avg']}</div></div>`;
                break;

              case items[i].length >= 5:
                //console.log("noon",items[i]);
                pageButton.innerHTML = `<div class="row"><div class="col">${items[i][0]}</div></div><div class="row"><div class="col-6"><i class="owi owi-3x owi-${items[i][3]['icon']}"></i></div><div class="col-6">${items[i][3]['avg']}</div></div>`;
                break;

              case items[i].length >= 3 && items[i].length > 2: 
                //console.log("next",items[i]); 
                pageButton.innerHTML = `<div class="row"><div class="col">${items[i][0]}</div></div><div class="row"><div class="col-6"><i class='owi owi-3x owi-${items[i][x]['icon']}'></i></div><div class="col-6 text-normal">${items[i][x]['avg']}</div></div>`;
                break;

              case items[i].length == 2: 
                //console.log("show last", items[i])
                pageButton.innerHTML = `<div class="row"><div class="col">${items[i][0]}</div></div><div class="row"><div class="col-6"><i class='owi owi-3x owi-${items[i][1]['icon']}'></i></div><div class="col-6 text-normal">${items[i][1]['avg']}</div></div>`;
                break;

              default:
                //console.log("show none", items[i])
                pageButton.innerHTML = `<div class="row"><div class="col">${items[i][0]}</div></div>`;
            }
      
            pageButton.addEventListener('click', () => {
              currentPage = i;
              showPage(currentPage);
              updateActiveButtonStates();
            });
              document.getElementById('weatherGrid').before(paginationContainer);
              paginationDiv.appendChild(pageButton);
          }
        }

        // Pagination button styling
        function updateActiveButtonStates() {
          const pageButtons = document.querySelectorAll('div.col.page');
          pageButtons.forEach((button, index) => {
            if (index === currentPage) {
              button.classList.add('active');
            } else {
              button.classList.remove('active');
            }
          });
        }

        // Pagination control code
        createPageButtons(); 
        showPage(currentPage);
      }
  }
}




// ----------------------------------------------------
// OPEN WEATHER MAP API CALL

async function callOpenWeatherMapForecastApi(myLatLng) {
    let errorMessage;
		
    return $.ajax({
			url: "../gazetteer/libs/php/open_weather_map_forecast_api.php",
			method: 'POST',
			dataType: 'json',
			data: {
        lat:myLatLng[0],
        lng:myLatLng[1]
			},
			done: function(result) {
        console.log(JSON.stringify(result));

        if (result['status']['code'] != "200") {
          if (!result['status']['message']) {
            result['status']['message'] = "There's been an unexpected problem but it will be investigated";
          }
          errorMessage = result['status']['message'];
          displayErrorPage('#weatherGrid',errorMessage);
        }

			},
			fail: function(jqXHR, textStatus, errorThrown) {
        appErrorLogger('OpenWeatherMap Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
			}
		});
}




// ----------------------------------------------------
// PRESENT CURRENCY EXCHANGE DATA

function presentCurrencyData(currencyData,currency,countryName) {

    // Update the modal heading
    let modalHeader = document.getElementById('currencyModalTitle');
    modalHeader.innerHTML = `Currency Conversion for ${countryName}`;

    // Display a modal error message if there is one
    if (currencyData['status']['message'] && currencyData['data'] == null){
      displayErrorPage('#currencyGrid',currencyData['status']['message']);
    }

    // Catch no data available
    if (!currencyData['status']['message'] && currencyData['data'] != null) {
      if (!currencyData['data'].length) {
        // Avoid potential duplicated message
        clearGrid('currencyGrid');
        $('#currencyGrid').append(`<div class="noneFound">Currency conversion is unavailable for ${countryName} at this time</div>`);
      }
      else {
        // Clear the presented data on navigating away
        $('#currencyModal').on('hidden.bs.modal', () => {
            clearGrid('currencyGrid');
        });
    
        // Focus on the data
        currencyData = currencyData['data'][0];
    
        // Obtain all available currency abbreviations
        let abbrv = Object.keys(profileArray['currencies']);
    
        // Create and insert a dropdown for currency selection
        const $selectToCurrency = $('<select>', { id: 'currencyToSelect' });
    
        // Populate the dropdown with all available currencies
        abbrv.forEach(currencyCode => {
            let symbol = profileArray['currencies'][currencyCode]?.symbol || ''; // Handle missing symbols
            let optionText = `${symbol} ${currencyCode}`.trim(); // Ensure clean formatting
            $selectToCurrency.append($("<option>").attr('value', currencyCode).text(optionText));
        });
    
        // Append UI elements
        $('#currencyGrid').append(`
            <div class="row pt-2 mt-1">
                <div id="currencyFromContainer" class="col-6 text-center">
                    <label for="currencyInput" class="fw-bold">Base</label>
                </div>
                <div id="currencyToContainer" class="col-6 text-center">
                    <label for="currencyResult" class="fw-bold">Local</label>
                </div>
            </div>
        `);
    
        // Append the currency dropdown (enabled by default)
        $('#currencyToContainer').append($selectToCurrency);
    
        // Create and insert text input for USD input
        const $input = $('<input>', { type: 'text', id: 'currencyInput', placeholder: '0.00', maxlength: '10' });
        $input.addClass('form-control input-sm text-center');
        $('#currencyFromContainer').append($input);
    
        // Create and insert a disabled select for the base currency
        const $selectFromCurrency = $('<select>');
        $selectFromCurrency.append($("<option>").attr('value', '').text(currencyData['base']));
        $selectFromCurrency.prop('disabled', true);
        $('#currencyFromContainer').append($selectFromCurrency);
    
        // Create and insert text input for the currency result
        const $result = $('<input>', { type: 'text', id: 'currencyResult', placeholder: '0.00', maxlength: '10' });
        $result.addClass('form-control input-sm text-center');
        $('#currencyToContainer').append($result);
    
        // Function to update exchange rate when currency changes
        function updateExchangeRate(selectedCurrency) {
          if ($('#currencyModal .modal-body .alert-danger').length > 0) {
            $('#currencyModal .modal-body .alert-danger').remove();
        }

            if (!currencyData['rates'][selectedCurrency]) {
              $('#currencyModal').find('.modal-body').append(`
                <div class="alert alert-danger mt-3">
                    <strong>Error:</strong> Exchange rate for ${selectedCurrency} not found.
                </div>
            `);  
              //console.error(`Exchange rate for ${selectedCurrency} not found.`);
                return;
            }
    
            let newExc = (Math.round(currencyData['rates'][selectedCurrency] * 1000) / 1000).toFixed(2);
            let newReverseExc = (1 / newExc).toFixed(2);
            let newSymbol = profileArray['currencies'][selectedCurrency]?.symbol || '';
    
            // Remove the old exchange rate section
            $('#exchangeRateContainer').remove();
    
            // Append new exchange rate details
            $('#currencyGrid').append(`
                <div id="exchangeRateContainer">
                    <div class="row pt-3 mb-1">
                        <div class="col text-center">
                            <p class="fw-bold fs-5">&#x0024;1 ${currencyData['base']} = ${newSymbol}<span class="text-success">${newExc}</span> ${selectedCurrency}</p>
                            <p class="fw-bold fs-5">${newSymbol}<span class="text-success">${newReverseExc}</span> ${selectedCurrency} = &#x0024;1 ${currencyData['base']}</p>
                        </div>
                    </div>
                </div>
            `);
    
            // Store updated exchange rates
            $input.data('exchangeRate', newExc);
            $input.data('reverseRate', newReverseExc);
    
            // Recalculate values based on the current input
            recalculateInputs();
        }
    
        // Function to recalculate inputs based on current values
        function recalculateInputs() {
            let exchangeRate = parseFloat($input.data('exchangeRate') || 1);
            let reverseRate = parseFloat($input.data('reverseRate') || 1);
    
            let baseInput = parseFloat($input.val() || 0) * exchangeRate;
            let currInput = parseFloat($result.val() || 0) * reverseRate;
    
            $result.val(baseInput.toFixed(2));
            $input.val(currInput.toFixed(2));
        }
    
        // Event listener for currency selection change
        $selectToCurrency.on('change', function () {
            let selectedCurrency = $(this).val();
            if (selectedCurrency) {
                updateExchangeRate(selectedCurrency);
            }
        });
    
        // Default exchange rate display
        updateExchangeRate(currency);
    
        // Handler for live input conversion (left input)
        $input.on('keyup', function () {
            let exchangeRate = parseFloat($input.data('exchangeRate') || 1);
            let baseInput = parseFloat($input.val() || 0) * exchangeRate;
            $result.val(baseInput.toFixed(2));
        });
    
        // Handler for live input conversion (right input)
        $result.on('keyup', function () {
            let reverseRate = parseFloat($input.data('reverseRate') || 1);
            let currInput = parseFloat($result.val() || 0) * reverseRate;
            $input.val(currInput.toFixed(2));
        });
    }
    }
  }





// ----------------------------------------------------
// CURRENCYFREAKS API CALL

async function callCurrencyFreaksApi(currency) {
    let errorMessage;
		
    return $.ajax({
			url: "../gazetteer/libs/php/currency_freaks_api.php",
			method: 'POST',
			dataType: 'json',
			data: {
        currency: currency
			},
			done: function(result) {
				console.log(JSON.stringify(result));

        if (result['status']['code'] != "200") {
          if (!result['status']['message']) {
            result['status']['message'] = "There's been an unexpected problem but it will be investigated";
          }
          errorMessage = result['status']['message'];
          displayErrorPage('#currencyGrid',errorMessage);
        }

			},
			fail: function(jqXHR, textStatus, errorThrown) {
          appErrorLogger('Currency Freaks Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
			}
		});
}




// ----------------------------------------------------
// PRESENT GEONAMES WIKIPEDIA SEARCH DATA

function presentWikipediaData(wikiData,countryName) {

    // Update the modal heading
    let modalHeader = document.getElementById('wikiModalTitle');
    modalHeader.innerHTML = `Wikipedia Articles for ${countryName}`;

    // Display the modal error message if there is one
    if (wikiData['status']['message'] && wikiData['data'] == null){
      displayErrorPage('#wikiGrid',wikiData['status']['message']);
    }

    // Catch no data available
    else if (!wikiData['status']['message'] && wikiData['data'] != null) {
      if (!wikiData['data'].length) {
        // Avoid potential duplicated message
        clearGrid('wikiGrid');
        $('#wikiGrid').append(`<div class="noneFound">There appears to be no Wikipedia articles listed for ${countryName} at this time</div>`);
      }

      else {
        // Clear the presented data on navigating away
        $('#wikiModal').on('hidden.bs.modal', () => {
          clearGrid('wikiGrid');
        })

        for (let row in wikiData['data']) {
          row = wikiData['data'][row];

          let imgShard;
          row['thumbnailImg'] ?
            imgShard = `<div class="row"><div class="col"><img class='img-sm float-start p-1' src='${row['thumbnailImg']}' alt="Favicon"/><p>${row['summary']}</p></div></div>`
            :
            imgShard = `<div class="row"><div class="col"><p>${row['summary']}</p></div></div>`
      
          $('#wikiGrid').append(`
          <div class="row pt-1 mt-1"><div class='col'><h5>${row['title']}</h5></div></div>
          ${imgShard}
          <div class="row pb-1 mb-1"><div class='col'><a href="https://${row['wikipediaUrl']}" target="_blank">See more <i class="fa fa-external-link"></i></a></div></div>
          `);
        }
      }
    }
}




// ----------------------------------------------------
// GEONAMES WIKIPEDIA SEARCH API CALL

async function callWikipediaSearchApi(city,countryName) {
    let errorMessage;

    try {
      city = encodeURIComponent(city);
      countryName = encodeURIComponent(countryName);
      
      return $.ajax({
        url: "../gazetteer/libs/php/geonames_wikipedia_search_api.php",
        method: 'POST',
        dataType: 'json',
        data: {
          city: city,
          countryName: countryName
        },
        success: function(result) {
          console.log(JSON.stringify(result));
                  
          if (result['status']['code'] != "200") {
            if (!result['status']['message']) {
              result['status']['message'] = "There's been an unexpected problem but it will be investigated";
            }
            errorMessage = result['status']['message'];
            displayErrorPage('#wikiGrid',errorMessage);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          appErrorLogger('Geonames Wikipedia Search Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
        }
      });
    }
    catch {
      appErrorLogger('Geonames Wikipedia Search Ajax', 'error', 'Encoding Error')
    }
}




// ----------------------------------------------------
// PRESENT RADIO BROWSER DATA

function presentRadioData(radioData,countryName){

    // Update the modal heading
    let modalHeader = document.getElementById('radioModalTitle');
    modalHeader.innerHTML = `Internet Radio for ${countryName}`;

    // Display the modal error message if there is one
    if (radioData['status']['message'] && radioData['data'] == null) {
      displayErrorPage('#radioGrid',radioData['status']['message']);
    }

    // Catch no data available
    else if (!radioData['status']['message'] && radioData['data'] != null) {
      if (!radioData['data'].length) {
        // Avoid potential duplicated message
        clearGrid('radioGrid');
        $('#radioGrid').append(`<div class="noneFound">There appears to be no radio stations listed for ${countryName} at this time</div>`);
      }
      else {
        // Clear the presented data on navigating away
        $('#radioModal').on('hidden.bs.modal', () => {
          clearGrid('radioGrid');
        })

        let audioPaused = 1;

        // Error was found when clicking play/pause too quickly
        async function playAudio() {
            await audioPlayer.play().catch((err)=>{appErrorLogger('audioPlayer', 'info', `RadioBrowser - ${err}`)});
        }

        // Create the audio player
        $('#radioGrid').append(`
        <div id="playerContainer" class="sticky-top">
          <audio id='audioPlayer'>Your browser does not support the html audio tag</audio>
          <div class="row pt-2">
            <div class="col-3"><i class="playBtn ps-3 fa fa-2x fa-play" aria-label="Radio Station Player Audio Play Pause"></i></div>
            <div id="audioPlayerLabel" class="col-9 p-2" aria-label="Radio Station Player Label"></div>
          </div>
          <div class="row text-center"><div class="col"><input type="range" min="0" max="1" step="0.01" id="volume-slider"/></div></div>  
        </div>
        `);

        // Create the station rows
        for (let row in radioData['data']) {
          row = radioData['data'][row];

          let imgShard;
          row['favicon'] ?
            imgShard = `<div class="col-3"><img class='img-xs float-start p-1' src="${row['favicon']}" alt="Favicon"/></div>`
            :
            imgShard = `<div class="col-3"><img class='img-xs float-start p-1' src="" alt=""/></div>`;

          $('#radioGrid').append(`
            <div id="resultsContainer">
              <div class="row ps-2 pt-2 mt-2">
                <div class="col"><h5>${row['name']}</h5></div>
              </div>
              <div class="row ps-2 pb-1 pe-2">
                ${imgShard}
                <div class="col-2">
                  <a href="${row['homepage']}" target="_blank"><i class="fa fa-external-link"></i></a>
                </div>
                <div class="col-7">
                  <i class="audioLoad ps-2 fa fa-play" value="${row['url_resolved']}" name="${row['name']}"></i>
                </div>
              </div>
              <div class="row ps-2 pb-1 mb-2 pe-2">
                <div class="col">
                  <p class="audioTags">${row['tags']}</p>
                </div>
              </div>
            </div>
          `);
        }

        // Handle the main audio player play/pause
        // Add a loading spinner until content is playing
        $('.playBtn').on('click', function() {
          let audioPlayer = document.getElementById('audioPlayer');
          audioPlayer.volume = 0.3;
    
          // Source may not yet be loaded
          if (!audioPlayer.src) {
            if (!$('#audioPlayerLabel').text()) {
              $('#audioPlayerLabel').html('<span class="p-3">No station loaded</span>') ;
            }
          }
    
          // Ability to play and pause from the main audio player, and reset all stream buttons
          else {
            try {
              if (audioPaused == 0) {
                audioPlayer.pause();
                $('.playBtn').attr('class','playBtn ps-3 fa fa-2x fa-play');
                $('.audioLoad').attr('class','audioLoad ps-2 fa fa-play');
                audioPaused = 1;

              } else {
                playAudio()
                $('.playBtn').attr('class','playBtn ps-3 fa fa-2x fa-pause');
                $(`.audioLoad[value="${audioPlayer.src}"]`).attr('class','audioLoad ps-2 fa fa-pause');
                audioPaused = 0;
              }
            }
            // Perhaps something unexpected could go occur; info, warning or error?
            catch (err) {
              appErrorLogger('RadioBrowser', 'info', `RadioBrowser - ${err}`)
            }
          }
        });


        // Implement controls for each stream
        // Nuance: clicking a audioLoad control when playing will only restart that stream, not pause it
        // Redesign this as a switch statement
        $('.audioLoad').on('click', function() {

          // If it's the same stream loaded, do play or pause
          // Using a switch statement should improve this
          if ($(`.audioLoad[value="${audioPlayer.src}"]`) == $(this).attr('value')) {
          
            if (audioPaused == 0) {
              audioPlayer.pause();
              $('.playBtn').attr('class','playBtn ps-3 fa fa-2x fa-play')
              $(this).attr('class','audioLoad ps-2 fa fa-play')
              audioPaused = 1;
            } 
            else {
              playAudio()
              $('.playBtn').attr('class','playBtn ps-3 fa fa-2x fa-pause');
              $('#audioPlayerLabel').html(`<span class="p-3 fw-bold">${$(this).attr('name')}</span>`);
              $(this).attr('class','audioLoad ps-2 fa fa-pause')
              audioPaused = 0;
            }
          }
          else {
            // If its a different stream, pause audio, reset all buttons
            audioPlayer.pause();
            $('.playBtn').attr('class','playBtn ps-3 fa fa-2x fa-play')
            $(`.audioLoad[value="${audioPlayer.src}"]`).attr('class','audioLoad ps-2 fa fa-play')

            // Play the new stream
            audioPlayer.volume = 0.3;
            audioPlayer.src = $(this).attr('value');
            playAudio()
            $('.playBtn').attr('class','playBtn ps-3 fa fa-2x fa-pause')
            $('#audioPlayerLabel').html(`<span class="p-3">${$(this).attr('name')}</span>`);
            $(this).attr('class','audioLoad ps-2 fa fa-pause')
            audioPaused = 0;
          }
        });

        // Functionality for the volume control
        const volumeSlider = document.getElementById("volume-slider");
        volumeSlider.addEventListener("input", () => {
          audioPlayer.volume = volumeSlider.value;
        });
      }
    }
}



// ----------------------------------------------------
// RADIO BROWSER API CALL

async function callRadioBrowserApi(cca2) {
    let errorMessage;

    return $.ajax({
      url: "../gazetteer/libs/php/radio_browser_api.php",
      method: 'POST',
      dataType: 'json',
      data: {
        cca2: cca2
      },
      success: function(result) {
        console.log(JSON.stringify(result));

        if (result['status']['code'] != "200") {
          if (!result['status']['message']) {
            result['status']['message'] = "There's been an unexpected problem but it will be investigated";
          }
          errorMessage = result['status']['message'];
          displayErrorPage('#radioGrid',errorMessage);
        }
      
      },
      error: function(jqXHR, textStatus, errorThrown) {
          appErrorLogger('Radio Browser Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
      }
    });
}




// ----------------------------------------------------
// PRESENT CURRENTS API (NEWS) DATA

function presentNewsData(newsData,countryName){

    // Update the modal heading
    let modalHeader = document.getElementById('newsModalTitle');
    modalHeader.innerHTML = `News Stories for ${countryName}`;

    // Display the modal error message if there is one
    if (newsData['status']['message'] && newsData['data'] == null) {
      displayErrorPage('#newsData',newsData['status']['message']);
    }

    // Sometimes countries do not have news articles returned from the api
    else if (!newsData['status']['message'] && newsData['data'] != null) {
      if (!newsData['data']['news'].length) {
        // Avoid potential duplicated message
        clearGrid('newsGrid');
        $('#newsGrid').append(`<div class="noneFound">There appears to be no news articles for ${countryName} at this time</div>`);
      }
      else {
        // Clear the presented data on navigating away
        $('#newsModal').on('hidden.bs.modal', () => {
          clearGrid('newsGrid');
        })

        for (let row in newsData['data']['news']) {
          row = newsData['data']['news'][row];

          $('#newsGrid').append(`<div class="row pt-2 mt-1"><div class="col"><h5>${row['title']}</h5></div></div>`);
          if (row['image'] !== 'None') {
            $('#newsGrid').append(`<div class="row"><div class="col"><img class='img-sm float-start p-1' src='${row['image']}'/><p>${row['description']}</p></div></div>`);
          } 
          else {
          $('#newsGrid').append(`<div class="row"><div class="col"><p>${row['description']}</p></div></div>`);
          }
          row['url'] ? 
          $('#newsGrid').append(`<div class="row">
            <div class="col-6"><a href="${row['url']}" target="_blank">Source <i class="fa fa-external-link"></i></a></div>
            <div class="col-6 text-end">${row['pub_fmt']}</div>
          </div>`)
          :
          $('#newsGrid').append(`<div class="row pb-2 mb-1">
            <div class="col text-end">${row['pub_fmt']}</div>
          </div>`);
          }
        }
    }
}


// ----------------------------------------------------
// CURRENTS API CALL (NEWS)

async function callCurrentsApi(cca2,category,countryName) {
    let errorMessage;

    return $.ajax({
    url: "../gazetteer/libs/php/currents_news_api.php",
    method: 'POST',
    dataType: 'json',
    data: {
      cca2: cca2.toLowerCase(),
      category: category
    },
    success: function(result) {
      console.log(JSON.stringify(result));      

      if (result['status']['code'] != "200") {
        if (!result['status']['message']) {
          result['status']['message'] = "There's been an unexpected problem but it will be investigated";
        }
        errorMessage = result['status']['message'];
        displayErrorPage('#newsGrid',errorMessage);
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
        appErrorLogger('CurrentsAPI Ajax', 'error', `${jqXHR}, ${textStatus}, ${errorThrown}`)
    }
  });
}




// ----------------------------------------------------
// UTILITY: SEND A LOG MESSAGE

async function appErrorLogger(source, status, message) {
  let errorMessage;
  //console.log("error:", source,status,message);

  return $.ajax({
    url: '../gazetteer/libs/php/app_logger.php',
    method: 'POST',
    dataType: 'json',
    data: {
       'source': source, 
       'status': status,
       'message': message
    },
    success: function(result) {
      console.log(JSON.stringify(result));

      // AppErrorLogger was successful
      // In production this should not log to the console
      // Error - supplied message is logged and a generic error message presented
      // Warning - supplied message is logged and also presented
      // Info - supplied message is logged but not presented
      if (result['status']['code'] == "200") {
        if (result['data']['status'] == 'error') {
          displayErrorPage('body',errorMessage="Unforeseen technical issues have occurred");
        }
        if (result['data']['status'] == 'warning') {
          errorMessage = result['data']['message'];
          displayErrorPage('body',errorMessage);
        }
      }
      else {
        // There's been a problem with appErrorLogger
        displayErrorPage('body',errorMessage="There's been an unexpected problem but it will be rectified")
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      // throw new Error();
      displayErrorPage('body',errorMessage="Unexplained technical issues have occurred");
    }
  });
}



// ----------------------------------------------------
// DISPLAY ERROR PAGE
// Either full page or just a modal

function displayErrorPage(component,errorMessage) {
  if (errorMessage == null) {
    errorMessage = "Oh no, there's something not quite right"
  }

  if (component === 'body') {
    $('body').html(`
    <div id="errorStyle" class="container mt-2 mb-2 pt-2 pb-2 text-center">
      <h1 class="pt-5">&#x1F615;</h1>
        <h5 class="pb-3">There's been a problem</h5>
        <p class="p-3 fw-bold">${errorMessage}</p>
      <p>If the issue persists, please make <a href='#'>contact</a></p>
      <a class="focusButton" href='/'><div class="button1 buttonShine mx-auto">BACK TO HOMEPAGE</div></a>
    </div>
    `);
  }

  else {
    $(component).html(`
    <div id="errorStyle" class="container mt-2 mb-2 pt-1 pb-2 text-center">
      <h1 class="pt-5">&#x1F615;</h1>
        <h5 class="pb-3">There's been a problem</h5>
        <p class="p-3 fw-bold">${errorMessage}</p>
      <p>If the issue persists, please make <a href='#'>contact</a></p>
    </div>
    `);
  }

}




// ----------------------------------------------------
// CONTROL CODE

function removeLoader(){
  $("#loadingDiv" ).fadeOut(500, function() {
    $('.load-dialog').text('')
    $("#loadingDiv" ).remove();
});  
}

async function trigger(func) {
  $('body').append('<div id="loadingDiv"><div class="loader">Loading...</div><div class="load-dialog"></div></div>');
  $('.load-dialog').text('Taking flight...');
  await func;
  removeLoader();
}

trigger(init());


