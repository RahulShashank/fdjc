<?php
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

require_once "../database/connecti_mongoDB.php";
require_once "../common/functions.php";
require_once "../common/computeFleetStatusData.php";

$itemStyle = "font-family: Helvetica; font-size: 10px; text-align: left";
$systemResetSvduRatio = getSystemResetSvduRatio();
$flightPhases = getFlightPhases();


$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
$flightLegs = $_REQUEST['flightLegs'];

$displayFailures = $_REQUEST['failureTimeline'];
$displayFaults = $_REQUEST['faultsTimeline'];
$displayResets = $_REQUEST['resetsTimeline'];
$displayApplications = $_REQUEST['applicationsTimeline'];
$displayServices = $_REQUEST['servicesTimeline'];
$displayConnectivity = $_REQUEST['connectivityTimeline'];
$displayMaintenance = $_REQUEST['maintenanceTimeline'];
$statusForClimb = false;
if($aircraftId != '') {
	$query = "SELECT databaseName, platform FROM aircrafts a WHERE a.id = $aircraftId";
	$result = mysqli_query($dbConnection, $query);
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $dbName = $row['databaseName'];
        $platform = $row['platform'];

        $selected = mysqli_select_db($dbConnection, $dbName)
			or die("Could not select ".$dbName);
    } else {
         echo "<br>error: ".mysqli_error($dbhandle);
    }
} else {
	$selected = mysqli_select_db($dbConnection, $sqlDump)
			or die("Could not select ".$sqlDump);
}


if(isset($flightLegs)) {
	if(strpos($flightLegs, '-') > 0) {
		$leg1 = $type  = strtok($flightLegs, '-');
		$leg2 = $type  = strtok('-');
		$flightLegsCondition = " a.idFlightLeg BETWEEN $leg1 AND $leg2";
	} else if(strpos($flightLegs, ',') > 0) {
		$flightLegsCondition = " a.idFlightLeg in ($flightLegs)";
	} else {
		$flightLegsCondition = " a.idFlightLeg = $flightLegs";
	}
}

$monitorState = "1,3";


if($aircraftId != '') {
	if(isset($flightLegs)) {
		$query = "SELECT createDate 
					FROM SYS_flight a
					WHERE $flightLegsCondition
					ORDER BY lastUpdate 
					LIMIT 1";
		$result = mysqli_query($dbConnection, $query);
	    if($result != null) { // i5000 doesn't have this table -> to be checked with XML
	        $row = mysqli_fetch_array($result);
	        $startDateTime = $row['createDate'];
	    }

		$query = "SELECT lastUpdate 
					FROM SYS_flight  a
					WHERE $flightLegsCondition
					ORDER BY lastUpdate DESC
					LIMIT 1";
		$result = mysqli_query($dbConnection, $query);
	    if($result != null) { // i5000 doesn't have this table -> to be checked with XML
	        $row = mysqli_fetch_array($result);
	        $endDateTime = $row['lastUpdate'];
	    }
	} else {
		$query = "SELECT lastUpdate 
					FROM SYS_flight 
					ORDER BY lastUpdate 
					DESC LIMIT 1";
		$result = mysqli_query($dbConnection, $query);
	    if($result != null) { // i5000 doesn't have this table -> to be checked with XML
	        $row = mysqli_fetch_array($result);
	        $maxDateTime = $row['lastUpdate'];
	    }
	}
} else {
	// for a dump we don't have an end date from the SYS_flight table
	// one way to get an end date is to get the max date of events from failures, faults, ext_app or event tables
	$query = "SELECT MAX(correlationDate) AS lastFailureTime FROM BIT_failure";
	$result = mysqli_query($dbConnection, $query);
    if($result != null) {
        $row = mysqli_fetch_array($result);
        $lastFailureTime = strtotime($row['lastFailureTime']);
    }

	$query = "SELECT MAX(detectionTime) AS lastFaultTime FROM BIT_fault";
	$result = mysqli_query($dbConnection, $query);
    if($result != null) {
        $row = mysqli_fetch_array($result);
        $lastFaultTime = strtotime($row['lastFaultTime']);
    }

	$query = "SELECT MAX(lastUpdate) AS lastEventTime FROM BIT_event";
	$result = mysqli_query($dbConnection, $query);
    if($result != null) {
        $row = mysqli_fetch_array($result);
        $lastEventTime = strtotime($row['lastEventTime']);
    }

	$query = "SELECT MAX(detectionTime) AS lastExtAppEventTime FROM BIT_extAppEvent";
	$result = mysqli_query($dbConnection, $query);
    if($result != null) {
        $row = mysqli_fetch_array($result);
        $lastExtAppEventTime = strtotime($row['lastExtAppEventTime']);
    }

    $maxTime = max($lastFailureTime, $lastFaultTime, $lastEventTime, $lastExtAppEventTime);
    $maxDateTime = date("Y-m-d H:i:s", $maxTime);
}


if( isset($endDateTime) ) {
	$endDateTime = strtotime($endDateTime);
    $endDateTime = date("Y-m-d H:i:s", $endDateTime); 
} else {
	$endDateTime = $maxDateTime;
}
$timelineEndDateTime = date("Y-m-d H:i", strtotime("+1 hours", strtotime($endDateTime)));

if( isset($startDateTime) ) {
	$starDateTime = strtotime($starDateTime);
    $starDateTime = date("Y-m-d H:i:s", $starDateTime);
} else {
	$startDateTime = strtotime("-$aircratTimelineDuration days", strtotime($endDateTime));
	$startDateTime = date("Y-m-d H:i", $startDateTime);
}
$timelineStartDateTime = date("Y-m-d H:i", strtotime("-1 hours", strtotime($startDateTime))); 


// Options data - start and end time values are for the date picker fields
$options = 
	array(
    	'start' => "$timelineStartDateTime", 
    	'end' => "$timelineEndDateTime", 
    	'min' => "$timelineStartDateTime", 
    	'max' => "$timelineEndDateTime"
	);


// Groups data common to every timeline
$groups = array (
	array(
    	'id' => 'Open', 
    	'content' => "<i class=\"fa fa-sign-in fa-fw\" aria-hidden=\"true\"></i><br><strong>Open flight legs</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    ),
	array(
    	'id' => 'Closed', 
    	'content' => "<i class=\"fa fa-sign-out fa-fw\" aria-hidden=\"true\"></i><br><strong>Closed flight legs</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    )
);

if(!$displayMaintenance) {
	$groups[] = array(
		'id' => 'FlightPhases', 
		'content' => "<i class=\"fa fa-plane fa-fw\" aria-hidden=\"true\"></i><br><strong>Flight Phases</strong>",
		'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
		'style' => 'font-weight: bold; text-align: center'
	);
	$groups[] = array(
		'id' => 'Redundancy', 
		'content' => "<i class=\"fa fa-server fa-fw\" aria-hidden=\"true\"></i><br><strong>Redundancy</strong>",
		'style' => 'font-weight: bold; text-align: center'
	);
	$groups[] = array(
		'id' => 'SystemPower', 
		'content' => "<i class=\"fa fa-bolt fa-fw\" aria-hidden=\"true\"></i><br><strong>System Power</strong>",
		'style' => 'font-weight: bold; text-align: center'
	);
}

if($displayConnectivity) {

    $groups[] = array(
    	'id' => 'Altitude', 
    	'content' => "<img src=\"../img/altitude.png\" width=\"16px\" height=\"16px\"/><br><strong>Altitude</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    );	
	$groups[] = array(
    	'id' => 'Restricted Area', 
    	'content' => "<img src=\"../img/restrictedArea.png\" width=\"16px\" height=\"16px\"/><br><strong>Wifi Restricted Area</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    );
	
    $groups[] = array(
    	'id' => 'WIFI', 
    	'content' => "<img src=\"../img/wifi.png\" width=\"16px\" height=\"16px\"/><br><strong>WIFI-ON</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    );	
	$groups[] = array(
    	'id' => 'OMTS Restricted Area', 
    	'content' => "<img src=\"../img/restrictedArea.png\" width=\"16px\" height=\"16px\"/><br><strong>OMTS Restricted Area</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    );	
    $groups[] = array(
    	'id' => 'OMTS', 
    	'content' => "<img src=\"../img/gsm.png\" width=\"16px\" height=\"16px\"/><br><strong>OMTS Availability</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    );	
	$groups[] = array(
    	'id' => 'DSU23', 
    	'content' => "<img src=\"../img/dsu.png\"/><br><strong>DSU23</strong>",
    	'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
    	'style' => 'font-weight: bold; text-align: center'
    );
	$groups[] = array(
    	'id' => 'SDU', 
    	'content' => "<img src=\"../img/dsu.png\"/><br><strong>SDU</strong>",
    	'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
    	'style' => 'font-weight: bold; text-align: center'
    );
    $groups[] = array(
    	'id' => 'NCU', 
    	'content' => "<img src=\"../img/dsu.png\"/><br><strong>NCU</strong>",
    	'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
    	'style' => 'font-weight: bold; text-align: center'
    );
	$groups[] = array(
    	'id' => 'BTS1', 
    	'content' => "<img src=\"../img/dsu.png\"/><br><strong>BTS 1</strong>",
    	'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
    	'style' => 'font-weight: bold; text-align: center'
    );
	$groups[] = array(
    	'id' => 'BTS2', 
    	'content' => "<img src=\"../img/dsu.png\"/><br><strong>BTS 2</strong>",
    	'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
    	'style' => 'font-weight: bold; text-align: center'
    );
    $groups[] = array(
    	'id' => 'CWLU', 
    	'content' => "<img src=\"../img/adbg.png\" width=\"16px\" height=\"16px\"/><br><strong>CWLU</strong>",
    	'subgroupOrder' => 'function (a,b) {return a.subgroupOrder - b.subgroupOrder;}',
    	'style' => 'font-weight: bold; text-align: center'
    );
}

if($displayMaintenance) {
	$groups[] = array(
    	'id' => 'Maintenance', 
    	'content' => "<i class=\"fa fa-wrench fa-fw\" aria-hidden=\"true\"></i><br><strong>Maintenance</strong>",
    	'style' => 'font-weight: bold; text-align: center'
    );
}


$addedHostnames = array();


$query = "SELECT * FROM SYS_flight a
		WHERE createDate >= '$startDateTime'
		AND lastUpdate <= '$endDateTime'";

if(isset($flightLegsCondition)) {
	$query .= " AND $flightLegsCondition";
}

// execute the SQL query and return records
$result = mysqli_query($dbConnection, $query);
$dataItems = array();
if($result) {
	if(mysqli_num_rows($result) > 0) {
		$i=0;
		$keepLooping = true;
		$row = mysqli_fetch_array($result); // get first flight leg

		while($keepLooping) {
			$id = $row['idFlightLeg'];
			$flightNumber = $row['flightNumber'];
			$departureAirportCode = $row['departureAirportCode'];
			$arrivalAirportCode = $row['arrivalAirportCode'];
			$content = "$id - $flightNumber - $departureAirportCode - $arrivalAirportCode";
			$start = $row['createDate'];

			if($aircraftId != '') {
				$endFlightLeg = $row{'lastUpdate'};
			} else {
				// for db, we need to get the createDate of the next flight leg in order to get the end date
				$nextRow = mysqli_fetch_array($result);
				if($nextRow) {
					$endFlightLeg = $nextRow['createDate'];
				} else {
					// we have reached the last flight leg
					// there are two cases: the
					$query = "SELECT createDate FROM SYS_flight WHERE idFlightLeg = $id+1";
				    $result = mysqli_query($dbConnection, $query);
				    if($result && mysqli_num_rows($result) > 0) {
				        $row = mysqli_fetch_array($result);
				        $endFlightLeg = $row['createDate'];
				    } else {
				    	// it is the very last flight leg of the database
				    	$endFlightLeg = $maxDateTime;
				    }

				    $keepLooping = false;
				}
			}

			$flightLegName = $row{'flightLeg'};
			$duration = dateDifference($start, $endFlightLeg, '%h Hours %i Minute %s Seconds');
			$title = "$id - $flightLegName - $flightNumber - $departureAirportCode - $arrivalAirportCode / $start --> $endFlightLeg / $duration";
			
			if(strpos($flightLegName, 'CL') === 0) {
				$group = 'Closed';
				// $class = 'closed';
			} else {
				$group = 'Open';
				// $class = 'open';
			}

			$class = 'closed';
			
			// get corresponding flight phases
			$query2 = "SELECT * FROM $dbName.SYS_flightPhase WHERE idFlightLeg = $id ORDER BY startTime";
			$result2 = mysqli_query($dbConnection, $query2);
			  $cnt = 0;
			if($result2) { // not every dump has the SYS_flightphase table
				while ($row2 = mysqli_fetch_array($result2)) {
                  
					$idFlightPhase = $row2['idFlightPhase'];
					$contentFlightPhase = getFlightPhaseDesc($idFlightPhase) . " [$idFlightPhase]";
					$startFlightPhase = $row2[ 'startTime'];
					$endFlightPhase = $row2['endTime'];
					$subgroupFlightPhase = getFlightPhaseOrder($idFlightPhase);
					$durationFlightPhase = dateDifference($startFlightPhase, $endFlightPhase, '%h Hours %i Minute %s Seconds');
					$titleFlightPhase = "$contentFlightPhase / $startFlightPhase --> $endFlightPhase / $durationFlightPhase";
					
					$dataItems[] = array(
						'group' => "FlightPhases",
						'subgroup' => "$subgroupFlightPhase", 
						'content' => "$contentFlightPhase", 
						'title' => "$titleFlightPhase", 
						'start' => "$startFlightPhase", 
						'end' => "$endFlightPhase",
						'style' => "$itemStyle"
					);

					if($sqlDump == ''){ //Don't compute flightStatus for sqldumps
						if($idFlightPhase == 5 || $idFlightPhase == 4) {
							// it is a real flight so let's compute its status
							if($aircraftId != '') {
								$status = getFlightStatus($dbName, $id, $platform);
							} else {
								$status = 0;
							}
							
							if($status == 0) {
								$class = 'statusOK';
							} else if($status == 1) {
								$class = 'statusWarning';
							} else if($status > 1) {
								$class = 'statusAlert';
							}
                            $cnt++;
						}
                     /*   elseif($platform == 'i5000' || $platform == 'i8000'){
                            if($idFlightPhase == 4) {
                                $statusForClimb = true;
                              
                                if($aircraftId != '') {
                                    $status = getFlightStatus($dbName, $id, $platform);
                                } else {
                                    $status = 0;
                                }

                                if($status == 0) {
                                    $class = 'statusOK';
                                } else if($status == 1) {
                                    $class = 'statusWarning';
                                } else if($status > 1) {
                                    $class = 'statusAlert';
                                }
                            }
                        }*/
					}
				 }
                 if($cnt == 1){
                            $statusForClimb = true;
                 }
				$dataItems[] = array(
					'className' => "$class", 
					'id' => "FLI/$id", 
					'group' => "$group",
					'subgroup' => "$subgroup", 
					'content' => "$content", 
					'title' => "$title", 
					'start' => "$start", 
					'end' => "$endFlightLeg",
					'style' => "$itemStyle"
				);
			}


			// display failures
			if($displayFailures) {
				// create events
				$queryFailures = "SELECT a.idFailure, a.failureCode, a.accusedHostName, a.correlationDate, a.lastUpdate, a.monitorState, a.legFailureCount, b.failureDesc 
							FROM (
								SELECT a.idFailure, a.failureCode, a.accusedHostName, a.correlationDate, a.lastUpdate, a.monitorState, a.legFailureCount
								FROM $dbName.BIT_failure a
								WHERE a.idFlightLeg = $id AND (a.accusedHostName LIKE 'DSU%' OR a.accusedHostName LIKE 'SVDU%')
							) AS a
							LEFT JOIN $mainDB.sys_failureinfo b  
							ON a.failureCode = b.failureCode
							ORDER BY LENGTH(a.accusedHostName) DESC, a.accusedHostName DESC";
				$resultFailures = mysqli_query($dbConnection, $queryFailures);
									
				if($resultFailures) {
					while ($rowFailure = mysqli_fetch_array($resultFailures)) {
						$idFailure = $rowFailure['idFailure'];
						$failureCode = $rowFailure['failureCode'];
						$failureDesc = formatStringForTimeLine($rowFailure['failureDesc']);
						$monitorState = getMonitorStateDesc($rowFailure['monitorState']);
						$hostname = $rowFailure['accusedHostName'];
						$start = $rowFailure['correlationDate'];
						$legFailureCount = $rowFailure['legFailureCount'];
						
						$img = "<img src=\"../img/failure.png\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
						$content = "$img $hostname - $legFailureCount x $failureCode - $failureDesc - $monitorState";
						
						if($rowFailure['monitorState'] == 1) {
							$end = $rowFailure['lastUpdate'];
							$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
							
							$title = formatStringForTimeLine("$hostname - $failureCode - $failureDesc / Start: $start / End: $end / Duration: $duration / $monitorState");
							
							$class = 'fault';
						} else {
							$end = $endFlightLeg;
							$title = formatStringForTimeLine("$hostname - $failureCode - $failureDesc / Start: $start / $monitorState");
							
							$class = 'activeFault';
						}
						$dataItems[] = array(
							'id' => "$hostname/$idFailure",
							'className' => "$class",
							'group' => "$hostname",
							'subgroup' => "$failureCode",
							'content' => "$content", 
							'title' => "$title", 
							'start' => "$start", 
							'end' => "$end", 
							'style' => "$itemStyle",
						);

					}
				} else {
					echo "Error with query $queryFailures : ".mysqli_error($dbConnection);
					exit;
				}
			}
			
			// display faults
			if($displayFaults) {
				// create events
				$queryFaults = "SELECT a.idFault, a.faultCode, a.hostName, a.reportingHostName, a.detectionTime, a.clearingTime, a.monitorState, a.param1, a.param2, a.param3, a.param4, b.faultDesc 
							FROM (
								SELECT idFault, faultCode, hostName, reportingHostName, detectionTime, clearingTime, monitorState, param1, param2, param3, param4
								FROM BIT_fault a
								WHERE a.idFlightLeg= $id AND (hostName LIKE 'DSU%' OR hostName LIKE 'SVDU%')
							) AS a
							LEFT JOIN $mainDB.sys_faultinfo b  
							ON a.faultCode = b.faultCode
							ORDER BY LENGTH(hostName) DESC, hostName DESC";
				$resultFaults = mysqli_query($dbConnection, $queryFaults);
					
				if($resultFaults) {
					while ($rowFault = mysqli_fetch_array($resultFaults)) {
						$idFault = $rowFault{'idFault'};
						$faultCode = $rowFault{'faultCode'};
						$failureDesc = $rowFault{'faultDesc'};
						$monitorState = getMonitorStateDesc($rowFault['monitorState']);
						$hostName = $rowFault{'hostName'};
						$reportingHostname = $rowFault['reportingHostName'];
						$start = $rowFault{'detectionTime'};
						$param1 = $rowFault['param1'];
						$param2 = $rowFault['param2'];
						$param3 = $rowFault['param3'];
						$param4 = $rowFault['param4'];
						$content = "$faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4";
						if($rowFault['monitorState'] == 1) {
							$end = $rowFault{'clearingTime'};
							$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
							$title = "$hostName / $faultCode - $failureDesc / Reported by $reportingHostname / $param1 - $param2 - $param3 - $param4 / Start: $start / End: $end / Duration: $duration / $monitorState";
							// if(in_array($faultCode, $criticalFaults)) {
							// 	$class = 'critical';
							// } else {
								$class = 'fault';
							// }
						} else {
							$end = $endFlightLeg;
							$title = "$hostName / $faultCode - $failureDesc / Reported by $reportingHostname / $param1 - $param2 - $param3 - $param4 / Start: $start / $monitorState";
							// if(in_array($faultCode, $criticalFaults)) {
							// 	$class = 'criticalActive';
							// } else {
								$class = 'activeFault';
							// }
						}
						$dataItems[] = array(
							'id' => "$hostname/$idFault",
							'className' => "$class",
							'group' => "$hostName",
							'subgroup' => "$faultCode",
							'content' => "$content", 
							'title' => "$title", 
							'start' => "$start", 
							'end' => "$end", 
							'style' => "$itemStyle",
						);

						// $dataItems[$i++] = "{className: '$class', id: '$hostname/$id', content: '$content', title: '$title', 
						// start: '$start', end: '$end', $itemStyle, 
						// group: '$group', subgroup:'$hostname/$faultCode'}";
					}
				} else {
					echo "Error with query $query"; exit;
				}
			}


			if($nextRow) {
				$row = $nextRow;
			} else {
				$row = mysqli_fetch_array($result);
			}

			if(!$row) {
				$keepLooping = false;
			} 
		}
	}
} else {
	echo "error: ".mysqli_error($dbConnection);
}

		
		// Get the number of SVDUs
		$query = "SELECT count(*) AS 'count' 
					FROM $dbName.BIT_lru  a
					WHERE hostName LIKE 'SVDU%'
					AND a.lastUpdate = (
						SELECT MAX(b.lastUpdate)
						FROM $dbName.BIT_lru b
						WHERE a.hostName = b.hostName
					)";
		//TODO : Check with stephane , about above query
		$result = mysqli_query($dbConnection, $query);
		if($result) {
			$row = mysqli_fetch_array($result);
			$nbLru = $row['count'];
		}

		// Create system restarts (computation is done for an interval of 5 minutes / 300 seconds)
		// Use filter inputs for this request in the where close
		// But it looks putting the flight leg condition on this query will reduce the number of count
		// So I don't filter on the flight lef id but after in the results
		$query = " SELECT idFlightLeg, lastUpdate, count(*) AS 'count' 
					FROM (
						SELECT idFlightLeg, eventData, lastUpdate 
						FROM $dbName.BIT_events
						WHERE lastUpdate >= '$startDateTime' AND lastUpdate <= '$endDateTime'
					) AS a
					WHERE eventData LIKE 'SVDU%'";
		if(isset($flightLegsCondition)) {
			$query .= "AND $flightLegsCondition";
		}
		$query .= " GROUP BY UNIX_TIMESTAMP(lastUpdate) DIV 300 ORDER BY lastUpdate";

		$result = mysqli_query($dbConnection, $query);
		if($result) {
			$threshold = $nbLru * 15 / 100;
			while ($row = mysqli_fetch_array($result)) {
				$count = $row['count'];
				// echo "1<br>";
				if($count >= $threshold) {
					$idFlightLeg = $row['idFlightLeg'];
					// check if flight id match the flight id filter
					if(count($flightLegIds) > 0) {
						$found = in_array($idFlightLeg, $flightLegIds);	
					}
					if ($flightLegIdInput == '' || $found) {
						$img = "<img src=\"../img/power.png\" style=\"width: 12px; height: 12px;\">";
						$content = $img;
						$start = $row['lastUpdate'];
						
						$per = floor(($count/$nbLru)*100);
						
						$title = "$start / Flight leg: $idFlightLeg / $count out of $nbLru / $per%";
						
						// Check if system restart happened during cruise
                        if($statusForClimb == true){
                            $query2 = "SELECT idFlightPhase
									FROM SYS_flightPhase
									WHERE '$start' >= startTime AND '$start' <= endTime
									AND idFlightPhase = 4";
                        }
                        else{
						$query2 = "SELECT idFlightPhase
									FROM SYS_flightPhase
									WHERE '$start' >= startTime AND '$start' <= endTime
									AND idFlightPhase = 5";
                        }
						$result2 = mysqli_query($dbConnection, $query2);
						if($result2) {
							if(mysqli_num_rows($result2) > 0) {
								if($per>=15 && $per<30){
									$className = 'columnReset';
								}else if($per>=30 && $per<50){
									$className = 'critical';
								}else if($per>=50){
									$className = 'statusAlert';
								}else{
									$className = '';
								}
								//$className = 'statusAlert';
							} else {
								$className = '';
							}
						}
						
						if(isset($flightLegs) || ($className == 'statusAlert') || ($className == 'columnReset') || ($className == 'critical')) {
							$dataItems[] = array(
								'className' => "$className", 
								'group' => "SystemPower",
								'content' => "$content", 
								'title' => "$title", 
								'start' => "$start", 
								'style' => "$itemStyle"
							);
						}
					}
				}
			}
		}
		

		//Create Time Condition for fetching from services_events table.
		$timeCondition = "";
		$query = " SELECT createDate, lastUpdate FROM SYS_flight a WHERE $flightLegsCondition " ;

		$result = mysqli_query($dbConnection, $query);
		$timeConditionArray = array();
		if($result){
			while($row = mysqli_fetch_array($result)){
				array_push($timeConditionArray, " eventTime BETWEEN '" . $row['createDate'] . "' AND '" . $row['lastUpdate'] ."' ");
			}
		}
		$timeCondition = implode(' OR ', $timeConditionArray);
		
		if($timeCondition != ''){
			$timeCondition = " AND ( " . $timeCondition . " ) ";
		}
				
		$query = " SELECT param2, eventTime
				FROM $dbName.services_events a 
				WHERE a.eventSource='DSS DSU'
				AND eventName = 'DSS Redundancy'
				AND param1 = 'DOWN'  
				$timeCondition ";
		/* There is no idFlightLeg  column in services_events, so cannot apply flightLegsCondition.
		if(isset($flightLegsCondition)) {
			$query .= "AND $flightLegsCondition";
		}
		*/
		$query .= " ORDER BY eventTime";
		
		$result = mysqli_query($dbConnection, $query);
			
		if($result) {
			while ($row = mysqli_fetch_array($result)) {
				$dsu = $row['param2'];
				$time = $row['eventTime'];

				// Check if redundancy happened during cruise
                 if($statusForClimb == true){
                     $query2 = "SELECT idFlightPhase
							FROM SYS_flightPhase
							WHERE '$time' >= startTime AND '$time' <= endTime
							AND idFlightPhase = 4";
                 }
				else{
                $query2 = "SELECT idFlightPhase
							FROM SYS_flightPhase
							WHERE '$time' >= startTime AND '$time' <= endTime
							AND idFlightPhase = 5";
                }
				$result2 = mysqli_query($dbConnection, $query2);
				if($result2) {
					if(mysqli_num_rows($result2) > 0) {
						$className = 'statusAlert';
						//echo "$idFlightLeg - $nbLru - $threshold - $count<br>";
					} else {
						$className = '';
					}
				}

				if(isset($flightLegs) || ($className == 'statusAlert')) {
					$dataItems[] = array(
						'className' => "$className", 
						'group' => "Redundancy",
						'subgroup' => '$dsu',
						'content' => "$dsu", 
						'title' => "$time", 
						'start' => "$time", 
						'style' => "$itemStyle"
					);
				}
			}
		}


		if($displayFailures) {
			// Create groups
			$query = "SELECT COUNT(*) AS count, accusedHostName
						FROM BIT_failure a
						INNER JOIN SYS_flightPhase b
			            ON a.idFlightLeg = b.idFlightLeg
			            AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime 
						AND b.idFlightPhase IN ($flightPhases) 
						AND (accusedHostName LIKE 'DSU%' OR accusedHostName LIKE 'SVDU%')";
			if(isset($flightLegsCondition)) {
				$query .= "AND $flightLegsCondition";
			}
			$query .= " GROUP BY accusedHostName
						ORDER BY 
				        CASE 
				            WHEN accusedHostName LIKE 'DSU%' THEN 1 
				            ELSE 2
				        END, LENGTH(accusedHostName), accusedHostName";
			// echo $query; die;
			$result = mysqli_query($dbConnection, $query);
				
			if($result) {
				while ($row = mysqli_fetch_array($result)) {
					$count = $row['count'];
					$hostName = $row['accusedHostName'];

					$groups[] = array(
				    	'id' => "$hostName", 
				    	'content' => "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=$hostName\" target=\"_blank\"><strong>$hostName</strong> ($count)</a>",
				    	'style' => 'font-weight: bold; text-align: center'
				    );
				}
			}
		}


		if($displayFaults) {
			// Create groups
			$query = "SELECT COUNT(DISTINCT(idFault)) AS count, hostName
						FROM BIT_fault a
						INNER JOIN SYS_flightPhase b
			            ON a.idFlightLeg = b.idFlightLeg
			            AND detectionTime >= b.startTime AND detectionTime <= b.endTime 
						AND b.idFlightPhase IN ($flightPhases) 
						AND (hostName LIKE 'DSU%' OR hostName LIKE 'SVDU%')";
			if(isset($flightLegsCondition)) {
				$query .= "AND $flightLegsCondition";
			}
			$query .= " GROUP BY hostName
						ORDER BY 
				        CASE 
				            WHEN hostName LIKE 'DSU%' THEN 1 
				            ELSE 2
				        END, LENGTH(hostName), hostName";
			//echo $query; die;
			$result = mysqli_query($dbConnection, $query);
				
			if($result) {
				while ($row = mysqli_fetch_array($result)) {
					$count = $row['count'];
					$hostName = $row['hostName'];

					$groups[] = array(
				    	'id' => "$hostName", 
				    	'content' => "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=$hostName\" target=\"_blank\"><strong>$hostName</strong> ($count)</a>",
				    	'style' => 'font-weight: bold; text-align: center'
				    );
				}
			}
		}
        $itemStyleNew = "font-family: Helvetica; font-size: 10px; text-align: left; background-color: white";
		// display seat resets
		if($displayResets) {
			$query = "SELECT COUNT(DISTINCT(idEvent)) AS count, eventData, a.lastUpdate, a.eventName,a.eventInfo
						FROM BIT_events a
						INNER JOIN SYS_flightPhase b
			            ON a.idFlightLeg = b.idFlightLeg
			            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime
						AND (eventData LIKE 'DSU%' OR eventData LIKE 'LAIC%' OR eventData LIKE 'AVCD%' OR eventData LIKE 'ADB%' OR eventData LIKE 'ICMT%' OR eventData LIKE 'SVDU%' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%' ) 
						AND b.idFlightPhase IN ($flightPhases) ";
			if(isset($flightLegsCondition)) {
				$query .= "AND $flightLegsCondition ";
			}
			
			$query .= " GROUP BY eventData ";
			
			$query .= " ORDER BY 
				        CASE 
				            WHEN eventData LIKE 'DSU%' THEN 1 
				            WHEN (eventData LIKE 'AVCD%' OR eventData LIKE 'LAIC%') THEN 2
				            WHEN eventData LIKE 'ADB%' THEN 3
				            WHEN eventData LIKE 'ICMT%' THEN 4
				            WHEN eventData LIKE 'SVDU%' THEN 5
				            ELSE 6
				        END, LENGTH(eventData), eventData";
			//echo $query; die;
			$result = mysqli_query($dbConnection, $query);
				
			if($result) {
				while ($row = mysqli_fetch_array($result)) { 
                    $imgSrc =false;
					$count = $row['count'];
                    $failureDesc = $row['eventName'];
			         $eventInfo2 = $row['eventInfo'];
					$hostName = $row['eventData'];
					$time = $row['lastUpdate'];
                    $eventInfo = str_replace(' ', '', $row['eventInfo']);
              if($eventInfo == 'SWINSTALLRESET'){
                    $imgSrc = '../img/swInstallReset.png';
                }
                elseif($eventInfo == 'POWERBUTTONRESET'){
                    $imgSrc = '../img/powerBtnReset.png';
                }
                elseif($eventInfo == 'CREWRESET'){
                   $imgSrc = '../img/crewReset.png';
                }
                elseif($eventInfo == 'UNKNOWNRESETREASON'){
                    $imgSrc = '../img/unknownReset.png';
                }
                elseif($eventInfo == 'SYSTEMREBOOT'){
                    $imgSrc = '../img/systemReboot.png';
                }
                elseif($eventInfo == 'SKCOLDRESET'){
                   $imgSrc = '../img/skColdReset.png';
                }
                elseif($eventInfo == 'POWERCOLDRESET'){
                    $imgSrc = '../img/powerColdReset.png';
                }
                elseif($eventInfo == 'KERNELPANICRESET'){
                    $imgSrc = '../img/kernelPanicReset.png';
                }
                elseif($eventInfo == 'GLIBCRESET'){
                    $imgSrc = '../img/glibcReset.png';
                }
                elseif($eventInfo == 'FSCHECKRESET'){
                    $imgSrc = '../img/fsCheckReset.png';
                }
                elseif($eventInfo == 'DUCATIRESET'){
                    $imgSrc = '../img/ducatiReset.png';
                }
                elseif($eventInfo == 'ADBREBOOTRESET'){
                   $imgSrc = '../img/adbRebootReset.png';
                }
                elseif($eventInfo == ''){
                    $failureDescNew = str_replace(' ', '', $failureDesc);
                    if($failureDescNew == 'UncommandedReboot'){
                         $imgSrc = '../img/uncommandedReset.png';
                    }
                    elseif($failureDescNew == 'CommandedReboot'){
                    $imgSrc = '../img/commandedReset.png';
                    }
                }
                     if($imgSrc != false){
                $content = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 16px; height: 16px;\">";
                }
                else{
                    $imgSrc = '../img/defaultResetReason.png';
                     $content = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 16px; height: 16px;\">";
                }
                $title = "$failureDesc - $eventInfo2 / Time: $time";
					if(isset($flightLegs)) {
						$dataItems[] = array(
							'group' => "$hostName",
							'content' => "$content", 
							'title' => "$title", 
							'start' => "$time", 
							'style' => "$itemStyleNew",
							'type' => 'point'
						);

						if(!in_array($hostName, $addedHostnames)) {
							$groups[] = array(
						    	'id' => $hostName, 
						    	'content' => "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=$hostName\" target=\"_blank\"><strong>$hostName</strong> ($count)</a>",
						    	'style' => 'font-weight: bold; text-align: center'
						    );

							$addedHostnames[] = $hostName;
						}
					}
				}
			}

			$query = "SELECT DISTINCT(idEvent), eventData, eventName, a.lastUpdate, a.eventName, a.eventInfo
						FROM BIT_events a
						INNER JOIN SYS_flightPhase b
			            ON a.idFlightLeg = b.idFlightLeg
			            AND b.idFlightPhase IN ($flightPhases) 
			            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime
						AND (eventData LIKE 'DSU%' OR eventData LIKE 'LAIC%' OR eventData LIKE 'AVCD%' OR eventData LIKE 'ADB%' OR eventData LIKE 'ICMT%' OR eventData LIKE 'SVDU%' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%' )";
			if(isset($flightLegsCondition)) {
				$query .= "AND $flightLegsCondition";
			}
			$query .= " ORDER BY 
				        CASE 
				            WHEN eventData LIKE 'DSU%' THEN 1 
				            WHEN (eventData LIKE 'AVCD%' OR eventData LIKE 'LAIC%') THEN 2
				            WHEN eventData LIKE 'ADB%' THEN 3
				            WHEN eventData LIKE 'ICMT%' THEN 4
				            WHEN eventData LIKE 'SVDU%' THEN 5
				            ELSE 6
				        END, LENGTH(eventData), eventData";
			// echo $query; die;
			$result = mysqli_query($dbConnection, $query);
				
			if($result) {
				while ($row = mysqli_fetch_array($result)) {
                     $imgSrc =false;
                     $failureDesc = $row['eventName'];
			         $eventInfo2 = $row['eventInfo'];
					$hostName = $row['eventData'];
					$time = $row['lastUpdate'];
					$rebootType = $row['eventName'];
                $eventInfo = str_replace(' ', '', $row['eventInfo']);
                if($eventInfo == 'SWINSTALLRESET'){
                    $imgSrc = '../img/swInstallReset.png';
                }
                elseif($eventInfo == 'POWERBUTTONRESET'){
                    $imgSrc = '../img/powerBtnReset.png';
                }
                elseif($eventInfo == 'CREWRESET'){
                   $imgSrc = '../img/crewReset.png';
                }
                elseif($eventInfo == 'UNKNOWNRESETREASON'){
                    $imgSrc = '../img/unknownReset.png';
                }
                elseif($eventInfo == 'SYSTEMREBOOT'){
                    $imgSrc = '../img/systemReboot.png';
                }
                elseif($eventInfo == 'SKCOLDRESET'){
                   $imgSrc = '../img/skColdReset.png';
                }
                elseif($eventInfo == 'POWERCOLDRESET'){
                    $imgSrc = '../img/powerColdReset.png';
                }
                elseif($eventInfo == 'KERNELPANICRESET'){
                    $imgSrc = '../img/kernelPanicReset.png';
                }
                elseif($eventInfo == 'GLIBCRESET'){
                    $imgSrc = '../img/glibcReset.png';
                }
                elseif($eventInfo == 'FSCHECKRESET'){
                    $imgSrc = '../img/fsCheckReset.png';
                }
                elseif($eventInfo == 'DUCATIRESET'){
                    $imgSrc = '../img/ducatiReset.png';
                }
                elseif($eventInfo == 'ADBREBOOTRESET'){
                   $imgSrc = '../img/adbRebootReset.png';
                }
                elseif($eventInfo == ''){
                    if($failureDesc == 'UncommandedReboot'){
                         $imgSrc = '../img/uncommandedReset.png';
                    }
                    elseif($failureDesc == 'CommandedReboot'){
                    $imgSrc = '../img/commandedReset.png';
                    }
                }
                     if($imgSrc != false){
                $content = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 16px; height: 16px;\">";
                }
                else{
                    $imgSrc = '../img/defaultResetReason.png';
                     $content = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 16px; height: 16px;\">";
                }
                error_log('Query : '.$time);
             $title = "$failureDesc - $eventInfo2 / Time: $time";
					if(isset($flightLegs)) {
						$dataItems[] = array(
							'className' => "$rebootType", 
							'group' => "$hostName",
							'content' => "$content", 
							/*'title' => "$time", */
                            'title' => "$title", 
							'start' => "$time", 
							'style' => "$itemStyleNew",
							'type' => 'point'
						);

						if(!in_array($hostName, $addedHostnames)) {
							$groups[] = array(
						    	'id' => $hostName, 
						    	'content' => "<strong>$hostName</strong>",
						    	'style' => 'font-weight: bold; text-align: center'
						    );

							$addedHostnames[] = $hostName;
						}
					}
				}
			}
		}

		// display applications
		if($displayApplications && isset($flightLegs)) {
			// Create groups
			$query = "SELECT COUNT(DISTINCT(idExtAppEvent)) AS count, hostName
						FROM BIT_extAppEvent a
						INNER JOIN SYS_flightPhase b
			            ON a.idFlightLeg = b.idFlightLeg
			            AND b.idFlightPhase IN ($flightPhases) 
			            AND detectionTime >= b.startTime AND detectionTime <= b.endTime
						AND (hostName LIKE 'DSU%' OR hostName LIKE 'SVDU%')";
			if(isset($flightLegsCondition)) {
				$query .= "AND $flightLegsCondition";
			}
			$query .= " GROUP BY hostName
						ORDER BY 
				        CASE 
				            WHEN hostName LIKE 'DSU%' THEN 1 
				            ELSE 2
				        END, LENGTH(hostName), hostName";
			// echo $query; die;
			$result = mysqli_query($dbConnection, $query);
				
			if($result) {
				while ($row = mysqli_fetch_array($result)) {
					$count = $row['count'];
					$hostName = $row['hostName'];

					$groups[] = array(
				    	'id' => "$hostName", 
				    	'content' => "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=$hostName\" target=\"_blank\"><strong>$hostName</strong> ($count)</a>",
				    	'style' => 'font-weight: bold; text-align: center'
				    );
				}
			}

			// create events
			$query = "SELECT hostName, detectionTime, faultCode
						FROM BIT_extAppEvent a
						INNER JOIN SYS_flightPhase b
			            ON a.idFlightLeg = b.idFlightLeg
			            AND b.idFlightPhase IN ($flightPhases) 
			            AND detectionTime >= b.startTime AND detectionTime <= b.endTime
						AND (hostName LIKE 'DSU%' OR hostName LIKE 'SVDU%')";
			if(isset($flightLegsCondition)) {
				$query .= "AND $flightLegsCondition";
			}
			$query .= " ORDER BY 
				        CASE 
				            WHEN hostName LIKE 'DSU%' THEN 1 
				            ELSE 2
				        END, LENGTH(hostName), hostName";
			// echo $query; die;
			$result = mysqli_query($dbConnection, $query);
				
			if($result) {
				while ($row = mysqli_fetch_array($result)) {
					$count = $row['count'];
					$hostName = $row['hostName'];
					$time = $row['detectionTime'];
					$faultCode = $row['faultCode'];

					if(isset($flightLegs)) {
						$dataItems[] = array(
							'className' => "applications", 
							'group' => "$hostName",
							'subgroup' => "$faultCode",
							'content' => "", 
							'title' => "$time / ".getExtAppEventDesc($faultCode), 
							'start' => "$time", 
							'style' => "$itemStyle",
							'type' => 'point'
						);
					}
				}
			}
		}

		// display connectivity
		if($displayConnectivity) {
			$criticalFaults = getCriticalFaults();
					
			//$group = "Altitude";			
			createConnectivityDataForAltitudeAbove10K($collection, "Altitude", $dataItems, $itemStyle, "Altitude", "inactive",$tailSign,$flightLegs);
			
			//Wifi Available Status
			createConnectivityDataForWifi($collection, $dataItems, $itemStyle, "WIFI", "wifiStatusOn",$flightLegs);			
		
			//OMTS Available Status
			createConnectivityDataForOmts($collection, $dataItems, $itemStyle, "OMTS", "wifiStatusOn",$flightLegs);

			//DSU
			createFaultDataItems($dbConnection, $mainDB, $flightLegsCondition, $criticalFaults, $dataItems, $itemStyle, "DSU23", "DSU23", $endDateTime, $hostnameInput, $biteCode, $monitorState, $severity);

			//SDU
			createFaultDataItems($dbConnection, $mainDB, $flightLegsCondition, $criticalFaults, $dataItems, $itemStyle, "SDU", "SDU", $endDateTime, $hostnameInput, $biteCode, $monitorState, $severity);

			//NCU
			createFaultDataItems($dbConnection, $mainDB, $flightLegsCondition, $criticalFaults, $dataItems, $itemStyle, "NCU", "NCU", $endDateTime, $hostnameInput, $biteCode, $monitorState, $severity);

			//BTS
			createFaultDataItems($dbConnection, $mainDB, $flightLegsCondition, $criticalFaults, $dataItems, $itemStyle, "BTS", "BTS", $endDateTime, $hostnameInput, $biteCode, $monitorState, $severity);

			//CWLU
			createFaultDataItems($dbConnection, $mainDB, $flightLegsCondition, $criticalFaults, $dataItems, $itemStyle, "CWLU", "CWLU", $endDateTime, $hostnameInput, $biteCode, $monitorState, $severity);
		}
		
		if($displayMaintenance) {
			// Create maintenance activities reuqest
			$removedLruQuery = " SELECT *
								FROM BIT_removedLru
								WHERE removalDate >= '$startDateTime' AND lastUpdate <= '$endDateTime'
								AND hostName NOT LIKE 'IPM%'
								ORDER BY removalDate";
			// echo $removedLruQuery;
			$result = mysqli_query($dbConnection, $removedLruQuery);
			if($result) {
				$i = 0;
				while ($row = mysqli_fetch_array($result)) {
					$content = $row['hostName'];
					$start = $row['removalDate'];
					$serialNumber = $row['serialNumber'];
					$title = "$content / $start / previous S/N: $serialNumber";
					// echo "{id': 'MAIN/$content/$serialNumber', group: 'Maintenance', content: '$content', title: '$title', start: '$start', $itemStyle}";
					// $dataItems[] = "{id': 'MAINT/$content/$serialNumber', group: 'Maintenance', content: '$content', title: '$title', start: '$start', 'style': '$itemStyle'}";

					$dataItems[] = array(
							'id' => "MAINT/$content/$i", 
							'group' => "Maintenance",
							'content' => "$content", 
							'title' => "$title", 
							'start' => "$start", 
							'style' => "$itemStyle"
						);

					$i++;
				}
			}
		}


/*	
} else {
	echo "error: ".mysqli_error($dbConnection);
}
*/
//TODO:Check with stephane, to place the last else -if combinatioin before computing resets and others.


$fleetStatusData = array(
	'groups' => $groups,
	'items' => $dataItems,
	'options' => $options
); 

echo json_encode($fleetStatusData, JSON_NUMERIC_CHECK );


function createFaultDataItems($dbConnection, $database, $flightLegsCondition, $criticalFaults, &$dataItems, $itemStyle, $unitType, $group, $endFlightLeg, $hostnameInput, $biteCode, $monitorState, $severity) {
	$query = "SELECT a.idFault, a.faultCode, a.hostName, a.reportingHostName, a.detectionTime, a.clearingTime, a.monitorState, a.param1, a.param2, a.param3, a.param4, b.faultDesc 
	FROM (
		SELECT idFault, faultCode, hostName, reportingHostName, detectionTime, clearingTime, monitorState, param1, param2, param3, param4
		FROM BIT_fault a
		WHERE $flightLegsCondition
		AND hostName LIKE '$unitType%' 
		AND monitorState IN ($monitorState)
	) AS a
	LEFT JOIN $database.sys_faultinfo b  
	ON a.faultCode = b.faultCode
	ORDER BY LENGTH(hostName) DESC, hostName DESC";

	// echo $query; exit;

	$result = mysqli_query($dbConnection, $query);
	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			$id = $row{'idFault'};
			$faultCode = $row{'faultCode'};
			$failureDesc = $row{'faultDesc'};
			$monitorState = getMonitorStateDesc($row['monitorState']);
			$hostname = $row{'hostName'};
			$reportingHostname = $row['reportingHostName'];
			$start = $row{'detectionTime'};
			$param1 = $row['param1'];
			$param2 = $row['param2'];
			$param3 = $row['param3'];
			$param4 = $row['param4'];
			$content = "<img src=\"../img/fault.png\" style=\"vertical-align:top; width: 12px; height: 12px;\"> $hostname / $faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4";
			if($row['monitorState'] == 1) {
				$end = $row{'clearingTime'};
				$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
				$title = "$hostname / $faultCode - $failureDesc / Reported by $reportingHostname / $param1 - $param2 - $param3 - $param4 / Start: $start / End: $end / Duration: $duration / $monitorState";
				if(in_array($faultCode, $criticalFaults)) {
					$class = 'critical';
				} else {
					$class = '';
				}
			} else {
				$end = $endFlightLeg;
				$title = "$hostname / $faultCode - $failureDesc / Reported by $reportingHostname / $param1 - $param2 - $param3 - $param4 / Start: $start / $monitorState";
				if(in_array($faultCode, $criticalFaults)) {
					$class = 'criticalActive';
				} else {
					$class = 'active';
				}
			}			
			
			if($unitType == 'CWLU') {
				$subgroup = "$hostname/$faultCode";
			} elseif($unitType == 'BTS') {
				$group = $hostname;
				$subgroup = "$faultCode";
			} else {				
				$subgroup = "$hostname/$faultCode/$id";
				
			}

			$dataItems[] = array(
							'className' => "$class", 
							'id' => "$hostname/$id",
							'group' => "$group", 
							'subgroup' => "$subgroup",
							'content' => "$content",
							'title' => "$title", 
							'start' => "$start", 
							'end' => "$end",
							'style' => "$itemStyle",
						);
		}
	}
}


//Get the Altitude event time stamp for time line data
function createConnectivityDataForAltitudeAbove10K($collection, $typeOfConnectivity, &$dataItems, $itemStyle, $group, $className,$tailSign,$flightLegs) 
{
	$fields = array('altitudeEvent.startTime',
					'tailSign',
					'startTime',
					'idFlightLeg',
					'altitudeEvent.endTime');
					
	$flightLegsArray = getFlightInArray($flightLegs);
								
	$where=array("idFlightLeg" => array('$in' => $flightLegsArray));
	
/*	$where=array('$and' => array(	array("startTime" => array('$ne' => 0)),
									array("idFlightLeg" => $flightLegs),
									array("tailSign" => $tailSign)));*/
		//echo "the Tailsign is $tailSign";
	$cursor = $collection->find($where,$fields);
	
	foreach ($cursor as $doc) 
	{

		if(($doc['altitudeEvent']['startTime'] != null) || ($doc['altitudeEvent']['endTime'] != null))
		{
			$altitudeStartTime = $doc['altitudeEvent']['startTime'];

			$altitudeEndTime = $doc['altitudeEvent']['endTime'];

			$duration = dateDifference($altitudeStartTime, $altitudeEndTime, '%h Hours %i Minute %s Seconds');
			$titleString = "Start : $altitudeStartTime/ End : $altitudeEndTime / Duration : $duration\n";

			$contentString = "Altitude Above 10K FT";

			$dataItems[] = array(
				'id' => "CON/ALT/$altitudeStartTime/$altitudeEndTime",
				'className' => "$className", 
				'group' => "$group", 
				'content' => "$contentString",
				'title' => "$titleString", 
				'start' => "$altitudeStartTime", 
				'end' => "$altitudeEndTime",
				'style' => "$itemStyle"

			);
		}
	}
}

//get Wifi data for Timestamp
function createConnectivityDataForWifi($collection, &$dataItems, $itemStyle, $group, $className,$flightLegs) 
{
	
	$flightLegsArray = getFlightInArray($flightLegs);
								
	$where=array("idFlightLeg" => array('$in' => $flightLegsArray));
	
	$cursor = $collection->find($where);
	
	foreach ($cursor as $doc) 
	{

		if(is_array($doc['wifiAvailabilityEvents']) && count($doc['wifiAvailabilityEvents']) > 0)
		{
			foreach($doc['wifiAvailabilityEvents']  as $temp)
			{
				$wifiStartTime = $temp['startTime'] ;
				$wifiEndTime = $temp['endTime'];
				$wifiDescription = $temp['description'];
				$faultString = $temp['computedFailure'];
				$manualFaultString =  $temp['manualFailureEntry'];
				
				if(($wifiStartTime != null) && ($wifiEndTime != null) && ($wifiDescription != null))
				{				
				
					$duration = dateDifference($wifiStartTime, $wifiEndTime, '%h Hours %i Minute %s Seconds');
					
					if($wifiDescription == "WIFI-ON"){
						$contentString = "WIFI ON";
						$idString = "WIFION";
						$group = "WIFI";
						$className = "wifiStatusOn";
						$titleString = "Start : $wifiStartTime/ End : $wifiEndTime / Duration : $duration";
					}elseif($wifiDescription == "WIFI-OFF"){
						$contentString = "WIFI OFF";
						$idString = "WIFIOFF";
						$group = "WIFI";
						$titleString = "Start : $wifiStartTime/ End : $wifiEndTime / Duration : $duration";
						
						if($faultString != 'Restricted Area ')  {
							$len = strlen ($manualFaultString); 
							if($len > 0){
								$titleString .= " / RootCause : " . $manualFaultString;
							}else{
								$titleString .= " / RootCause : " . $faultString;
							}					
							$className = "wifiStatusOff";	
						}else {
							// Restricted Aread - Change color to orange
							$className = "restrictedArea";
							$len = strlen ($manualFaultString); 
							if($len > 0){
								$rootCauseString = $manualFaultString;
								$titleString .= " / RootCause : " . $rootCauseString;
							}else{
								$rootCauseString = str_replace("Unknown","",$faultString);
								$titleString .= " / RootCause : " . $rootCauseString;							
							}						
						}
					
					}elseif($wifiDescription == "WIFI RESTRICTED AREA"){
						$contentString = "WIFI RESTRICTED AREA";
						$idString = "WIFIRA";
						$group = "Restricted Area";
						$className = "restrictedArea";
						$titleString = "Start : $wifiStartTime/ End : $wifiEndTime / Duration : $duration";
					}else{
						$contentString = "Nodata";
						$idString = "Nodata";
					}
				
					$dataItems[] = array(
						'id' => "CON/$idString/$wifiStartTime/$wifiEndTime",
						'className' => "$className", 
						'group' => "$group", 
						'content' => "$contentString",
						'title' => "$titleString", 
						'start' => "$wifiStartTime", 
						'end' => "$wifiEndTime",
						'style' => "$itemStyle"

					);
				}

			}
		}
	}		
}


//Get the OMTS Data for time stamp
function createConnectivityDataForOmts($collection, &$dataItems, $itemStyle, $group, $className,$flightLegs) 
{
								
	$flightLegsArray = getFlightInArray($flightLegs);
								
	$where=array("idFlightLeg" => array('$in' => $flightLegsArray));
	
	$cursor = $collection->find($where);
	
	foreach ($cursor as $doc) 
	{

		if(is_array($doc['omtsAvailabilityEvents']) && count($doc['omtsAvailabilityEvents']) > 0)
		{
			foreach($doc['omtsAvailabilityEvents']  as $temp)
			{
				$omtsStartTime = $temp['startTime'] ;
				$omtsEndTime = $temp['endTime'];
				$omtsDescription = $temp['description'];
				$faultString = $temp['computedFailure'];
				$manualFaultString =  $temp['manualFailureEntry'];
				
				if(($omtsStartTime != null) && ($omtsEndTime != null))
				{								
					$duration = dateDifference($omtsStartTime, $omtsEndTime, '%h Hours %i Minute %s Seconds');

					if($omtsDescription == "OMTS-ON"){
						$contentString = "OMTS ON";
						$idString = "OMTSON";
						$group = "OMTS";
						$className = "wifiStatusOn";
						$titleString = "Start : $omtsStartTime/ End : $omtsEndTime / Duration : $duration";
					}elseif($omtsDescription == "OMTS-OFF"){
						$contentString = "OMTS OFF";
						$idString = "OMTSOFF";
						$group = "OMTS";
						$titleString = "Start : $omtsStartTime/ End : $omtsEndTime / Duration : $duration";
						
						if($faultString != 'Restricted Area ')  {
							$len = strlen ($manualFaultString); 
							if($len > 0){
								$titleString .= " / RootCause : " . $manualFaultString;
							}else{
								$titleString .= " / RootCause : " . $faultString;
							}					
							$className = "wifiStatusOff";	
						}else {
							// Restricted Aread - Change color to orange
							$className = "restrictedArea";
							$len = strlen ($manualFaultString); 
							if($len > 0){
								$rootCauseString = $manualFaultString;
								$titleString .= " / RootCause : " . $rootCauseString;
							}else{
								$rootCauseString = str_replace("Unknown","",$faultString);
								$titleString .= " / RootCause : " . $rootCauseString;							
							}						
						}
					
					}elseif($omtsDescription == "OMTS-RESTICTED"){
						$contentString = "OMTS RESTRICTED AREA";
						$idString = "OMTSRA";
						$group = "OMTS Restricted Area";
						$className = "restrictedArea";
						$titleString = "Start : $omtsStartTime/ End : $omtsEndTime / Duration : $duration";
					}else{
						$contentString = "Nodata";
						$idString = "Nodata";
					}
					
					$dataItems[] = array(
						'id' => "CON/$idString/$omtsStartTime/$omtsEndTime",
						'className' => "$className", 
						'group' => "$group", 
						'content' => "$contentString",
						'title' => "$titleString", 
						'start' => "$omtsStartTime", 
						'end' => "$omtsEndTime",
						'style' => "$itemStyle"

					);
				}

			}
		}
	}
		
		
}	
?>
