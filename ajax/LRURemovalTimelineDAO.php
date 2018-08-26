<?php
error_log(basename(__FILE__) . " entered");
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
$flightLegs = $_REQUEST['flightLegs'];
// Change format
$starDateTime = strtotime($starDateTime);
$starDateTime = date("Y-m-d H:i:s", $starDateTime);

// Change format
$endDateTime = strtotime($endDateTime);
$endDateTime = date("Y-m-d H:i:s", $endDateTime); 

$removals = array();

if( ( isset($aircraftId) || isset($sqlDump) ) && isset($startDateTime) && isset($endDateTime) ) {

	if(isset($aircraftId)) {
		// Get database name
		$query = "SELECT databaseName from aircrafts WHERE id=?";
		
		if( $stmt = $dbConnection->prepare($query) ) {
			$stmt->bind_param("i", $aircraftId);
			$stmt->execute();
			$stmt->bind_result($dbName);
			$stmt->fetch();

			$stmt->close();
		} else {
			echo "Error creating statement";
			exit;
		}
	} else {
		$dbName = $sqlDump;
	}

    // Get removals
	$query = "SELECT brl.removalDate, brl.hostName, brl.serialNumber, brl.newSerialNumber, brl.idFlightLeg
	FROM $dbName.BIT_removedLru brl, $dbName.SYS_flight sf
	WHERE brl.idFlightLeg = sf.idFlightLeg
	AND brl.removalDate >= sf.createDate AND brl.removalDate <= sf.lastUpdate
	AND brl.hostName NOT LIKE 'IPM%'
	AND brl.idFlightLeg in ($flightLegs)
	ORDER BY brl.removalDate";
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($removalDate, $hostName, $serialNumber, $newSerialNumber, $idFlightLeg);
		
		while ($stmt->fetch()) {
			$removals[] = array(
				'removalDate' => $removalDate, 
				'hostname' => $hostName, 
				'serialNumber' => $serialNumber,
			    'newSerialNumber' => $newSerialNumber,
			    'idFlightLeg' => $idFlightLeg
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
}

# JSON-encode the response
echo $json_response = json_encode($removals);

?>
