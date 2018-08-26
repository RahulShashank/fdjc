<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');
include ("../engineering/BlockCustomer.php");
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);

$tailsigns = $_REQUEST['tailsigns'];
$airlines = $_REQUEST['airlines'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

$query = "SELECT a.id,a.airlineId,a.tailsign,a.aircraftId,a.filename,a.date,b.name,b.acronym FROM banalytics.connectivity_upload a INNER JOIN airlines b ON a.airlineId=b.id where date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' ";

if($airlines != '' && !empty($airlines) ){
			//$query.=" AND tailsign IN ($tailsign)";
			$query .= " and airlineId IN (";
            foreach ($airlines as $ts) {
                $query .=  $ts . ",";
            }
            $query = rtrim($query, ",");
            $query .= ")";
		}
if($tailsigns != '' && !empty($tailsigns) ){
			//$query.=" AND tailsign IN ($tailsign)";
			$query .= " and tailsign IN (";
            foreach ($tailsigns as $ts) {
                $query .= "'" . $ts . "',";
            }
            $query = rtrim($query, ",");
            $query .= ")";
		}
$query .= " order by a.id";

$stmt = $dbConnection->prepare($query) ;

$stmt->execute();
$stmt->bind_result($id, $airlineId, $tailsign, $aircraftId, $filename, $date, $name, $acronym);

$connectivity = array();

while ($stmt->fetch()) {
	$connectivity[] = array('id' => $id, 'airlineId' => $airlineId, 'tailsign' => $tailsign, 'aircraftId'=>$aircraftId, 'filename'=>$filename, 'date'=>$date, 'name'=>$name, 'acronym'=>$acronym);
}

$stmt->close();

# JSON-encode the response
echo $json_response = json_encode($connectivity);

?>
