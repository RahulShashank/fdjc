<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once "../map/airports.php";
require_once "../common/computeFleetStatusData.php";
require_once ("checkEngineeringPermission.php");

function getPassengerServicesData($dbName,$flightLegs,$dbConnection) {
		//Passenger Events
		$query = "SELECT * FROM $dbName.SYS_flight
					WHERE idFlightLeg IN($flightLegs)";
					
		$result = mysqli_query($dbConnection, $query);
		
		$startDates = array();
		$endDates = array();	
		$passengerDataItems = array();
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
						$passengerDataItems[$i++] = "{group: 'FP', subgroup:'$subgroupOrderFlightPhase',
							content: '$contentFlightPhase', title: '$titleFlightPhase', start: '$startFlightPhase', end: '$endFlightPhase', style: '$itemStyle'}";				
					}
				}
				
				// Add flight leg now as we its the status
				$passengerDataItems[$i++] = "{className: '$class', group: '$group', content: '$content', title: '$content', 
					start: '$startFL', end: '$endFL', style: '$itemStyle'}";
				
				
				// Retrieve service event without DSU INFO 
				$query2 = " SELECT a.eventName, a.eventSource, a.eventTime, a.param1, a.param2, a.param3 
							FROM services_events a
							WHERE eventName = 'AppService ON or OFF'                
							AND eventTime BETWEEN '$startFL' AND '$endFL'
							ORDER BY a.eventTime";	
				
				$result2 = mysqli_query($dbConnection, $query2);
		
				if($result2 && mysqli_num_rows($result2) > 0) {
					while ($row = mysqli_fetch_array($result2)) {			
						$eventName = $row['eventName'];
						if($eventName == "AppService ON or OFF") {
							$eventSource = $row['eventSource'];
							if($eventSource == "FSA") {
								$group = "FSA";
							} else {
								$group = "Services";
							}
							$start = $row['eventTime'];
							$service = $row['param1'];
							if( $service == 'AOD') {
								$img = "audio.png";
							} else if( $service == 'VOD') {
								$img = "video.png";
							} else if( $service == 'GAMES') {
								$img = "games.png";
							} else if( $service == 'MAP') {
								$img = "map.png";
							} else if( $service == 'SHOPPING') {
								$img = "shopping.png";
							} else if( $service == 'SURVEY') {
								$img = "survey.png";
							} else if( $service == 'TELEPHONY') {
								$img = "telephony.png";
							} else if( $service == 'INTERNET') {
								$img = "internet.png";
							} else if( $service == 'FLIGHTCONNECT') {
								$img = "flightConnect.png";
							} else if( $service == 'HOSPITALITY') {
								$img = "hospitality.png";
							} else {
								$img = "service.png";
							}
							$content = "- <img src=\"../img/$img\" style=\"vertical-align:bottom;\"> -";
							$subgroup = $service;
							$status = $row['param2'];
							if($status == "ON") {
								$className = "appon";
							} else {
								$className = "appoff";
							}
							$title = "$service - $status - $start";
							$passengerDataItems[$i] = "{className: '$className', group: '$group', subgroup: '$subgroup', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
							$i++;
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
		
		$passengerServiceData = array(
			'items' => $passengerDataItems,
			'options' => $options
		); 
		
		return $passengerServiceData;
	}
?>