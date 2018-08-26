<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once "../map/airports.php";
require_once "../common/computeFleetStatusData.php";
require_once ("checkEngineeringPermission.php");

function getSystemEventData($dbName,$flightLegs,$dbConnection) {

	$query = "SELECT * FROM $dbName.SYS_flight
			WHERE idFlightLeg IN($flightLegs)";
		
	$result = mysqli_query($dbConnection, $query);

	$startDates = array();
	$endDates = array();
	$SystemEventsdataItems = array();
	$i=0;

	// display flight legs
	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			$id = $row['idFlightLeg'];
			$content = $id." - ".$row['flightNumber']." - ".$row['departureAirportCode']." - ".$row['arrivalAirportCode'];
			$startFL = $row['createDate'];
			$endFL = $row['lastUpdate'];

			$flightLegName = $row{'flightLeg'};
				
			if(strpos($flightLegName, 'CL') === 0) {
				//$color = "border-color: rgb(144,144,144); background-color: rgb(240,240,240)";
				$group = 'CL';
			} else {
				//$color = "border-color: rgb(154,180,246); background-color: rgb(214,222,245)";
				$group = 'OPP';
			}

			// default flight leg class
			$class = 'closed';
				
			// get corresponding flight phases
			$query2 = "SELECT * FROM $dbName.SYS_flightPhase WHERE idFlightLeg = $id ORDER BY startTime";
			$result2 = mysqli_query($dbConnection, $query2);

			if($result2) { // not every dump has the sys_flightphase table
				while ($row2 = mysqli_fetch_array($result2)) {
					$idFlightPhase = $row2{'idFlightPhase'};
					$contentFlightPhase = getFlightPhaseDesc($idFlightPhase) . " [$idFlightPhase]";
					$startFlightPhase = $row2{'startTime'};
					$endFlightPhase = $row2{'endTime'};
					$subgroupOrderFlightPhase = getFlightPhaseOrder($idFlightPhase);
					$durationFlightPhase = dateDifference($startFlightPhase, $endFlightPhase, '%h Hours %i Minute %s Seconds');
					$titleFlightPhase = "$contentFlightPhase / $startFlightPhase --> $endFlightPhase / $durationFlightPhase";

					// Status computation
					if($idFlightPhase == 4 || $idFlightPhase == 5) {
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
					}
					$startDates[]=$startFlightPhase;
					$endDates[]=$endFlightPhase;
					
					$SystemEventsdataItems[$i++] = "{group: 'FP', subgroup:'$subgroupOrderFlightPhase',
					content: '$contentFlightPhase', title: '$titleFlightPhase', start: '$startFlightPhase', end: '$endFlightPhase', style: '$itemStyle'}";				
				}
			}

			// Add flight leg now as we its the status
			$SystemEventsdataItems[$i++] = "{className: '$class', group: '$group', content: '$content', title: '$content',
			start: '$startFL', end: '$endFL', style: '$itemStyle'}";


			// Retrieve service event without DSS INFO
			$query2 = " SELECT a.eventName, a.eventSource, a.eventTime, a.param1, a.param2, a.param3
					FROM services_events a
					WHERE (						
						(eventName LIKE 'FlightPhase%')
						OR
						(eventName LIKE 'System Mode Change%')
						OR
						(eventName LIKE 'HB Master%')
						OR
						(eventName LIKE 'BIT Master%')
						OR
						(eventName LIKE 'AllDoorsClosed')
						OR
						(eventName LIKE 'LandingGearDownLocked')
						OR
						(eventName LIKE '%Decompression%')
						OR
						(eventName LIKE '%Offload%')
						OR
						(eventName LIKE 'WOW')
						OR
						(eventName LIKE '%Ground%')
					)                
					AND eventTime BETWEEN '$startFL' AND '$endFL'
					ORDER BY a.eventTime";	

			$result2 = mysqli_query($dbConnection, $query2);

			if($result2 && mysqli_num_rows($result2) > 0) {
				while ($row = mysqli_fetch_array($result2)) {
					$eventName = $row['eventName'];
					$className = "appoff";

					if($eventName == "FlightPhase") {
						$eventSource = $row['eventSource'];
						if($eventSource == "FligthDataService") {
							$group = "FDSFP";
							$content = $row['param1'];
						} else {
							$group = "CSWFP";
							$content = substr($row['param1'], 24); // Remove "GAP_ICD_V2_FLIGHT_PHASE_"
						}

						$start = $row['eventTime'];
						$title = "$content - $start";
							
						switch($content){
							case "PRE_FLIGHT_GROUND":
							case "PRE_FLIGHT":
							case "POST_FLIGHT_GROUND":
								$subgroupOrder = 1;
								break;
							case "TAXI_OUT":
							case "TAXI_IN":
								$subgroupOrder = 2;
								break;
							case "TAKEOFF":
							case "TAKE_OFF":
							case "TOUCHDOWN":
							case "TOUCH_DOWN":
								$subgroupOrder = 3;
								break;
							case "CLIMB":
							case "DESCENT_APPROACH":
								$subgroupOrder = 4;
								break;
							case "CLIMB":
								$subgroupOrder = 5;
								break;
							case "CRUISE":
								$subgroupOrder = 6;
								break;
							default:
								$subgroupOrder = 0;
						}
							
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', subgroup:'$subgroupOrder', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "System Mode Change") {
						$content = $row['param1'];
						$modeArray = explode('_', $content);
						$content = $modeArray[count($modeArray)-1];
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "MODE";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "HB Master") {
						$content = $row['param1'];
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "HB";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "BIT Master") {
						$content = $row['param1'];
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "BIT";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "AllDoorsClosed") {
						$status = ($row['param1'] === "ON") ? "CLOSED" : "OPEN";
						$content = $status;
						$subgroupOrder = 0;
						if($status === "CLOSED") {
							$className = ""; // Set a different color when doors are closed
							$subgroupOrder = 1;
						}
						$start = $row['eventTime'];
						$title = "$content - $start / " . $row['param2'];
						$group = "DOORS";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', subgroup: '$subgroupOrder', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "WOW") {
						$content = $row['param1'];
						$subgroupOrder = 0;
						if($content == "OFF" ) {
							$className = "";
							$subgroupOrder = 1;
						}
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "WOW";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', subgroup: '$subgroupOrder', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "LandingGearDownLocked") {
						$content = $row['param1'];
						if($content == "ON" ) {
							$className = "";
						}
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "LNDGR";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					} else if($eventName == "Decompression") {
						$content = $row['param1'];
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "DEC";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					}  else if($eventName == "Offload") {
						$content = $row['param1'];
						$start = $row['eventTime'];
						$title = "$content - $start";
						$group = "OFF";
						$SystemEventsdataItems[$i++] = "{className: '$className', group: '$group', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
					}
				}
			}
		}
	}
		usort($startDates, function($a, $b){
		    $dateTimestamp1 = strtotime($a);
		    $dateTimestamp2 = strtotime($b);
		
		    return $dateTimestamp1 < $dateTimestamp2 ? -1: 1;
		});
		usort($endDates, function($a, $b){
		    $dateTimestamp1 = strtotime($a);
		    $dateTimestamp2 = strtotime($b);
		
		    return $dateTimestamp1 < $dateTimestamp2 ? -1: 1;
		});
		
		$endDate=$endDates[count($endDates) - 1];
		
		$timelineStartDateTime = date("Y-m-d H:i:s", strtotime("-1 hours", strtotime($startDates[0]))); 
		$timelineEndDateTime = date("Y-m-d H:i:s", strtotime("+1 hours", strtotime($endDate)));

		$options = 	array(
				    	'start' => "$startDates[0]", 
				    	'end' => "$endDate",
						'min' => "$timelineStartDateTime", 
				    	'max' => "$timelineEndDateTime"
					);
		
		$systemEventData = array(
			'items' => $SystemEventsdataItems,
			'options' => $options
		); 
		
		return $systemEventData;
}
?>