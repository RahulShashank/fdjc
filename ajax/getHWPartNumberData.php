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

$hostNamelru = trim($_REQUEST['hostname']);

$_SESSION['airlineId'] = $_REQUEST['airlineId'];
$_SESSION['platform'] =  $_REQUEST['platform'];
$_SESSION['configType'] =  $_REQUEST['configType'];		
$_SESSION['tailsignList'] =  $_REQUEST['tailsign'];
$_SESSION['hostNamelru'] =  $hostNamelru;
$_SESSION['startDate'] =  $_REQUEST['startDateTime'];
$_SESSION['endDate'] =  $_REQUEST['endDateTime'];
$_SESSION['submenu'] =  $_REQUEST['submenu'];

$hwPartNumberlist = array();
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
		if( $stmt = $dbConnection->prepare($query) ) {
			
			$stmt->execute();
			$stmt->bind_result($databaseName,$tailsign,$aircraftId);
			$stmt->store_result ();
			while ($stmt->fetch()) {				

				$query1 = "SELECT hostName,hwPartNumber,serialNumber,idFlightLeg,lastUpdate,macAddress,ipAddress FROM $databaseName.bit_lru WHERE lastUpdate BETWEEN '$startDateTime' AND '$endDateTime' AND hostName NOT LIKE 'IPM%'";
				if($hostNamelru != ''){
					$query1.=" AND hostName like '%$hostNamelru%'";
				}
				$query1.=" ORDER BY lastUpdate";
				error_log($query1);		
				if( $stmt1 = $dbConnection->prepare($query1) ) {					
					$stmt1->execute();
					$stmt1->bind_result($hostName, $hwPartNumber, $serialNumber, $idFlightLeg,$lastUpdate,$macAddress,$ipAddress);
					$stmt1->store_result ();
					while ($stmt1->fetch()) {
						$hwPartNumberlist[] = array(
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
				}  else {
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
echo $json_response = json_encode($hwPartNumberlist);

?>
