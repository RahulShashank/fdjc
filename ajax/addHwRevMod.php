<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../admin/checkAdminPermission.php');

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

if($request['action'] == 'add'){
	$lruType = $request ['lruType'];
	$hwPartNumber = $request ['hwPartNumber'];
	$expectedRevision = $request ['expectedRevision'];
	$expectedModel = $request ['expectedModel'];


	//	Status Return Format
	$status = array('state' => 0,
					'message' => "Successfully Added HW Rev Mod"
					);
					
	//echo json_encode($status);exit();

	// Server Side Validation of User Input.
	if(!preg_match('/^.{1,30}$/',$lruType)){
		$status['state'] = -1;
		$status['message'] = "Invalid LRU Type";
		echo json_encode($status);
		exit ();
	}else if(!preg_match('/^.{1,10}$/',$hwPartNumber)){
		$status['state'] = -1;
		$status['message'] = "Invalid HW Part Number";
		echo json_encode($status);
		exit ();
	}elseif(!preg_match('/^[A-Z]{1,2}$/',$expectedRevision)){
		$status['state'] = -1;
		$status['message'] = "Invalid Expected Revision";
		echo json_encode($status);
		exit ();
	}elseif(!preg_match('/^[\d]+(,[\d]+)*$/',$expectedModel)){
		$status['state'] = -1;
		$status['message'] = "Invalid Expected Model";
		echo json_encode($status);
		exit ();
	}


	//Escape the user input before using in mysql query.
	$lruType = mysqli_real_escape_string($dbConnection, $lruType);
	$hwPartNumber = mysqli_real_escape_string($dbConnection, $hwPartNumber);
	$expectedRevision = mysqli_real_escape_string($dbConnection, $expectedRevision);
	$expectedModel = mysqli_real_escape_string($dbConnection, $expectedModel);

	$sql = "INSERT INTO hardware_revs_mods (lruType, hwPartNumber, expectedRevision, expectedModel)
						  VALUES
						 ('$lruType', '$hwPartNumber','$expectedRevision','$expectedModel')";

	if (! mysqli_query ( $dbConnection,$sql )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	if (! mysqli_commit ( $dbConnection )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	echo json_encode($status);
	exit();
}elseif($request['action']=='delete'){

	$hwRevModIds = $request ['hwRevModIds'];
	
	//	Status Return Format
	$status = array('state' => 0,
					'message' => "Successfully Deleted HW Rev Mods"
					);
					
	$hwRevModIdArray = explode(',', trim($hwRevModIds));
	$hwRevModIdsString = implode(',', $hwRevModIdArray);
					
	$sql = "DELETE FROM hardware_revs_mods where id in ($hwRevModIdsString) ";

	if (! mysqli_query ( $dbConnection,$sql )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	if (! mysqli_commit ( $dbConnection )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	echo json_encode($status);
	exit();
}

?>
