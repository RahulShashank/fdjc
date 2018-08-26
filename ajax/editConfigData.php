<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";

require_once('../engineering/checkEngineeringPermission.php');

$configname			= $_REQUEST ['configname'];
$platform			= $_REQUEST ['platform'];
$previoussw 		= $_REQUEST ['previoussw'];
$previousswversion 	= $_REQUEST ['previousswversion'];
$latestsw 			= $_REQUEST ['latestsw'];
$latestswversion 	= $_REQUEST ['latestswversion'];
$futuresw			= $_REQUEST ['futuresw'];
$futureswdate 		= $_REQUEST ['futureswdate'];
$airlineId			= $_REQUEST ['airlineId'];


$status = array('state' => 0,
				'message' => "Successfully updated Aircraft"
				);
				
	

//Escape the user input before using in mysql query.
$configname 		= mysqli_real_escape_string($dbConnection, $configname);
$platform 			= mysqli_real_escape_string($dbConnection, $platform);
$previoussw 		= mysqli_real_escape_string($dbConnection, $previoussw);
$previousswversion 	= mysqli_real_escape_string($dbConnection, $previousswversion);
$latestsw 			= mysqli_real_escape_string($dbConnection, $latestsw);
$latestswversion 	= mysqli_real_escape_string($dbConnection, $latestswversion);
$futuresw 			= mysqli_real_escape_string($dbConnection, $futuresw);
$futureswdate 		= mysqli_real_escape_string($dbConnection, $futureswdate);
$airlineId 			= mysqli_real_escape_string($dbConnection, $airlineId);


//Not allowing second entry for same aircraft Using TailSign.

	$qry = "UPDATE Configuration SET `Platform`='$platform',`PreviousSW`='$previoussw',`PreviousCustSW`='$previousswversion',`LatestSW`='$latestsw',`LatestCustSW`='$latestswversion',`FutureSW`='$futuresw',`FutureSwDate`='$futureswdate' WHERE ConfigName='$configname' and airlineId='$airlineId'";
	

	if (! mysqli_query ( $dbConnection,$qry )) {
		$status['state'] = -1;
		$status['message'] = "Error: $qry " . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}else{
		echo "Successful";
		mysqli_autocommit ( $dbConnection, TRUE );
	}
	

//echo json_encode("$qry");

echo json_encode("$status");
?>
