<table data-classes="table table-no-bordered" data-toggle="table" data-pagination="true" data-page-list="[10, 25, 50, 100, All]" data-page-size="25" data-search="true" data-search-align="left"  data-export-data-type= "all" data-height="auto" data-show-export="true">
									<thead>
										<th data-sortable="true">Flight Date</th>
										<th data-sortable="true">Above 10k ft starts</th>
										<th data-sortable="true">Below 10k ft stops</th>
										<th data-sortable="true">Tailsign</th>							
										<th data-sortable="true">Flight #</th>
										<th data-sortable="true">City Pair</th>
										<th data-sortable="true">Duration >10K</th>
										<th data-sortable="true">Wifi Availability</th>
										<!-- BeR: removing Wifi root cause column as all failure are now displayed in Failures identified column -->
										<!--
										<th>Wifi Root Cause</th>
										-->
										<th data-sortable="true">OMTS Availability</th>
										<!-- BeR: removing OMTS root cause column as all failure are now displayed in Failures identified column -->
										<!--
										<th>OMTS Root Cause</th>
										-->
										<!-- BeR: changing column name from "OtherFlightFailures" to "Failures identified" -->
										<th data-sortable="true">Failures identified </th>
									</thead>
									<tbody>
										<?php
											function str_replace_json($search, $replace, $subject) 
											{
												return json_decode(str_replace($search, $replace, json_encode($subject)), true);
											}
											if (isset ( $cursor )) {
											foreach ($cursor as $document) {
												//Get Wifi Availability On Percentage from MongoDB
												$computedWifiAvailability = round($document["wifiAvailability"]["totalOnPercentage"],2);
												$manualWifiAvailability = round($document["wifiAvailability"]["manualPercentageOn"],2);
												if($manualWifiAvailability != "") {
													$wifiAvailability = $manualWifiAvailability;
													$displayComputedWifiAvailability = "[$computedWifiAvailability%]";
												} else {
													$wifiAvailability = $computedWifiAvailability;
													$displayComputedWifiAvailability = "";
												}
												
												if($wifiAvailability >= 90) {
													$wifiClass = "success";
												} else if($wifiAvailability >= 80) {
													$wifiClass = "warning";
												} else {
													$wifiClass = "danger";
												}
												
												//Get Omts Availability On Percentage from MongoDB
												$computedOmtsAvailability = round($document["omtsAvailability"]["totalOnPercentage"],2);
												$manualOmtsAvailability = round($document["omtsAvailability"]["manualPercentageOn"],2);
												if($manualOmtsAvailability != "") {
													$omtsAvailability = $manualOmtsAvailability;
													$displayComputedOmtsAvailability = "[$computedOmtsAvailability%]";
												} else {
													$omtsAvailability = $computedOmtsAvailability;
													$displayComputedOmtsAvailability = "";
												}
			
												if($omtsAvailability >= 90) {
													$omtsClass = "success";
												} else if($omtsAvailability >= 80) {
													$omtsClass = "warning";
												} else {
													$omtsClass = "danger";
												}
												
												//Get City Pair On Percentage from MongoDB
												$cityPair = $document["cityPair"];	
												
												//get flight number from flight leg details from Mysql DB
												//$flightNumber = getFlightNumber($document["idFlightLeg"],$dbConnection,$dbName);
												
												//flightNumber from MongoDB
												$flightNumber = $document["flightNumber"];
												
												//SB:Code added to get aircraftId as per the timestamp.
												$idFlightLeg = getFlightLegId($document["startTime"],$dbConnection,$dbName);
												
												// compute duration above 10k
												$altitudeStart = $document['altitudeEvent']['startTime'];
												$altitudeEnd = $document['altitudeEvent']['endTime'];
												$duration = dateDifference($altitudeStart, $altitudeEnd, '%hh %I\' %S\'\'');
												
												$id = $document["_id"];
												
												//update document
												if($idFlightLeg !=""){
													$queryCriteria = array('_id' => array('$eq' => $id));
													$options = array('upsert' => true);
													$res = $collection->update($queryCriteria,
																		array('$set'=> array('idFlightLeg'=>$idFlightLeg)),
																		$options);	
												}
												
												echo "<tr>";
												//BeR: not writing into test column anymore												
												//SB:Code added for flightLeg
												if($idFlightLeg != ""){
													echo "<td><a href=\"ConnectivityFlightData.php?aircraftId=$aircraftId&flightLegs=$idFlightLeg&startDate=$startDate&endDate=$endDate&airlineId=$airlineId&tailsign=$tailsign&conStatusVisited=true\">" . date('Y-m-d', strtotime($document['startTime'])) . "</a></td>";
													echo "<td>".$document['startTime']."</td>";
													echo "<td>$altitudeEnd</td>";
													echo "<td>$aircraftTailsign</td>";
													echo "<td>" . $flightNumber . "</td>";
													echo "<td>$cityPair</td>";
												}else{
												//send aircraftid,starttime,endtime,id
													echo "<td><a href=\"ConnectivityFlightData.php?aircraftId=$aircraftId&start=$altitudeStart&end=$altitudeEnd&startDate=$startDate&endDate=$endDate&airlineId=$airlineId&tailsign=$tailsign&conStatusVisited=true\">" . date('Y-m-d', strtotime($document['startTime'])) . "</a></td>";
													echo "<td>".$document['startTime']."</td>";
													echo "<td>$altitudeEnd</td>";
													echo "<td>$aircraftTailsign</td>";
													echo "<td>" . $flightNumber . "</td>";
													echo "<td>$cityPair</td>";
												}											
											
												echo "<td>$duration</td>";
												echo "<td class=\"$wifiClass\">" . $wifiAvailability . "% $displayComputedWifiAvailability</td>";
												
												echo "<td class=\"$omtsClass\">" . $omtsAvailability . "% $displayComputedOmtsAvailability</td>";
												
												$flightFailuredata = $document["flightFailure"];
												//echo $flightFailuredata;
												echo "<td>".$flightFailuredata."</td>";									
												echo "</tr>";
											}
                                           }
										?>
									</tbody>					
								</table>