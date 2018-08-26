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
										<!-- BeR 7Mar18: adding bandwidthTX consumed column -->
										<th data-sortable="true">Tx bandwidth used (MB)</th>
										<!-- BeR 8Mar18: adding bandwidthRX consumed column -->
										<th data-sortable="true">Rx bandwidth used (MB)</th>						
										<!-- BeR: changing column name from "OtherFlightFailures" to "Failures identified" -->							
										<th data-sortable="true">Failures identified </th>
									</thead>
									<tbody>
										<?php
											function str_replace_json($search, $replace, $subject) 
											{
												return json_decode(str_replace($search, $replace, json_encode($subject)), true);
											}
														
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
													//BeR 30Mar18: calling display for Ka connectivity full log table
													//echo "<td><a href=\"connectivityFlightData.php?aircraftId=$aircraftId&start=$altitudeStart&end=$altitudeEnd\">" . date('Y-m-d', strtotime($document['startTime'])) . "</a></td>";
													//echo "<td><a href=\"ConnectivityFlightData.php?aircraftId=$aircraftId&start=$altitudeStart&end=$altitudeEnd\">" . date('Y-m-d', strtotime($document['startTime'])) . "</a></td>";
												echo "<td><a href=\"ka_ConnectivityFlightData.php?aircraftId=$aircraftId&start=$altitudeStart&end=$altitudeEnd&startDate=$startDate&endDate=$endDate&airlineId=$airlineId&tailsign=$tailsign&conStatusVisited=true\">" . date('Y-m-d', strtotime($document['startTime'])) . "</a></td>";
			
													echo "<td>".$document['startTime']."</td>";
			
													echo "<td>$altitudeEnd</td>";
			
													echo "<td>$aircraftTailsign</td>";
			
													echo "<td>" . $flightNumber . "</td>";
			
													//BeR 3Apr18: using cityPair fields to display Ka map
													//echo "<td>$cityPair</td>";
													//echo "<td><a href=\"flightConnectivityMap_Ka.php?aircraftId=$aircraftId&start=$altitudeStart&end=$altitudeEnd\">" . $cityPair . "</a></td>";
													echo "<td>" . $cityPair . "</td>";
												//}
												
												echo "<td>$duration</td>";
												echo "<td class=\"$wifiClass\">" . $wifiAvailability . "% $displayComputedWifiAvailability</td>";
												
												//BeR 7Mar18: adding result of TX bandwidth used. Convert from kb to mb and round result
												$TXbandwidth = round( (($document["bandwidthTX"])*0.001) );
												echo "<td>".$TXbandwidth."</td>";
												//BeR 8Mar18: adding result of RX bandwidth used. Convert from kb to mb and round result
												$RXbandwidth = round( (($document["bandwidthRX"])*0.001) );
												echo "<td>".$RXbandwidth."</td>";									
																					
												$flightFailuredata = $document["flightFailure"];
												//echo $flightFailuredata;
												echo "<td>".$flightFailuredata."</td>";									
												echo "</tr>";
											}
										?>
									</tbody>					
								</table>