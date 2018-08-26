<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

$tailsign		= $_REQUEST ['tailsign'];
$msn			= $_REQUEST ['msn'];
$software 		= $_REQUEST ['software'];
$swpartno 		= $_REQUEST ['swpartno'];
$swinstalled	= $_REQUEST ['swinstalled'];
$swbaseline 	= $_REQUEST ['swbaseline'];
$mapversion 	= $_REQUEST ['mapversion'];
$content		= $_REQUEST ['content'];
$maintenanceStatus		= $_REQUEST ['maintenanceStatus'];


$status = array('state' => 0,
				'message' => "Successfully updated Aircraft"
				);
				
				
	/*if(!preg_match('/^.{1,10}$/',$software)){
	$status['state'] = -1;
	$status['message'] = "Invalid Software";
	echo json_encode($status);
	exit ();
	}*/
	/*else if(!preg_match('/^[A-Za-z0-9-]+$/',$partNumber)){
	$status['state'] = -1;
	$status['message'] = "Invalid Part number";
	echo json_encode($status);
	exit ();	*/	

//Escape the user input before using in mysql query.
$mapversion 	= mysqli_real_escape_string($dbConnection, $mapversion);
$swbaseline 	= mysqli_real_escape_string($dbConnection, $swbaseline);
$swinstalled 	= mysqli_real_escape_string($dbConnection, $swinstalled);
$swpartno 		= mysqli_real_escape_string($dbConnection, $swpartno);
$software 		= mysqli_real_escape_string($dbConnection, $software);
$content 		= mysqli_real_escape_string($dbConnection, $content);
$tailsign 		= mysqli_real_escape_string($dbConnection, $tailsign);
$msn 			= mysqli_real_escape_string($dbConnection, $msn);
$maintenanceStatus 			= mysqli_real_escape_string($dbConnection, $maintenanceStatus);


//Not allowing second entry for same aircraft Using TailSign.
if ($tailsign != '') {
	$qry = "UPDATE aircrafts SET `software`='$software',`msn`='$msn',`SW_PartNo`='$swpartno',`SW_installed`='$swinstalled',`SW_Baseline`='$swbaseline',`Map_Version`='$mapversion',`Content`='$content', `maintenanceStatus`='$maintenanceStatus' WHERE tailsign='$tailsign'";	

	if (! mysqli_query ( $dbConnection,$qry )) {
		$status['state'] = -1;
		$status['message'] = "Error: $qry " . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}else{
// 		echo "Successful";
		mysqli_autocommit ( $dbConnection, TRUE );
	}
	
}
//echo json_encode("$qry");

echo json_encode($status);
?>
