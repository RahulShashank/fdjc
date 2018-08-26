<?php
require_once "../database/connecti_database.php";
//require_once('../engineering/checkEngineeringPermission.php');

$filter = $_REQUEST ['filter'];

$startDate = $_REQUEST ['startDate'];
$endDate = $_REQUEST ['endDate'];
$airlineId = $_REQUEST ['airline'];
$platforms = $_REQUEST ['platform'];
$configTypes = $_REQUEST ['config'];
$softwares = $_REQUEST ['software'];
$tailsigns = $_REQUEST ['tailsign'];
$status = $_REQUEST ['status'];

if($filter == 'airline_type_platform'){
	$query = "SELECT distinct acronym, type, platform from airlines JOIN aircrafts ON airlines.id=aircrafts.airlineId";
	$result = mysqli_query($dbConnection, $query);
	$returnResult = array();
	if ($result && mysqli_num_rows ( $result ) > 0) {
		while($row = mysqli_fetch_assoc($result)){
			if(array_key_exists($row['acronym'],$returnResult)){
				if(array_key_exists($row['type'],$returnResult[$row['acronym']])){
					$returnResult[$row['acronym']][$row['type']][] = $row['platform'];
				}else{
					$returnResult[$row['acronym']][$row['type']] = array($row['platform']);
				}
			}else{
				$returnResult[$row['acronym']] = array($row['type']=>array($row['platform']));
			}
		}
	}
	echo json_encode($returnResult);
}
else if($filter == 'lookup_history_data'){
	$query = "SELECT * from banalytics.SPNL_upload where software_version<>'' AND upload_date between '$startDate 00:00:00' AND '$endDate 23:59:59' ";
	
		if(is_array($airlineId)) {
            $query .= " and customer in (select acronym from airlines where id in (";
            foreach ($airlineId as $airline) {
                $query .=  $airline . ",";
            }
            $query = rtrim($query, ",") . "))";
        } 
        
        if(is_array($platforms)) {
            $query .= " and platform in (";
            foreach ($platforms as $platform) {
                $query .= "'" . $platform . "',";
            }
            $query = rtrim($query, ",") . ")";
        }
        
        if(is_array($configTypes)) {
            $query .= " and aircraft_type in (";
            foreach ($configTypes as $configType) {
                $query .= "'" . $configType . "',";
            }
            $query = rtrim($query, ",") . ")";
        } 
        if(is_array($softwares)) {
            $query .= " and software_version in (";
            foreach ($softwares as $software) {
                $query .= "'" . $software . "',";
            }
            $query = rtrim($query, ",") . ")";
        } 
        if(is_array($status)) {
            $query .= " and status in (";
            foreach ($status as $stat) {
                $query .= "'" . $stat . "',";
            }
            $query = rtrim($query, ",") . ")";
        } 
        error_log('SPNL Query '.$query);
	$result = mysqli_query($dbConnection, $query);
	$returnResult = array();
	if ($result && mysqli_num_rows ( $result ) > 0) {
		while($row = mysqli_fetch_assoc($result)){
			$returnResult[] = $row;
		}
	}
	echo json_encode($returnResult);
}
mysqli_close($dbConnection);
?>