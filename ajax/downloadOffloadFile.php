<?php
error_log("Inside downloadOffloads.php", 0);

// Start the session
session_start ();

require_once "../database/connecti_database.php";

$ds = DIRECTORY_SEPARATOR;
$storeFolder = 'upload_offloads';
$archiveFolder = 'Archive';
$processedFolder = 'Processed';
$rejectedFolder = 'Rejected';
$targetPath = dirname(dirname(__FILE__)) . $ds . $storeFolder . $ds;
$archivePath = dirname(dirname(__FILE__)) . $ds . $storeFolder . $ds . $archiveFolder . $ds;
$downloadPath = "";
// echo "Target Path: $targetPath";
// $airlineId = $_REQUEST ['airlineId'];

// $postdata = file_get_contents ( "php://input" );
// $request = json_decode ( $postdata, true );

// $id = $request ['id'];
$id = $_GET['id'];
$filename = $_GET['filename'];

// $query="SELECT airlineId, fileName, status, tailsignFound, flightNumber, depTime, arrTime, depAirport, arrAirport, offloadDate, failureReason FROM $mainDB.offloads_master where id=$id";
$query="SELECT om.airlineId, al.acronym, om.fileName, om.tailsignFound, om.status FROM $mainDB.offloads_master om, $mainDB.airlines al where om.id=$id and om.airlineId=al.id";
error_log("Downloadfile query: $query");
$result = mysqli_query($dbConnection, $query);

$arr = array();
if (mysqli_num_rows($result) > 0) {
   	$row = mysqli_fetch_assoc($result);

    if($row["status"] == "Processed") {
        $downloadPath = $archivePath . $processedFolder . $ds . $row['acronym']. $ds . $row["tailsignFound"] . $ds . $row["fileName"];
        
        if (!file_exists($downloadPath)) {
            $downloadPath = $archivePath . $processedFolder . $ds . $row["tailsignFound"] . $ds . $row["fileName"];
        }
    } else {
        $downloadPath = $archivePath . $rejectedFolder . $ds . $row['acronym'] . $ds . $row["fileName"];
        
        if (!file_exists($downloadPath)) {
            $downloadPath = $archivePath . $rejectedFolder . $ds . $row['airlineId'] . $ds . $row["fileName"];
        }
    }
}else{
    $downloadPath = $archivePath . $rejectedFolder . $ds . $filename;
}

error_log("Going to download the file: $downloadPath", 0);

if (file_exists($downloadPath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($downloadPath));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($downloadPath));
    ob_clean();
    flush();
    readfile($downloadPath);
    exit;
} else {
    echo "File is not available for download";
}
?>