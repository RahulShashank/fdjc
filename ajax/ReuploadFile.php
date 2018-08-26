<?php

ini_set('memory_limit', '-1');
ini_set('max_execution_time', 2000);
date_default_timezone_set("GMT");

if(!isset($_SESSION)){
	error_log("session in Reupload was not started, starting it");
	session_start();
}

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/seatAnalyticsData.php";
require_once "../common/resetsReportFunctions.php";
require_once "../common/FlightFaultFunctions.php";

require_once ('../engineering/checkEngineeringPermission.php');

require_once "../common/computeFleetStatusData.php";

$ds = DIRECTORY_SEPARATOR;
$FILE_PROCESSED = 1;
$TAILSIGN_NOT_FOUND = 2;
$DB_NOT_FOUND = 3;

$storeFolder = 'upload_offloads';
$archiveFolder = 'Archive';
$processedFolder = 'Processed';
$rejectedFolder = 'Rejected';
$readXmlFiles = true;

// Get user uid. We are going to upload in a specific directory for each user.
$uid = $auth->getSessionUID($hash);

// Check if the user id is empty. If not, through error message.
if(empty($uid) || !isset($uid)) {
	error_log("User id is not set. It could be due to session expiry");
	echo "NO_UID";
	exit();
}

$airlineId = $_REQUEST['airlineId'];

$query="select acronym from $mainDB.airlines where id=$airlineId";

$result = mysqli_query($dbConnection, $query);

if (mysqli_num_rows($result) > 0) {
   	$row = mysqli_fetch_assoc($result);
   	$acronym = $row["acronym"];
}

$tailsign = $_REQUEST['tailsign'];

$fileName = $_REQUEST['fileName'];
error_log("fileName is $fileName");

$source = $_REQUEST['source'];
error_log("source is $source");

//$_SESSION['source'] = "Manual";
$_SESSION['source'] = $source;
$_SESSION['airlineId'] = $airlineId;
$_SESSION['fileName'] = $fileName;
$_SESSION['tailsign'] = $tailsign;
$_SESSION['acronym'] = $acronym;

//$tempFile = $_FILES['file']['tmp_name'];
$tempFile = $fileName;

$targetPath = dirname(dirname(__FILE__)) . $ds . $storeFolder . $ds . $uid . $ds;

//BeR saving targetPath for process
$_SESSION['targetPath'] = $targetPath;

$archivePath = dirname(dirname(__FILE__)) . $ds . $storeFolder . $ds . $archiveFolder . $ds;
//BeR saving archivepath for process
$_SESSION['archivePath'] = $archivePath;

//$RejectedFilePath = $archivePath . $rejectedFolder . $ds . $airlineId . $ds . $fileName;

$id = $_REQUEST['offloadId'];

// $query="SELECT om.airlineId, al.acronym, om.fileName, om.tailsignFound, om.status FROM $mainDB.offloads_master om, $mainDB.airlines al where om.id=$id and om.airlineId=al.id";
$query="SELECT om.airlineId, om.fileName, om.tailsignFound, om.status FROM $mainDB.offloads_master om, $mainDB.airlines al where om.id=$id";
error_log("Downloadfile query: $query");
$result = mysqli_query($dbConnection, $query);
$ProcessedPath='';
$RejectedFilePath='';
$arr = array();
$rejectedFileAcronym = "";

if (mysqli_num_rows($result) > 0) {
   	$row = mysqli_fetch_assoc($result);
   	$airlineId = $row["airlineId"];

    if($airlineId == 0) {
        $RejectedFilePath = $archivePath . $rejectedFolder . $ds . $fileName;
    } else {
        $query="SELECT acronym FROM $mainDB.airlines where id=$airlineId";
        $result = mysqli_query($dbConnection, $query);
        if($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $rejectedFileAcronym = $row['acronym'];
        }
        $RejectedFilePath = $archivePath . $rejectedFolder . $ds . $rejectedFileAcronym . $ds . $fileName;
    }
}else{
    $RejectedFilePath = $archivePath . $rejectedFolder . $ds . $filename;
}

error_log('Rejected File Path '.$RejectedFilePath);
if (!copy($RejectedFilePath, $targetPath.$fileName)) {
    error_log("failed to copy $RejectedFilePath...\n");
}else{
    error_log("copied $RejectedFilePath into $targetPath\n");
    unlink($RejectedFilePath);
}

if (! file_exists($targetPath)) {
	mkdir($targetPath, 0755);
}

//BeR: saving all targetFile* details for process
$targetFileName = $tempFile;
$_SESSION['targetFileName'] = $targetFileName;
$targetFile =  $targetPath. $tempFile;
$_SESSION['targetFile'] = $targetFile;
$targetFileType = pathinfo($targetFile, PATHINFO_EXTENSION);
$_SESSION['targetFileType'] = $targetFileType;
$_SESSION['offload_master_id'] = $id;
include ('../engineering/ProcessOffloads.php');
//include_path ('../engineering/ProcessOffloads.php');
?>
