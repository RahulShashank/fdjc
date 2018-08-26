<?php
require_once "../database/connecti_database.php";

$project = $_POST['project'];
$releaseTag = $_POST['releaseTag'];
$customer = $_POST['customer'];
$aircraftType = $_POST['aircraftType'];
$platform = $_POST['platform'];
$version = $_POST['version'];
$baseline = $_POST['baseline'];
$baselineMediaPN = $_POST['baselineMediaPN'];
$currentReleaseMediaPN = $_POST['currentReleaseMediaPN'];
$filename = $_POST['filename'];
$LRU_LIST = $_POST['LRU_LIST'];
error_log('Filename : '.$filename);
$historyInsertQuery = "INSERT INTO banalytics.SPNL_upload(`project`, `release_tag`, `customer`, `aircraft_type`, `platform`, `software_baseline`, `software_version`, `baseline_media_partnumber`, `current_release_media_partnumber`, `upload_date`, `aircrafts_affected_id`, `filename`, `status`) VALUES('".$project."','".$releaseTag."', '".$customer."', '".$aircraftType."', '".$platform."', '".$baseline."', '".$version."', '".$baselineMediaPN."', '".$currentReleaseMediaPN."', NOW(),'', '".$filename."', 'UNPROCESSED')";

if (mysqli_query($dbConnection, $historyInsertQuery)) {
	if(mysqli_commit($dbConnection)){
		$getUploadedFileIdQuery = "SELECT LAST_INSERT_ID();";
		$result = mysqli_query($dbConnection, $getUploadedFileIdQuery);
		$row = mysqli_fetch_assoc($result);
		$fileUploadedID = $row['LAST_INSERT_ID()'];
		//echo $fileUploadedID;
		$insertLRUQuery = "INSERT INTO banalytics.SPNL_config(`uploaded_file_id`, `lru_name`, `sw_partnumber`, `nomenclature`, `baseline`, `current_partnumber`) VALUES";
		foreach($LRU_LIST as $lru){
			//echo json_encode($lru);
			$insertLRUQuery = $insertLRUQuery."(".$fileUploadedID.",'".$lru['lru_name']."', '".$lru['sw_partnumber']."', '".$lru['nomencalture']."', '".$lru['baseline']."', '".$lru['current_partnumber']."'), ";
		}
		$insertLRUQuery = substr($insertLRUQuery, 0, -2).";";
		if (mysqli_query($dbConnection, $insertLRUQuery)) {
			if(mysqli_commit($dbConnection)){
				echo json_encode(array("status"=>"success", "message"=>"LRU data commited succesfully"));
			}else{
				echo json_encode(array("status"=>"fail", "query"=>$insertLRUQuery, "message"=>"Error: ".mysqli_error($dbConnection)));
			}
		}else{
			echo json_encode(array("status"=>"fail", "query"=>$insertLRUQuery, "message"=>"Error: ".mysqli_error($dbConnection)));
		}
	}else{
		echo json_encode(array("status"=>"fail", "query"=>$historyInsertQuery, "message"=>"Error: ".mysqli_error($dbConnection)));
	}
}else{
	echo json_encode(array("status"=>"fail", "query"=>$historyInsertQuery, "message"=>"Error: ".mysqli_error($dbConnection)));
}
mysqli_close($dbConnection);
?>