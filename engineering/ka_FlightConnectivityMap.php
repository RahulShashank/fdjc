
			<?php
				if($displayNoMapDataAlert) {
					echo "<div class=\"alert alert-warning\" role=\"alert\"  style=\"height: 45px;\">
						  <span class=\"glyphicon glyphicon-exclamation-sign\" aria-hidden=\"true\"></span>
						  <span class=\"sr-only\">Error:</span>
						  No connectivity data has been uploaded for this flight.
						</div>
						<br><br>";
				} else {
					echo "<div id=\"map\" style=\"height: 800px\"></div>";
				}
			?>			
				<script>
					//var map = L.map('map').setView([51.505, -0.09], 13);
					<?php
						echo "var departureAirportCode = '$departureAirportCode';\n";
						echo "var departureAirportName = '$departureAirportName';\n";
						echo "var departureAirportCity = '$departureAirportCity';\n";
						echo "var departureAirportLat = '$departureAirportLat';\n";
						echo "var departureAirportLong = '$departureAirportLong';\n";
						echo "var departureAirportElevation = '$departureAirportElevation';\n";

						echo "var arrivalAirportCode = '$arrivalAirportCode';";
						echo "var arrivalAirportName = '$arrivalAirportName';\n";
						echo "var arrivalAirportCity = '$arrivalAirportCity';\n";
						echo "var arrivalAirportLat = '$arrivalAirportLat';\n";
						echo "var arrivalAirportLong = '$arrivalAirportLong';\n";
						echo "var arrivalAirportElevation = '$arrivalAirportElevation';\n";
						
						echo "var firstLat = '$firstLatitude';\n";
						echo "var firstLong = '$firstLongitude';\n";
						echo "var lastLat = '$lastLatitude';\n";
						echo "var lastLong = '$lastLongitude';\n";
					?>
										
					var map = L.map('map').fitBounds(
												[
													[firstLat, firstLong],
													[lastLat, lastLong]
												],
												{padding: [20,20]}
											);

					// var mapUrl = 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6IjZjNmRjNzk3ZmE2MTcwOTEwMGY0MzU3YjUzOWFmNWZhIn0.Y8bhBaUMqFiPrDRW9hieoQ';
					//var mapUrl = 'http://otile4.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png';
					var mapUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

					L.tileLayer(mapUrl, {
						maxZoom: 18,
						id: 'mapbox.streets'
					}).addTo(map);

					// Google style icons
					var departureIcon = L.icon({
						iconUrl: '../img/departure.png',
						shadowUrl: '../img/marker-shadow.png',
						iconAnchor: [15,50],
						popupAnchor:  [2, -40]
					});

					var arrivalIcon = L.icon({
						iconUrl: '../img/arrival.png',
						shadowUrl: '../img/marker-shadow.png',
						iconAnchor: [15,50],
						popupAnchor:  [2, -40]
					});

					if( (departureAirportLat != '') && (departureAirportLong != '') ) {
						L.marker(
								[
									departureAirportLat, 
									departureAirportLong
								],
								{icon: departureIcon}
							)
							.addTo(map)
							.bindPopup(
								"<b>Code</b>: " + departureAirportCode + "<br>" +
								"<b>City</b>: " + departureAirportCity + "<br>" +
								"<b>Airport Name</b>: " + departureAirportName + "<br>" +
								
								"<br><b>Latitude</b>: " + departureAirportLat + " / " + degToDms(departureAirportLat) + "<br>" +
								"<b>longitude</b>: " + departureAirportLong + " / " + degToDms(departureAirportLong, true) + "<br>" +
								"<b>Elevation</b>: " + departureAirportElevation + "ft / " + Math.round(convertFeetToMeters(departureAirportElevation)) + "m<br>" +

								"<br>This the <u>DEPARTURE</u> airport."
							);
					}
					
					if( (arrivalAirportLat != '') && (arrivalAirportLong != '') ) {
						L.marker(
								[
									arrivalAirportLat, 
									arrivalAirportLong
								]
								,
								{icon: arrivalIcon}
							).addTo(map)
							.bindPopup(
								"<b>Code</b>: " + arrivalAirportCode + "<br>" +
								"<b>City</b>: " + arrivalAirportCity + "<br>" +
								"<b>Airport Name</b>: " + arrivalAirportName + "<br>" +
								
								"<br><b>Latitude</b>: " + arrivalAirportLat + " / " + degToDms(arrivalAirportLat) + "<br>" +
								"<b>longitude</b>: " + arrivalAirportLong + " / " + degToDms(arrivalAirportLong, true) + "<br>" +
								"<b>Elevation</b>: " + arrivalAirportElevation + "ft / " + Math.round(convertFeetToMeters(arrivalAirportElevation)) + "m<br>" +

								"<br>This the <u>ARRIVAL</u> airport."
							);
					}

					


					// Trajectory

					var trajectory = <?php echo $trajectory; ?>;

					function onEachTrajectoryFeature(feature, layer) {
						// console.log(onEachTrajectoryFeature);
						// does this feature have a property named popupContent?
						if (feature.properties && feature.properties.services) {
							var htmlCode = "<table>";

							var services = feature.properties.services;
							var previousServiceStatus= "";
							for (var i = 0; i < services.length; i++) {
								var service = services[i];
								// console.log(service.service + " - " + service.status);
								var serviceName = service.service;
								var serviceStatus = service.status;

								var fontColor;

								start = service.start;
								end = service.end;
									
								rootCause = "";
								if(serviceStatus == "ON") {
									fontColor = "#6BB658";
									time = "<tr><td><b>Time:</b></td><td>" + start + "</td></tr>";
									connectivityLink = "";
								} else if(serviceStatus == "OFF") {
									fontColor = "#BE3E14";
									rootCause = "<tr><td><b>Root Cause:</b></td><td><span style='color:#BE3E14'>" + service.rootCause  + "</span></td></tr>";									
									duration = service.duration;
									time = "<tr><td><b>Start:</b></td><td>" + start + "</td></tr><tr><td><b>End:</b></td><td>" + end + "</td></tr><tr><td><b>Total:</b></td><td>" + duration + "</td>";
									connectivityLink = "<tr><td colspan='2'><a class='serviceLink' href='connectivityActivityView.php?aircraftId=<?php echo $aircraftId; ?>&flightLegs=<?php echo $flightLegs; ?>&start=" + start + "&end=" + end +  "' target='_blank'>>> See logs</a></td>"
								} else {
									fontColor = "Grey";
									time = "<tr><td><b>Time:</b></td><td>" + start + "</td></tr>";
									connectivityLink = "";
								}
								
								// Pure cosmetic code
								if(htmlCode != '' && previousServiceStatus == "OFF") {
									htmlCode += "<tr><td colspan='2'><hr></td></tr>";
								}
								
								
								htmlCode += "<tr><td colspan='2' style=\"color:" + fontColor + "\"><img src=\"../img/"+ serviceName + "_" + serviceStatus + ".png\" style='vertical-align:bottom'/>&nbsp;&nbsp;&nbsp;<b>" + serviceName + " is " + serviceStatus + "</b></td></tr>" + rootCause + time + connectivityLink;
								
								previousServiceStatus = serviceStatus;
							}

							htmlCode += "</table>";

							layer.bindPopup(htmlCode);
						}
					}


					var trajectoryShadowLayerStyle = {
						"color": "#202020",
						"weight": 5,
						"opacity": 0.75
					};

					var trajectoryShadowLayer = L.geoJson(trajectory, {
						style: trajectoryShadowLayerStyle,
						onEachFeature: onEachTrajectoryFeature
					}).addTo(map);


					var trajectoryLayer = L.geoJson(trajectory, {
						style: function(feature) {
								if (feature.properties) {
									if(feature.properties.services) {
										var services = feature.properties.services;
										var totalServices = services.length;
										var servicesDisabledCount = 0;
										var servicesOnCount = 0;
										var servicesOffCount = 0;

										for (var i = 0; i < services.length; i++) {
											var service = services[i];
											var serviceStatus = service.status;

											switch(serviceStatus) {
												case 'DISABLED':
													servicesDisabledCount++;
													break;
												case 'ON':
													servicesOnCount++;
													break;
												case 'OFF':
													servicesOffCount++;
													break;
												default:
													break;
											}
										}

										if(servicesOnCount == totalServices) {
											return {color: "LawnGreen", opacity: "0.95", weight:3};
										} else if (servicesOffCount == totalServices) {
											return {color: "Red", opacity: "0.95", weight:3};
										} else if (servicesOffCount == totalServices / 2) {
											return {color: "DarkOrange", opacity: "0.95", weight:3};
										} else {
											return {color: "#FEFEFE", opacity: "0.95", weight:3};
										}
									}
								}
						},
						onEachFeature: onEachTrajectoryFeature
					}).addTo(map);

					
					// Event objects
					var eventServiceOn = L.icon({
						iconUrl: '../img/service_on.png',
						iconAnchor: [8,8],
						popupAnchor:  [2, -8]
					});
					
					var eventServiceOff = L.icon({
						iconUrl: '../img/service_off.png',
						iconAnchor: [8,8],
						popupAnchor:  [2, -8]
					});

					var eventsLayer = new L.featureGroup();
					
					<?php
						createMarkers($wifiOffEvents, $aircraftId, $flightLegs);
						createMarkers($omtsOffEvents, $aircraftId, $flightLegs);
						
						function createMarkers($events, $aircraftId, $flightLegs) {
							if(isset($events)){
								foreach($events as $event) {
									//var_dump($event); exit;
									$service = $event['service'];							
									
									// Create the start marker
									$start = $event['start'];
									$end = $event['end'];
									$rootCause = $event['rootCause'];
									$latitude = $event['startLatLong'][0];
									$longitude = $event['startLatLong'][1];
									
									echo "var marker =
										L.marker(
											[$longitude,$latitude],
											{icon: eventServiceOff}
										)									
										.bindPopup(
											\"<br><img src='../img/" . $service . "_OFF.png' style='vertical-align:bottom'/>&nbsp;&nbsp;<span style='color:#BE3E14'><b>$service is OFF</b></span><br><br><b>Time:</b> $start<br><br><b>Root Cause:</b> <span style='color:#BE3E14'>$rootCause</span><br><br><a class='serviceLink' href='connectivityActivityView.php?aircraftId=$aircraftId&flightLegs=$flightLegs&start=$start&end=$end' target='_blank'>>> See logs</a>\"
										);\n";
									
									echo "eventsLayer.addLayer(marker);";
									
									// Create the end marker
									$latitude = $event['endLatLong'][0];
									$longitude = $event['endLatLong'][1];
									
									echo "var marker =
										L.marker(
											[$longitude,$latitude],
											{icon: eventServiceOn}
										)									
										.bindPopup(
											\"<br><img src='../img/" . $service . "_ON.png' style='vertical-align:bottom'/>&nbsp;&nbsp;<span style='color:#6BB658'><b>$service is ON</b></span><br><br><b>Time:</b> $end\"
										);\n";
										
									echo "eventsLayer.addLayer(marker);";
								}
							}			
						}
					?>					
					
					eventsLayer.addTo(map);
					
					
					function onEachAreaFeature(feature, layer) {
						if (feature.properties) {
							layer.bindPopup("<br><b>Country:</b> " + feature.properties.name + "<br><br><b>Service:</b> " + feature.properties.service +  "<br><b>Status:</b> " + feature.properties.status);
						}
					}

					// OMTS Authorized Areas
					var omtsAuthorizedAreaStyle = {
						"color": "#33CC33",
						"weight": 1,
						"opacity": 0.75
					};

					var omtsAuthorizedAreaLayer = L.geoJson(omtsAuthorizedArea, {
							style: omtsAuthorizedAreaStyle,
							onEachFeature: onEachAreaFeature
						}
						).addTo(map);

					// OMTS Restricted Areas
					var omtsRestricatedAreaStyle = {
						"color": "red",
						"weight": 1,
						"opacity": 0.75
					};

					var omtsRestrictedAreaLayer = L.geoJson( omtsRestrictedArea, {
							style: omtsRestricatedAreaStyle,
							onEachFeature: onEachAreaFeature
						}
					).addTo(map);

					// Wifi Authorized Areas
					var wifiAuthorizedAreaStyle = {
						"color": "#33CC33",
						"weight": 1,
						"opacity": 0.75
					};

					var wifiAuthorizedAreaLayer = L.geoJson(wifiAuthorizedArea, {
							style: wifiAuthorizedAreaStyle,
							onEachFeature: onEachAreaFeature
						}
						).addTo(map);

					// Wifi Restricted Areas
					var wifiRestricatedAreaStyle = {
						"color": "red",
						"weight": 1,
						"opacity": 0.75
					};

					var wifiRestrictedAreaLayer = L.geoJson( wifiRestrictedArea, {
							style: wifiRestricatedAreaStyle,
							onEachFeature: onEachAreaFeature
						}
					).addTo(map);

					// Create layers controls

					// http://stackoverflow.com/questions/19545422/leaflet-geojason-layer-control-not-work-in-this-script
					var overlayMaps = {
						"&nbsp;<img src=\"../img/trajectory.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">Trajectory</span>": trajectoryLayer,
						"&nbsp;<img src=\"../img/warning.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">Connectivity Events</span>": eventsLayer,
						"&nbsp;<img src=\"../img/OMTS_ON.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">OMTS Auth. Area</span>": omtsAuthorizedAreaLayer,
						"&nbsp;<img src=\"../img/OMTS_OFF.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">OMTS Rest. Area</span>": omtsRestrictedAreaLayer,
						"&nbsp;<img src=\"../img/WIFI_ON.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">WIFI Auth. Area</span>": wifiAuthorizedAreaLayer,
						"&nbsp;<img src=\"../img/WIFI_OFF.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">WIFI Rest. Area</span>": wifiRestrictedAreaLayer						
					};

					L.control.layers(null, overlayMaps, {position: 'topleft'}).addTo(map);

					// Bring trajectory layer and events layer to the top as the areas layer have been added after.
					trajectoryLayer.bringToFront();
					eventsLayer.bringToFront();

					// Way to keep the layers ordered (especially to keep trajectory on top of all other layers)
					// We have to do the following otherwise there is an error when calling bringToFront() on layer which is currently not displayed
					map.on('overlayadd',function(layer){ 
						if(map.hasLayer(omtsAuthorizedAreaLayer)) {
							omtsAuthorizedAreaLayer.bringToFront();
						}
						if(map.hasLayer(omtsRestrictedAreaLayer)) {
							omtsRestrictedAreaLayer.bringToFront();
						}
						if(map.hasLayer(wifiAuthorizedAreaLayer)) {
							wifiAuthorizedAreaLayer.bringToFront();
						}
						if(map.hasLayer(wifiRestrictedAreaLayer)) {
							wifiRestrictedAreaLayer.bringToFront();
						}
						if(map.hasLayer(trajectoryLayer)) {
							// Show the shadow when trajectory is added to display
							map.addLayer(trajectoryShadowLayer);
							trajectoryShadowLayer.bringToFront();
							trajectoryLayer.bringToFront();
						}
						if(map.hasLayer(eventsLayer)) {
							//eventsLayer.bringToFront();
						}
					});

					map.on('overlayremove',function(layer){ 
						if(layer.name.indexOf("Trajectory") > 0) {
							// Hide the shadow when trajectory is removed from display
							map.removeLayer(trajectoryShadowLayer);
						}
					});




					// Popup to give coordinates
				    /*  	
					var popup = L.popup();

					function onMapClick(e) {
						popup
							.setLatLng(e.latlng)
							.setContent("You clicked the map at " + e.latlng.toString())
							.openOn(map);
					}

					map.on('click', onMapClick);
					*/


					// Utilities
					function convertFeetToMeters(feetValue) {
						return 0.3048 * feetValue;
					}

					function convertMetersToFeet(metersValue) {
						return 3.2808 * metersValue;
					}

					// http://stackoverflow.com/questions/5786025/decimal-degrees-to-degrees-minutes-and-seconds-in-javascript
					function degToDms (deg, lng) {
						var values = {
							dir : deg<0?lng?'W':'S':lng?'E':'N',
							deg : 0|(deg<0?deg=-deg:deg),
							min : 0|deg%1*60,
							sec :(0|deg*60%1*6000)/100
						};

						return ("" + values['deg'] + "°" + values['min'] + "'" + values['sec'] + "\" " + values['dir']);
					}
				</script>
        	
