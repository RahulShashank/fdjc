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
$tailsign = $_REQUEST['tailsign'];
$config = $_REQUEST['configType'];
$platform = $_REQUEST['platform'];
$software = $_REQUEST['software'];

//$hwPartNumber = trim($_REQUEST['hwPartNumber']);
$serialNumberRemoval = trim($_REQUEST['serialNumber']);
$hostnameRemoval = trim($_REQUEST['hostname']);
$hwPartNumberRemoval = trim($_REQUEST['hwPartNumber']);

$_SESSION['airlineId'] = $_REQUEST['airlineId'];
$_SESSION['platform'] =  $_REQUEST['platform'];
$_SESSION['configType'] =  $_REQUEST['configType'];		
$_SESSION['tailsignList'] =  $_REQUEST['tailsign'];
$_SESSION['startDate'] =  $_REQUEST['startDateTime'];
$_SESSION['endDate'] =  $_REQUEST['endDateTime'];

$_SESSION['serialNumberRemoval'] =  $serialNumberRemoval;
$_SESSION['hostnameRemoval'] =  $hostnameRemoval;
$_SESSION['hwPartNumberRemoval'] =  $hwPartNumberRemoval;
$_SESSION['submenu'] =  $_REQUEST['submenu'];

error_log($_SESSION['tailsignList'].'Session AirlineId : '.$_SESSION['configType']);

$removals = array();
$dBArray=array();
if( ( isset($airlineId) || isset($sqlDump) ) && isset($startDateTime) && isset($endDateTime) ) {

	if(isset($airlineId)) {
		// Get database name
		$query = "SELECT databaseName, tailsign,id from aircrafts WHERE airlineId=$airlineId";
		
		if($tailsign != '' && !empty($tailsign) ){
			//$query.=" AND tailsign IN ($tailsign)";
			$query .= " and tailsign IN (";
            foreach ($tailsign as $ts) {
                $query .= "'" . $ts . "',";
            }
            $query = rtrim($query, ",");
            $query .= ")";
		}
		if($config != '' && !empty($config) ){
			//$query.=" AND Ac_Configuration IN ($config)";
			$query .= " and Ac_Configuration IN (";
            foreach ($config as $conf) {
                $query .= "'" . $conf . "',";
            }
            $query = rtrim($query, ",");
            $query .= ")";
		}
		if($platform != '' && !empty($platform) ){
			//$query.=" AND platform IN ($platform)";
			$query .= " and platform IN (";
            foreach ($platform as $platf) {
                $query .= "'" . $platf . "',";
            }
            $query = rtrim($query, ",");
            $query .= ")";
		}
		if($software != '' && !empty($software) ){
			//$query.=" AND software IN ($software)";
			
			$query .= " and software IN (";
            foreach ($software as $soft) {
                $query .= "'" . $soft . "',";
            }
            $query = rtrim($query, ",");
            $query .= ")";
		}
// 		error_log("Get Maintenance Data Query: " . $query);
		
		if( $stmt = $dbConnection->prepare($query) ) {
			//$stmt->bind_param("s", $flightLegs);
			$stmt->execute();
			$stmt->bind_result($databaseName,$tailsign,$aircraftId);
			$stmt->store_result ();
			while ($stmt->fetch()) {				
// 				error_log('dBnAME '.$databaseName);
// 				error_log('tailsign  '.$tailsign);
				// Get removals
				$query1 = "SELECT removalDate, hostName, serialNumber, newSerialNumber,idFlightLeg,hwPartNumber FROM $databaseName.BIT_removedLru WHERE removalDate BETWEEN '$startDateTime' AND '$endDateTime' AND hostName NOT LIKE 'IPM%'";
				if($hwPartNumberRemoval != ''){
					$query1.=" AND hwPartNumber='$hwPartNumberRemoval'";
				}
				if($serialNumberRemoval != ''){
					$query1.=" AND serialNumber='$serialNumberRemoval'";
				}
				if($hostnameRemoval != ''){
					$query1.=" AND hostName like '%$hostnameRemoval%'";
				}
				$query1.=" ORDER BY removalDate";
				
				//echo $query;exit;
				if( $stmt1 = $dbConnection->prepare($query1) ) {
					//$stmt->bind_param("s", $flightLegs);
					$stmt1->execute();
					$stmt1->bind_result($removalDate, $hostName, $serialNumber, $newSerialNumber,$idFlightLeg,$hwPartNumber);
					$stmt1->store_result ();
					while ($stmt1->fetch()) {
						$removals[] = array(
							'removalDate' => $removalDate, 
							'hostname' => $hostName, 
							'serialNumber' => $serialNumber,
							'newSerialNumber' => $newSerialNumber,
							'idFlightLeg' => $idFlightLeg,
							'tailsign' => $tailsign,
							'aircraftId' => $aircraftId,		
							'hwPartNumber'=>$hwPartNumber					
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
echo $json_response = json_encode($removals);

?>
