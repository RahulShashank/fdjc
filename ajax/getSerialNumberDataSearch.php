<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);
session_start();
$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
// Change format
$startDateTime = strtotime($startDateTime);
$startDateTime = date("Y-m-d H:i:s", $startDateTime);

// Change format
$endDateTime = strtotime($endDateTime);
$endDateTime = date("Y-m-d H:i:s", $endDateTime); 

$airlineId = $_REQUEST['airlineId'];
$serialNumberlru = trim($_REQUEST['serialNumber']);

$_SESSION['airlineId'] = $_REQUEST['airlineId'];
$_SESSION['platform'] =  $_REQUEST['platform'];
$_SESSION['startDate'] =  $_REQUEST['startDateTime'];
$_SESSION['endDate'] =  $_REQUEST['endDateTime'];
$_SESSION['serialNumber'] =  $serialNumberlru;
$_SESSION['submenu'] =  $_REQUEST['submenu'];

$serialNumberlist = array();
$dBArray=array();
if( ( isset($airlineId) || isset($sqlDump) ) && isset($startDateTime) && isset($endDateTime) ) {

	if(isset($airlineId)) {
		// Get database name
		$query = "SELECT databaseName, tailsign,id from aircrafts WHERE airlineId=$airlineId ";		
		
		if( $stmt = $dbConnection->prepare($query) ) {
			
			$stmt->execute();
			$stmt->bind_result($databaseName,$tailsign,$aircraftId);
			$stmt->store_result ();
			while ($stmt->fetch()) {				
				$query1 = "SELECT hostName,hwPartNumber,serialNumber,idFlightLeg,lastUpdate,macAddress,ipAddress FROM $databaseName.bit_lru WHERE lastUpdate BETWEEN '$startDateTime' AND '$endDateTime' AND hostName NOT LIKE 'IPM%'";
				if($serialNumberlru != ''){
					$query1.=" AND serialNumber='$serialNumberlru'";
				}
				$query1.=" ORDER BY lastUpdate";
				error_log("Get Serial Number Search Data Query: " . $query1);
				
				if( $stmt1 = $dbConnection->prepare($query1) ) {
					
					$stmt1->execute();
					$stmt1->bind_result($hostName, $hwPartNumber, $serialNumber, $idFlightLeg,$lastUpdate,$macAddress,$ipAddress);
					$stmt1->store_result ();
					while ($stmt1->fetch()) {
						$serialNumberlist[] = array(
							'hostName' => $hostName, 
							'hwPartNumber' => $hwPartNumber, 
							'serialNumber' => $serialNumber,
							'idFlightLeg' => $idFlightLeg,
							'lastUpdate' => $lastUpdate,
							'tailsign' => $tailsign,
							'macAddress' => $macAddress,
							'aircraftId' => $aircraftId,	
							'ipAddress'=>$ipAddress					
						);
					}
			
					$stmt1->close();
				} else {
					echo "Error creating statement for $query1";
					exit;
				}
			}
	
			$stmt->close();
		}else {
			echo "Error creating statement";
			exit;
		}
	} else {
		$dbName = $sqlDump;
	}

	
}

# JSON-encode the response
echo $json_response = json_encode($serialNumberlist);

?>
