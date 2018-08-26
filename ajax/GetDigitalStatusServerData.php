<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once "../map/airports.php";
require_once "../common/computeFleetStatusData.php";
require_once ("../engineering/checkEngineeringPermission.php");

function getDigitalServerStatusData($dbName,$flightLegs,$dbConnection) {
	//execute the SQL query and return records
	$query = "SELECT * FROM $dbName.SYS_flight
			WHERE idFlightLeg IN($flightLegs)";
		
	$result = mysqli_query($dbConnection, $query);

	$startDates = array();
	$endDates = array();
		
	$dataItems = array();
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

					$dataItems[$i++] = "{group: 'FP', subgroup:'$subgroupOrderFlightPhase',
					content: '$contentFlightPhase', title: '$titleFlightPhase', start: '$startFlightPhase', end: '$endFlightPhase', style: '$itemStyle'}";				
				}
			}

			// Add flight leg now as we its the status
			$dataItems[$i++] = "{className: '$class', group: '$group', content: '$content', title: '$content',
			start: '$startFL', end: '$endFL', style: '$itemStyle'}";


			// Display DSS events
			$query2 = " SELECT a.eventName, a.eventSource, a.eventTime, a.param1, a.param2, a.param3
			FROM services_events a 
			WHERE a.eventSource in ('DSS DSU','DSS')			
			AND eventTime BETWEEN '$startFL' AND '$endFL'
			ORDER BY a.eventTime";				
			$result2 = mysqli_query($dbConnection, $query2);

			if($result2 && mysqli_num_rows($result2) > 0) {
				while ($row = mysqli_fetch_array($result2)) {
					$eventName = $row['eventName'];
					$start = $row['eventTime'];
					
					if($row['eventSource']=='DSS DSU' || $eventName=='DSS Redundancy'){
						if($eventName == "DSS Redundancy"){
							$group = "Redundancy";
						}
						else if($eventName == "DSS Status apache"){
							$group = "Status_apache";
						}
						else if($eventName == "DSS Status rabbitmq"){
							$group = "Status_rabbitmq";
						}
						else if($eventName == "DSS Status mysql"){
							$group = "Status_mysql";
						}
						else if($eventName == "DSS Status 3dMaps"){
							$group = "Status_3dMaps";
						}
						else{
							$group = "Status_os3dMaps";
						}
	
						$DSU = $row['param2'];
						$status = $row['param1'];
						$subgroup = substr($DSU, 3);
						$content = "$DSU - $status";
						$title = "$content - $start - $subgroup";
						if($status == "UP") {
							$className = "appon";
						} else {
							$className = "appdown";
						}
					}else if($row['eventSource']=='DSS' || $eventName=='System_SW_Redundancy'){
						if($row['param1'] == 'apache'){							
							$group = "Status_apache";
						}
						else if($row['param1']== 'rabbitmq'){
							$group = "Status_rabbitmq";
						}
						else if($row['param1']== 'mysql'){
							$group = "Status_mysql";
						}
						else if($row['param1'] == '3dMaps'){
							$group = "Status_3dMaps";
						}
						else if($row['param1'] == 'os3dMaps'){
							$group = "Status_os3dMaps";
						}else if($eventName == "DSS Redundancy"  || $eventName=='System_SW_Redundancy'){
							$group = "Redundancy";
						}
						
							$DSU = $row['param3'];
							$status = $row['param2'];
							$subgroup = substr($DSU, 3);
							$content = "$DSU - $status";
							$title = "$content - $start - $subgroup";
							if($status == "UP") {
								$className = "appon";
							} else {
								$className = "appdown";
							}
						
					}

					$dataItems[$i++] = "{className: '$className', group: '$group', subgroup: '$subgroup', content: '$content', title: '$title',  start: '$start', style: '$itemStyle'}";
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
		
		$digitalServerData = array(
			'items' => $dataItems,
			'options' => $options
		); 
		
		return $digitalServerData;
}
?>