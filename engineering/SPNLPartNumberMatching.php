<?php
require_once "../database/connecti_database.php";

// list of SPNL lrus that are needed to be ignored while partnumber matching
$ignore_SPNL_LRUs = array("sys cfg", "app config", "app cfg", "sys config", "security");
// Number of alternate LRUs to be considered per SPNL LRU
$ALT_LRU_MATCH_COUNT = 3;
// controls if process_log field should be logged in a detailed fashion or not
$detailLog = true;

function updateSWVersion($airlineId, $aircraftId, $idFlightLeg){
	// deduced Inputs
	$customer = '';
	$aircraft_type = '';
	$platform = '';
	$databaseName = '';

	//GET the deduced inputs
	$aircraft_query = "SELECT A.acronym, B.type, B.platform, B.databaseName FROM banalytics.airlines A JOIN banalytics.aircrafts B ON A.id=B.airlineId WHERE A.id=".$airlineId." AND B.id=".$aircraftId.";";
	$result = mysqli_query($GLOBALS['dbConnection'], $aircraft_query);
	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_assoc($result);
		$customer = $row['acronym'];
		$aircraft_type = $row['type'];
		$platform = $row['platform'];
		$databaseName = $row['databaseName'];
	}
	else{
		updateProcess_log("Failed to get aircraft & airline data", $idFlightLeg, $databaseName);
		return false;
	}

	// Get list of SW Config File Uploads
	$swConfig_query = "SELECT * FROM banalytics.SPNL_upload WHERE customer='".$customer."' AND aircraft_type='".$aircraft_type."' AND platform='".$platform."' ORDER BY CAST(SUBSTR(software_version, 2, 4) AS UNSIGNED) DESC;";
	$result = mysqli_query($GLOBALS['dbConnection'], $swConfig_query);
	$swConfigs = array();
	if ($result && mysqli_num_rows ( $result ) > 0) {
		while($row=mysqli_fetch_assoc($result)){
			$swConfigs[] = $row;
		}
	}else{
		updateProcess_log("No SPNL Config found", $idFlightLeg, $databaseName);
		return false;
	}

	//Retrieve LRU data for each SW Config
	foreach($swConfigs as &$swConfig){
		$lru_query = "SELECT lru_name, CONCAT(sw_partnumber, current_partnumber) as partnumber FROM banalytics.SPNL_config WHERE uploaded_file_id=".$swConfig['id'].";";
		$result = mysqli_query($GLOBALS['dbConnection'], $lru_query);
		$swConfig['LRU_PARTNUMBERS'] = array();
		if ($result && mysqli_num_rows ( $result ) > 0) {
			while($row = mysqli_fetch_assoc($result)){
				if(array_key_exists($row['lru_name'], $swConfig['LRU_PARTNUMBERS'])){
					$swConfig['LRU_PARTNUMBERS'][$row['lru_name']][] = $row['partnumber'];
				}else{
					$swConfig['LRU_PARTNUMBERS'][$row['lru_name']] = array($row['partnumber']);
				}
			}
			$swConfig['LRU_NAME_SET'] = array_keys($swConfig['LRU_PARTNUMBERS']);
		}else{
			updateProcess_log("No LRU data found for SPNL_config:id= ".$swConfig['id'], $idFlightLeg, $databaseName);
			continue;
		}
		
		$SPNL_HOSTNAME_Mapping = get_SPNL_LRUHostName_Mapping($airlineId, $aircraft_type, $platform, $idFlightLeg, $databaseName, $swConfig);
		if($SPNL_HOSTNAME_Mapping===false){
			// SPNL-Type/SubType Mapping Not found
			return false;
		}else if(empty($SPNL_HOSTNAME_Mapping)){
			// If No LRU Found for any pair of Type/SubType for an LRU
			continue;
		}
		
		//Retrieve List of Part Numbers for the current Offload from BIT_confSw
		$LRU_Hostnames = array();
		foreach($SPNL_HOSTNAME_Mapping as $SPNL_Hostname => $hostname_array){
			$LRU_Hostnames = array_merge($LRU_Hostnames, $hostname_array);
		}
		$BIT_LRU_PartNumbers = array();
		foreach($LRU_Hostnames as $LRU_Hostname){
			$BIT_LRU_PartNumbers[$LRU_Hostname] = array();
		}

		$LRU_HostName_string = join("','", $LRU_Hostnames);
		$LRU_PartNumberhistory_Query = "SELECT host_name, part_number FROM ".$databaseName.".BIT_confSw_history WHERE FlightLeg_id=".$idFlightLeg." AND host_name IN ('".$LRU_HostName_string."')";
		error_log("LRU_PartNumber_history Query: $LRU_PartNumberhistory_Query");
		$result = mysqli_query($GLOBALS['dbConnection'], $LRU_PartNumberhistory_Query);
		if ($result && mysqli_num_rows ( $result ) > 0) {
		    while($row=mysqli_fetch_assoc($result)){
		        $BIT_LRU_PartNumbers[$row['host_name']][] = $row['part_number'];
		    }
		} else {
		    updateProcess_log("SW Config not found in BIT_confSw_history table for: ".join(",", $LRU_Hostnames), $idFlightLeg, $databaseName);
		    return false;
		}
		
		// Compare BIT_LRU_PartNumbers and SWConfigPartnumbers
		$LRUs_Matched_Mismatched_string = '';
		$PartNumberExactMatch = True;
		foreach($SPNL_HOSTNAME_Mapping as $SPNL_LRU => $BIT_LRU_Hostname_Array){
			$no_lru_matched = true;
			foreach($BIT_LRU_Hostname_Array as $BIT_LRU_Hostname){
				//this does subset check that SPNL_PNs<BIT_LRU_PNs
				if($GLOBALS['detailLog']){
					$spnl_lrus_partnumbers = $SPNL_LRU."(SPNL):".join(",", $swConfig['LRU_PARTNUMBERS'][$SPNL_LRU])." VS ".$BIT_LRU_Hostname."(BIT_lru):".join(",", $BIT_LRU_PartNumbers[$BIT_LRU_Hostname])."\n";
				}else{
					$spnl_lrus_partnumbers = $SPNL_LRU."(SPNL) VS ".$BIT_LRU_Hostname."(BIT_lru)";
				}
				if(array_diff($swConfig['LRU_PARTNUMBERS'][$SPNL_LRU], $BIT_LRU_PartNumbers[$BIT_LRU_Hostname])){
					// Not a subset(not a match)
					$LRUs_Matched_Mismatched_string .= "Part Numbers Mismatched, ".$spnl_lrus_partnumbers."\n";
				}else{
					// its a subset(its a match)
					$LRUs_Matched_Mismatched_string .= "Part Numbers Matched, ".$spnl_lrus_partnumbers."\n";
					$no_lru_matched = false;
					break;
				}
			}
			if($no_lru_matched){
				$PartNumberExactMatch = false;
				break;
			}
		}
		
		//$LRU_considered = join(",", array_values($swConfig['SPNL_HOSTNAME_MAPPING']));
		if($PartNumberExactMatch){
			// Update SW Version of the Offload in the SYS_flight table and Exit the script
		    updateProcess_log("Partnumbers Matched for idFlightLeg=".$idFlightLeg." and SPNL_upload:id=".$swConfig['id'], $idFlightLeg, $databaseName);
		    error_log("Partnumbers Matched for idFlightLeg=".$idFlightLeg." and SPNL_upload:id=".$swConfig['id'], $idFlightLeg, $databaseName);
		    $update_SW_Version_Query = "UPDATE ".$databaseName.".SYS_flight SET lookup_upload_id=".$swConfig['id'].", software_version='".$swConfig['software_version']."', version_updated_date=NOW(), processing_status='PROCESSED', process_log = concat(ifnull(process_log,''), NOW(), '=|Exact Match, SW Version updated to: ".$swConfig['software_version'].", SPNL_upload:id= ".$swConfig['id'].", LRUs Considered: ".join(",", $LRU_Hostnames)."|') WHERE idFlightLeg=".$idFlightLeg.";";
			if (mysqli_query($GLOBALS['dbConnection'], $update_SW_Version_Query)) {
				if(mysqli_commit($GLOBALS['dbConnection'])){
					updateProcess_log("SW version updated successfully for idFlightLeg=".$idFlightLeg."\nPartnumber Matching as Follows:\n".$LRUs_Matched_Mismatched_string, $idFlightLeg, $databaseName);
					error_log("SW version updated successfully for idFlightLeg=".$idFlightLeg."\nPartnumber Matching as Follows:\n".$LRUs_Matched_Mismatched_string, $idFlightLeg, $databaseName);
					$update_aircrafts_sw_version = "UPDATE banalytics.aircrafts SET software= CASE WHEN (SELECT idFlightLeg FROM ".$databaseName.".SYS_flight ORDER BY createDate DESC LIMIT 1)=".$idFlightLeg." THEN '".$swConfig['software_version']."' ELSE software END where id=$aircraftId";
					if (mysqli_query($GLOBALS['dbConnection'], $update_aircrafts_sw_version)) {
						if(mysqli_commit($GLOBALS['dbConnection'])){
						    error_log("aircraft table updated for aircraft id $aircraftId");
						    error_log("Aircraft update query: " . $update_aircrafts_sw_version);
							// Return with Success!
							return true;
						} else {
						    error_log("Error in updating aircrafts table: ". mysqli_errno($GLOBALS['dbConnection']));
						}
					} else {
					    error_log("Error in executing udpate query aircrafts table for flight leg id $idFlightLeg : " . mysqli_errno($GLOBALS['dbConnection']));
					}
				}
			}
		}else{
			// Move on to the next SW Config
			updateProcess_log("Partnumber Mismatch for SPNL_upload:id= ".$swConfig['id'].", LRUs Considered: ".join(",", $LRU_Hostnames).", for idFlightLeg=".$idFlightLeg."\nMismatch Reason: \n".$LRUs_Matched_Mismatched_string, $idFlightLeg, $databaseName);
			//updateProcess_log("Mismatch Reason: \n".$LRUs_Matched_Mismatched_string, $idFlightLeg, $databaseName);
		}
	}
	return false;
}

function updateProcess_log($process_log, $idFlightLeg, $databaseName){
	$update_offload_status_query = "UPDATE ".$databaseName.".SYS_flight SET processing_status='PROCESSED', process_log = concat(ifnull(process_log,''), NOW(), '=|".$process_log."|') WHERE idFlightLeg=".$idFlightLeg.";";
	if(mysqli_query($GLOBALS['dbConnection'], $update_offload_status_query)){
		mysqli_commit($GLOBALS['dbConnection']);
	}
}

function get_SPNL_LRUHostName_Mapping($airlineId, $aircraft_type, $platform, $idFlightLeg, $databaseName, $swConfig){
	// returns mapping of SPNL LRU Names to BITE LRU Hostname(one to many mapping)
	$SPNL_MAPPING_LRUS = array();
	$SPNL_TypeSubtype_Query = "SELECT lruName, lruType, lruSubType FROM banalytics.SPNL_lruType WHERE airlineId=".$airlineId." AND ac_config='".$aircraft_type."' AND platform='".$platform."';";
	$result = mysqli_query($GLOBALS['dbConnection'], $SPNL_TypeSubtype_Query);
	if ($result && mysqli_num_rows ( $result ) > 0) {
		while($row=mysqli_fetch_assoc($result)){
			if (array_key_exists($row['lruName'], $SPNL_MAPPING_LRUS)){
				$SPNL_MAPPING_LRUS[$row['lruName']][] = $row;
			}else{
				$SPNL_MAPPING_LRUS[$row['lruName']] = array($row);
			}
		}
	}else{
		updateProcess_log("SPNL-Type/SubType Mapping Not found for SPNL_upload:id=".$swConfig['id']." in banalytics.SPNL_lruType", $idFlightLeg, $databaseName);
		return false;
	}
	
	$SPNL_HOSTNAME_Mapping = array();
	//get list of LRU Hostnames to lookup PN for
	foreach($swConfig['LRU_NAME_SET'] as $lru_name){
		if(in_array(strtolower($lru_name), $GLOBALS['ignore_SPNL_LRUs'])===false){
			$no_lru_found = true;
			$lru_not_found_message = '';
			$SPNL_HOSTNAME_Mapping[$lru_name] = array();
			if(array_key_exists($lru_name, $SPNL_MAPPING_LRUS)){
				foreach($SPNL_MAPPING_LRUS[$lru_name] as $LRU_TypeSubType_row){
					$lru_hostname_query = "SELECT DISTINCT hostName FROM ".$databaseName.".BIT_lru WHERE lruType=".$LRU_TypeSubType_row['lruType']." AND lruSubType=".$LRU_TypeSubType_row['lruSubType']." AND hostName<>'' AND hostName IS NOT NULL;";
					$query_result = mysqli_query($GLOBALS['dbConnection'], $lru_hostname_query);
					$result = array();
					if($query_result) {
					    while($row=mysqli_fetch_assoc($query_result)){
					        $result[] = $row;
					    }
					}
					
					//$result = mysqli_fetch_all($result);
					$lru_count = count($result);
					if ($result && $lru_count > 0) {
						$no_lru_found = false;
						if($lru_count==1){
							$SPNL_HOSTNAME_Mapping[$lru_name][] = $result[0]['hostName'];
						}
						else if($lru_count==2){
							$SPNL_HOSTNAME_Mapping[$lru_name][] = $result[0]['hostName'];
							$SPNL_HOSTNAME_Mapping[$lru_name][] = $result[1]['hostName'];
						}
						else{
							// SELECT ALT_LRU_MATCH_COUNT number of LRUs Randomly
							$lru_selected_count = 0;
							while($lru_selected_count < $GLOBALS['ALT_LRU_MATCH_COUNT']){
								$index = mt_rand(0,$lru_count-1);
								if(in_array($result[$index]['hostName'], $SPNL_HOSTNAME_Mapping[$lru_name])===false){
									$SPNL_HOSTNAME_Mapping[$lru_name][] = $result[$index]['hostName'];
									$lru_selected_count += 1;
								}
							}
						}
					}else{
						$lru_not_found_message .= "No LRU found for Type:".$LRU_TypeSubType_row['lruType']." and Sub-Type:".$LRU_TypeSubType_row['lruSubType']."\n";
					}
					// Found all the LRUs required, Stop iterating to get LRUs from other Type/SubType
					if(count($SPNL_HOSTNAME_Mapping[$lru_name])==$GLOBALS['ALT_LRU_MATCH_COUNT']){
						break;
					}
				}
			}else{
				updateProcess_log("No LRU Type/Sub-Type mapping found for ".$lru_name, $idFlightLeg, $databaseName);
				return array();
			}
			// If No LRU Found for any pair of Type/SubType
			if($no_lru_found){
				updateProcess_log($lru_not_found_message." for SPNL_upload:id=".$swConfig['id']." in ".$databaseName.".BIT_lru", $idFlightLeg, $databaseName);
				return array();
			}
		}
	}
	// var_dump($SPNL_HOSTNAME_Mapping);
	return $SPNL_HOSTNAME_Mapping;
}
?>