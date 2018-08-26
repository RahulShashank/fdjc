<?php
require_once "../database/connecti_database.php";
require_once "../common/computeFleetStatusData.php";
include ("../engineering/BlockCustomer.php");
// date_default_timezone_set("GMT");

$airlineIds = $_REQUEST['airlineIds'];

require_once("../common/validateUser.php");
$approvedRoles = [$roles["admin"], $roles["engineer"]];
$auth->checkPermission($hash, $approvedRoles);

$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "6 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

$query = "SELECT id, name, acronym, status, lastStatusComputed FROM airlines";
if(isset($airlineIds) && $airlineIds != -1) {
	$query .=  " WHERE id IN ($airlineIds)";
}
$query .= " order by name";

$stmt = $dbConnection->prepare($query) ;

$stmt->execute();
$stmt->bind_result($id, $name, $acronym, $status, $lastStatusComputed);

$airlines = array();
$dateTimeThreshold = date_modify(new DateTime(), '-7 day');
$stmt->store_result ();
while ($stmt->fetch()) {

	$lastStatusComputedDateTime = new DateTime( $lastStatusComputed );
	if( $lastStatusComputedDateTime < $dateTimeThreshold ){
		$status=-1;
	}
	$offloadStatus=false;
	$query1 = "SELECT * FROM ( SELECT COUNT(distinct(tailsignFound)) as tailsignFoundcount,airlineId FROM banalytics.offloads_master WHERE  uploadedTime BETWEEN '$startDateTime 00:00:00' AND '$endDateTime 23:59:59' AND tailsignFound <> '' AND airlineId=$id) x INNER JOIN ( SELECT COUNT(distinct(tailsign)) as tailsigntotalcount,airlineId FROM banalytics.aircrafts WHERE airlineId=$id ) y ON x.airlineId = y.airlineId limit 1 ";					

	if( $stmt1 = $dbConnection->prepare($query1) ) {					
		$stmt1->execute();
		$stmt1->bind_result($tailsignFoundcount,$airlineId,$tailsigntotalcount,$airlineId);
		$stmt1->store_result ();
		while ($stmt1->fetch()) {
			$tailsignOffloads=$tailsignFoundcount;
			$totalTailsign=$tailsigntotalcount;
			$offloadPercentage=($tailsignFoundcount *100)/$tailsigntotalcount;	
			error_log('offloadPercentage : '.$offloadPercentage);		
			if($offloadPercentage>=80){
				$offloadStatus=true;
			}				
		}
	}else{
		$offloadStatus=false;
	}
	$airlines[] = array('id' => $id, 'name' => $name, 'acronym' => $acronym, 'status'=>$status, 'lastStatusComputed'=>$lastStatusComputed, 'offloadStatus'=>$offloadStatus);
}

$stmt->close();

# JSON-encode the response
echo $json_response = json_encode($airlines);

?>
