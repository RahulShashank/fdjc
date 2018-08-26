<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../admin/checkAdminPermission.php');

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

if($request['action'] == 'add'){
	$partNumber = $request ['partNumber'];
	$name = $request ['name'];
	$lruType = $request ['lruType'];
	

	$myfile = fopen("newfile1.txt", "w") or die("Unable to open file!");
	//	Status Return Format
	$status = array('state' => 0,
					'message' => "Successfully Added LRU Partnumber details"
					);
					
	//echo json_encode($status);exit();
	
	// Server Side Validation of User Input.
	if(!preg_match('/^[A-Za-z0-9-]+$/',$partNumber)){
		$status['state'] = -1;
		$status['message'] = "Invalid Part number";
		fwrite($myfile, "Invalid Part number");
		echo json_encode($status);
		exit ();
	}
	/*
	else if(!preg_match('/^[A-Za-z0-9-]+$/',$name)){
		$status['state'] = -1;
		$status['message'] = "Invalid name";
		fwrite($myfile, "Invalid name");
		echo json_encode($status);
		exit ();
	}
	
	elseif(!preg_match('/^[A-Za-z0-9 -]+$/',$lruType)){
		$status['state'] = -1;
		$status['message'] = "Invalid lruType";
		fwrite($myfile, "Invalid lruType");
		echo json_encode($status);
		exit ();
	}
*/

	//Escape the user input before using in mysql query.
	$partNumber = mysqli_real_escape_string($dbConnection, $partNumber);
	$name = mysqli_real_escape_string($dbConnection, $name);
	$lruType = mysqli_real_escape_string($dbConnection, $lruType);
	
	$query = "SELECT id FROM lru_types WHERE name='$lruType'";
	
	$result = mysqli_query($dbConnection, $query);
	$lruTypeId = 0;
	if($result){
		while ($row = mysqli_fetch_array($result)) {
			$lruTypeId = $row['id'];
		}
	}
	

	$sql = "INSERT INTO lru_table (partNumber, name, lruType, lruTypeId)
						  VALUES ('$partNumber','$name','$lruType', $lruTypeId)";
						  
	fwrite($myfile, $sql);
	fclose($myfile);

	if (! mysqli_query ( $dbConnection,$sql )) {
		$status['state'] = -1;
		$status['message'] = mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	if (! mysqli_commit ( $dbConnection )) {
		$status['state'] = -1;
		$status['message'] = mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	echo json_encode($status);
	exit();
}elseif($request['action']=='delete'){

	$lrupartNumbersIds = $request ['lrupartNumbersIds'];
	
	//	Status Return Format
	$status = array('state' => 0,
					'message' => "Successfully Deleted HW Rev Mods"
					);
					
	$lrupartNumbersIdsArray = explode(',', trim($lrupartNumbersIds));
	$lrupartNumbersIdsString = implode(',', $lrupartNumbersIdsArray);
					
	$sql = "DELETE FROM lru_table where id in ($lrupartNumbersIdsString) ";

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
