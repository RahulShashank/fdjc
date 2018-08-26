<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

//$newAircraftSw = $request['newSoftware'];
$newAircraftStatus = $request['newStatus'];
$aircraftId = $request['aircraftId'];

//	Status Return Format
$status = array('state' => 0,
				'message' => "Successfully Updated Aircraft Software"
				);
				
if(isset($aircraftId) && !empty($aircraftId) && !is_null($aircraftId)){
	
}else{
	$status['state'] = -1;
	$status['message'] = "Aircraft Id Missing";
	echo json_encode($status);
	exit ();
}

$query = " UPDATE $mainDB.aircrafts SET maintenanceStatus = '$newAircraftStatus' WHERE id = $aircraftId ";
$result = mysqli_query($dbConnection, $query);
if(!$result){
	$status['state'] = -1;
	$status['message'] = "Update Error for $query :".mysqli_error($dbConnection);
	echo json_encode($status);
	exit ();
}else{
	$status['state'] = 0;
	$status['message'] = "Updated Software Successfully";
	echo json_encode($status);
}

mysqli_commit($dbConnection);

?>
