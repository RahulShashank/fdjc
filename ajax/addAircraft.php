<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
//require_once('../admin/checkAdminPermission.php');

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

$airlineId = $request ['airlineId'];
$tailsign = $request ['tailSign'];
$msn = $request ['msn'];
$type = $request ['type'];
$config = $request ['config'];
$platform = $request ['platform'];
$isp = $request ['isp'];
$software = $request ['software'];
$noseNumber = $request ['noseNumber'];
$Ac_Configuration = $request ['Ac_Configuration'];
$swinstalled = $request ['swinstalled'];
$EIS = $request ['EIS'];
$softwareBaseline = $request ['softwareBaseline'];


//	Status Return Format
$status = array('state' => 0,
				'message' => "Successfully Added Aircraft"
				);
				
//echo json_encode($status);exit();

// Server Side Validation of User Input.
if(!preg_match('/^\d+$/',$airlineId)){
	$status['state'] = -1;
	$status['message'] = "Invalid Airline Id";
	echo json_encode($status);
	exit ();
/*}elseif(!preg_match('/^([A-Z0-9]+-)*[A-Z0-9]+$/',$tailsign)){
	$status['state'] = -1;
	$status['message'] = "Invalid Tailsign";
	echo json_encode($status);
	exit ();*/
}elseif(!preg_match('/^[0-9]{1,5}$/',$msn)){
	$status['state'] = -1;
	$status['message'] = "Invalid MSN";
	echo json_encode($status);
	exit ();
}elseif(!preg_match('/^[A-Za-z0-9]{1,10}$/',$type)){
	$status['state'] = -1;
	$status['message'] = "Invalid Aircraft Type";
	echo json_encode($status);
	exit ();
}elseif(!preg_match('/^[A-Za-z0-9]{1,10}$/',$platform)){
	$status['state'] = -1;
	$status['message'] = "Invalid Platform";
	echo json_encode($status);
	exit ();
}elseif(!preg_match('/^[A-Za-z0-9]+$/',$isp)){
	$status['state'] = -1;
	$status['message'] = "Invalid Internet Service Provider";
	echo json_encode($status);
	exit ();
}elseif(!preg_match('/^.{1,10}$/',$software)){
	$status['state'] = -1;
	$status['message'] = "Invalid Software";
	echo json_encode($status);
	exit ();
}

$airlineId = mysqli_real_escape_string($dbConnection, $airlineId);	//Escape the user input before using in mysql query.
$query = "SELECT a.acronym FROM airlines a WHERE id = $airlineId";
$result = mysqli_query ( $dbConnection, $query );

$airlineAcronymName = '';

//	In Case of errors in MySQL query execution.
if(!$result){
	$status['state'] = -1;
	$status['message'] = "Error: Finding Airline " . mysqli_error ( $dbConnection );
	echo json_encode($status);
	exit ();
}

if (mysqli_num_rows ( $result ) > 0) {
	$row = mysqli_fetch_array ( $result );
	$airlineAcronymName = $row ['acronym'];
} else {
	//	If Airline not found.
	$status['state'] = -1;
	$status['message'] = "Error: Airline Not found ";
	echo json_encode($status);
	exit ();
}

$tailsign = mysqli_real_escape_string($dbConnection, $tailsign);	//Escape the user input before using in mysql query.
$queryForTail = "SELECT * FROM aircrafts WHERE tailsign = '$tailsign'";
$resultForTail = mysqli_query ( $dbConnection,$queryForTail );

//	In Case of errors in MySQL query execution.
if(!$resultForTail){
	$status['state'] = -1;
	$status['message'] = "Error: " . mysqli_error ( $dbConnection );
	echo json_encode($status);
	exit ();
}

// Case where Aircraft matching tailsign already exists.
if(mysqli_num_rows($resultForTail) > 0){
	$status['state'] = -1;
	$status['message'] = "Error: Aircraft with matching Tailsign already exists";
	echo json_encode($status);
	exit ();
}

$aircraftDbName = createDbNameFrmTail ( $tailsign, $airlineAcronymName );

//Escape the user input before using in mysql query.
$msn = mysqli_real_escape_string($dbConnection, $msn);
$type = mysqli_real_escape_string($dbConnection, $type);
if(isset($config) && !is_null($config) && $config != '' ){
	$config = mysqli_real_escape_string($dbConnection, $config);
	$config = intval($config);
}else{
	$config = "NULL";
}
$platform = mysqli_real_escape_string($dbConnection, $platform);
$isp = mysqli_real_escape_string($dbConnection, $isp);
$software = mysqli_real_escape_string($dbConnection, $software);
$aircraftDbName = mysqli_real_escape_string($dbConnection, $aircraftDbName);
$repairTailName = getRepairTailNameForAircraftType($type, $msn);
error_log("Repair Tail Name after calling the method : $repairTailName");

// not allowing second entry for same aircraft Using TailSign.
if (mysqli_num_rows ( $resultForTail ) == 0 and $tailsign != '') {
	$sql = "INSERT INTO aircrafts (tailsign,repair_TailName,noseNumber,msn,type,aircraftConfigId,platform,software,airlineId,flightLegIdCount,databaseName, isp,Ac_Configuration,SW_installed,EIS,SW_Baseline)
	       	          VALUES
	       	         ('$tailsign','$repairTailName','$noseNumber','$msn','$type', $config ,'$platform','$software','$airlineId','1','$aircraftDbName','$isp','$Ac_Configuration','$swinstalled','$EIS','$softwareBaseline')";

	if (! mysqli_query ( $dbConnection,$sql )) {
		$status['state'] = -1;
		$status['message'] = "Error: $sql " . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}
// 	mysqli_commit( $dbConnection );
}

// Create Database. TODO: handle the case if DB creation is unsuccessfull.
$createDB = createDataBaseForAircraft ( $aircraftDbName, $tailsign );


/*	TODO: Idea of using commit & rollback was to fail the transaction, if new db creation is failure or any of the mysql_queries fail. But because of
*	DDL statment in between (CREATE DB), transaction gets committed implicitly. Need to find a proper handling of this case.
*/ 
if($createDB){
    error_log("Going to commit the DB changes");
// 	mysqli_select_db ( $dbConnection, $mainDB);
	mysqli_commit($dbConnection);
}else{
	mysqli_rollback( $dbConnection );
}

echo json_encode($status);
exit();


// Generate DB name
function createDbNameFrmTail($tailsign, $airlineAcronymName) {
	$dbName = "";
	$tail = str_replace ( "-", "_", $tailsign );
	
	if (is_null ( $tail )) {
		$dbName = $airlineAcronymName . "_" . $tailsign;
	} else {
		$dbName = $airlineAcronymName . "_" . $tail;
	}
	
	return $dbName;
}
// Generate New DataBase for new Aircraft.
function createDataBaseForAircraft($aircraftDbName, $tailsign) {
	global $status, $dbConnection;
	$dbCreationStatus = TRUE;
	
	error_log("addAircrafts.php -> going to create database with name $aircraftDbName");
	
	// not allowing database creationg if TailSign is wrong.
	if (! mysqli_select_db ( $dbConnection, $aircraftDbName ) and $tailsign != '') {
		//echo ("creating database!\n");
		$sql = "CREATE DATABASE $aircraftDbName";
		if (! mysqli_query ( $dbConnection,$sql )) {
			// TODO : Handle case whether to EXIT with note or IGNORE if db creation failed. right now echoing Error, which gets displayed to user.
			$status['state'] = -1;
			$status['message'] = 'Error creating database: ' . mysqli_error ($dbConnection);
			$dbCreationStatus = FALSE;
			return $dbCreationStatus;
		}
		// Select Database
		mysqli_select_db ( $dbConnection, $aircraftDbName );
		
		// Generate tables from "db_schema.txt"
		$filepath = pathinfo ( $_SERVER ['SCRIPT_FILENAME'], PATHINFO_DIRNAME );
		$fileschemapath = $filepath . "/db_schema.txt";
		
		$templine = '';
		$lines = file ( $fileschemapath );
		foreach ( $lines as $line ) {
			if (substr ( $line, 0, 2 ) == '--' || $line == '')
				continue;
			
			$templine .= $line;
			if (substr ( trim ( $line ), - 1, 1 ) == ';') {
				if (! mysqli_query ( $dbConnection, $templine )) {
				// TODO : Handle case whether to EXIT with note or IGNORE if db creation failed. right now echoing Error, which gets displayed to user.
					$status['state'] = -1;
					$status['message'] = 'Error Loading Schema: ' . mysqli_error ($dbConnection);
					$dbCreationStatus = FALSE;
					break;
				}
				$templine = '';
			}
		} //End of Foreach Loop
		//echo "Database created successfully with ".$tailsign;
		mysqli_commit( $dbConnection );
	} // End of Mysql DB Selection.
	
	error_log("addAircrafts.php -> database $aircraftDbName created and the creation status is $dbCreationStatus");
	
	return $dbCreationStatus;
} // End of create database
  
// JSON-encode the response
// echo $json_response = json_encode($tailsign));
// echo $tailsign;

/**
 * Fetches the RepairTailName for the given aircraft type.
 * 
 * @param unknown $acType
 * @param unknown $msn
 * @return string|unknown
 */
function getRepairTailNameForAircraftType($acType, $msn) {
    
    global $dbConnection, $mainDB;
    $repairTailName = "";
    
    $paddedmsn = str_pad($msn, 4, '0', STR_PAD_LEFT);
        
    $query = "select concat_ws('',repair_prefix, '$paddedmsn') as repairTailName from $mainDB.aircraft_types where type='$acType'";
    error_log("Repair Tail Name Query: $query");
    $result = mysqli_query($dbConnection, $query);
    
    if($result) {
        $row = mysqli_fetch_assoc($result);
        $repairTailName = $row['repairTailName'];
        error_log("Repair Tail Name : $repairTailName");
    }
    
    return $repairTailName;
}

?>
