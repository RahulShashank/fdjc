<?php
require_once "../database/connecti_database.php";
//SCRIPT CONFIG PARAMETERS
$echoScreen = false; // Enable/disable screen echo
$ALT_LRU_MATCH_COUNT = 3; // Number of alternate LRUs to be considered per SPNL LRU
$ignore_SPNL_LRUs = array("sys cfg", "app config", "app cfg", "sys config", "security"); // List of SPNL LRUs to be ignored

$log_filename = "";

SWVersionUpdateDriver(1, 965, '2017-08-28', '2017-08-29');

function SWVersionUpdateDriver($airlineID, $aircraftID, $startDate, $endDate){
	try {
		$time_start = microtime(true);
		$GLOBALS['log_filename'] = "../logs/Airline_".$airlineID."_Aircraft_".$aircraftID."_Past_Flight_SW_Update_Log_".date("Y-m-d_h:i:sa").".txt";
		writeLog("Script Starts");
		writeLog("Script config parameters: \nAirline ID: $airlineID\nAircraft ID: $aircraftID\nStart Date: $startDate\nEnd Date: $endDate\nScreen Echo: ".(($GLOBALS['echoScreen'])? "true": "false")."\nAlternate LRU Count: $ALT_LRU_MATCH_COUNT\nSPNL LRU Ignore List: ".join(",", $GLOBALS['ignore_SPNL_LRUs']));
		// The Main driving section
		$OUTPUT = updatePastFlightSWVersion($airlineID, $aircraftID, $startDate, $endDate);
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		writeLog("Total Script Execution Time: $time seconds");
		writeLog("Script Terminates");
		// compress the log file to .gz and delete the .txt file
		$fp = gzopen ($GLOBALS['log_filename'].".gz", 'w9');
		gzwrite ($fp, file_get_contents($GLOBALS['log_filename']));
		gzclose($fp);
		unlink($GLOBALS['log_filename']);
		return $OUTPUT;
	} catch (Exception $e) {
		writeLog('Error occured while Execution: ',  $e->getMessage());
	} finally{
		mysqli_close($GLOBALS['dbConnection']);
	}
}

function updatePastFlightSWVersion($airlineId, $aircraftId, $fromDate, $toDate){
	// $fromDate and $toDate format is : YYYY-MM-DD
	//validate input date formats
	if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$fromDate)==false) {
		return array("status"=>false, "message"=>"Invalid fromDate format, Expected format is YYYY-MM-DD");
	}
	if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$toDate)==false) {
		return array("status"=>false, "message"=>"Invalid toDate format, Expected format is YYYY-MM-DD");
	}

	// deduced Inputs
	$customer = '';
	$aircraft_type = '';
	$platform = '';
	$databaseName = '';

	writeLog("Retrieving Airline-Aircraft data...");
	//GET the deduced inputs
	$aircraft_query = "SELECT A.acronym, B.type, B.platform, B.databaseName FROM banalytics.airlines A JOIN banalytics.aircrafts B ON A.id=B.airlineId WHERE A.id=".$airlineId." AND B.id=".$aircraftId.";";
	$result = mysqli_query($GLOBALS['dbConnection'], $aircraft_query);
	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_assoc($result);
		$customer = $row['acronym'];
		$aircraft_type = $row['type'];
		$platform = $row['platform'];
		$databaseName = $row['databaseName'];
		writeLog("Retrieved-> Customer: ".$customer.", Aircraft_type: ".$aircraft_type.", Platform: ".$platform.", DatabaseName: ".$databaseName);
	}
	else{
		writeLog("Failed to get Aircraft & Airline data for airlineId=".$airlineId." and aircraftId=".$aircraftId);
		return array("status"=>false, "message"=>"Failed to get aircraft & airline data for airlineId=".$airlineId." and aircraftId=".$aircraftId);
	}

	writeLog("Retrieving list of SPNL Config uploads...");
	// Get list of SW Config File Uploads
	$swConfig_query = "SELECT * FROM banalytics.SPNL_upload WHERE customer='".$customer."' AND aircraft_type='".$aircraft_type."' AND platform='".$platform."' ORDER BY CAST(SUBSTR(software_version, 2, 4) AS UNSIGNED) DESC;";
	$result = mysqli_query($GLOBALS['dbConnection'], $swConfig_query);
	$swConfigs = array();
	if ($result && mysqli_num_rows ( $result ) > 0) {
		while($row=mysqli_fetch_assoc($result)){
			$swConfigs[] = $row;
		}
		writeLog("Number of SPNL Config retrieved: ".mysqli_num_rows($result));
	}else{
		writeLog("No SPNL Config found for customer=".$customer.", aircraft_type=".$aircraft_type." and platform=".$platform);
		return array("status"=>false, "message"=>"No SPNL Config found for customer=".$customer.", aircraft_type=".$aircraft_type." and platform=".$platform);
	}

	//Retrieve LRU data for each SW Config
	writeLog("Retrieving LRU data for the SPNL Configs...");
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
			// prepare an array of SPNL LRUs
			$swConfig['LRU_NAME_SET'] = array_keys($swConfig['LRU_PARTNUMBERS']);
		}else{
			writeLog("No LRU-PartNumber data found for SPNL_config:id= ".$swConfig['id']);
			continue;
		}
		
		// $SPNL_HOSTNAME_Mapping holds the mapping between SPNL Hostnames and BIT_lru hostnames
		$SPNL_HOSTNAME_Mapping = get_SPNL_LRUHostName_Mapping($airlineId, $aircraft_type, $platform, $databaseName, $swConfig);
		//var_dump($SPNL_HOSTNAME_Mapping);
		if($SPNL_HOSTNAME_Mapping===false){
			// SPNL-Type/SubType Mapping Not found
			return array("status"=>false, "message"=>"SPNL-Type/SubType Mapping Not found");
		}else if(empty($SPNL_HOSTNAME_Mapping)){
			// No LRU Found for a Type/Sub-Type combination
			$swConfig['SPNL_HOSTNAME_MAPPING'] = null;
		}else if(!empty($SPNL_HOSTNAME_Mapping)){
			$swConfig['SPNL_HOSTNAME_MAPPING'] = $SPNL_HOSTNAME_Mapping;
		}
	}
	writeLog("LRU data for SPNL Configs Retrieved.");

	writeLog("Retrieving list of flights between ".$fromDate." and ".$toDate);
	// Retrieve list of flights that are to be considered based on input date duration
	$get_Flight_query = "SELECT * FROM ".$databaseName.".SYS_flight WHERE processing_status IS NULL AND createDate BETWEEN '".$fromDate." 00:00:00' AND '".$toDate." 23:59:59' ORDER BY createDate DESC;";
	$flights = array();
	$result = mysqli_query($GLOBALS['dbConnection'], $get_Flight_query);
	if ($result && mysqli_num_rows ( $result ) > 0) {
		while($row=mysqli_fetch_assoc($result)){
			$flights[] = $row; 
		}
		$flight_count = mysqli_num_rows($result);
		writeLog("Number of flights Retrieved: ".$flight_count);
	}else{
		writeLog("No Flights found in ".$databaseName.".SYS_flight for duration ".$fromDate." to ".$toDate);
		return array("status"=>false, "message"=>"No flights found in ".$databaseName.".SYS_flight for duration ".$fromDate." to ".$toDate);
	}
	
	$flights_updated = 0;
	writeLog("Partnumber Matching Begins.");
	foreach($flights as $flight){
		writeLog("--- FlightLegId ".$flight['idFlightLeg']." PN Matching Starts ---");
		foreach($swConfigs as $swConfig){
			//Retrieve List of Part Numbers for the current Offload from BIT_confSw
			if(is_null($swConfig['SPNL_HOSTNAME_MAPPING'])){
				writeLog("Skipping SPNL_upload: id=".$swConfig['id']." because No LRU was found for some Type/Sub-Type");
				continue;
			}
			
			// Okay, In BIT_Confsw we store the offload datetime in the lastUpdate field
			// so, for each flightLegId I will need to get its corresponding entry in the offloads table and get its offloadDate 
			// and then get all the data by comparing the offloadDates of all

			//get offload date for the curent flightLegId
			$getOffloadDate_query = "SELECT offloadDate FROM ".$databaseName.".offloads WHERE idFlightLeg=".$flight['idFlightLeg'].";";
			$result = mysqli_query($GLOBALS['dbConnection'], $getOffloadDate_query);
			if ($result && mysqli_num_rows ( $result ) > 0) {
				$row = mysqli_fetch_assoc($result);
				$flightOffloadDate = $row['offloadDate'];
			}else{
				writeLog("Offload record not found in ".$databaseName.".offloads for idFlightLeg=".$flight['idFlightLeg']);
				break;
			}
			
			$LRU_Hostnames = array();
			foreach($swConfig['SPNL_HOSTNAME_MAPPING'] as $SPNL_Hostname => $hostname_array){
				$LRU_Hostnames = array_merge($LRU_Hostnames, $hostname_array);
			}
			$BIT_LRU_PartNumbers = array();
			foreach($LRU_Hostnames as $LRU_Hostname){
				$BIT_LRU_PartNumbers[$LRU_Hostname] = array();
			}
			
			//get partnumbers for all the flightLegIds which have greater offload date than the current flightleg
			$LRU_HostName_string = join("','", $LRU_Hostnames);
			$lru_pn_limit = count($LRU_Hostnames) * 25;	// Assuming each LRU takes 25 Partnumbers at max
			$get_SW_PN_query = "SELECT hostName, partNumber, lastUpdate, idFlightLeg FROM ".$databaseName.".BIT_confSw WHERE lastUpdate>='".$flightOffloadDate."' AND hostName IN ('".$LRU_HostName_string."') ORDER BY lastUpdate LIMIT ".$lru_pn_limit.";";
			$result = mysqli_query($GLOBALS['dbConnection'], $get_SW_PN_query);
			if ($result && mysqli_num_rows ( $result ) > 0) {
				while($row=mysqli_fetch_assoc($result)){
					$BIT_LRU_PartNumbers[$row['hostName']][] = $row;
				}
				// Keep the required partnumbers corresponding to first FlightLegID and get rid of others
				foreach($BIT_LRU_PartNumbers as $hostName=>$PN_rows){
					$id = null;
					$index = 0;
					$relevent_PN_rows = $PN_rows;
					foreach($PN_rows as $row){
						if(is_null($id)){
							$id = $row['idFlightLeg'];
						}else if($id!==$row['idFlightLeg']){
							$relevent_PN_rows = array_splice($PN_rows, 0, $index);
							break;
						}
						$index += 1;
					}
					$PN_array = array();
					foreach($relevent_PN_rows as $conf_row){
						$PN_array[] = $conf_row['partNumber'];
					}
					$BIT_LRU_PartNumbers[$hostName] = $PN_array;
				}

				// Compare BIT_LRU_PartNumbers and SWConfigPartnumbers
				$LRUs_Matched_Mismatched_string = '';
				$PartNumberExactMatch = True;
				foreach($swConfig['SPNL_HOSTNAME_MAPPING'] as $SPNL_LRU => $BIT_LRU_Hostname_Array){
					$no_lru_matched = true;
					foreach($BIT_LRU_Hostname_Array as $BIT_LRU_Hostname){
						//this does subset check that SPNL_PNs<BIT_LRU_PNs
						$spnl_lrus_partnumbers = $SPNL_LRU."(SPNL):".join(",", $swConfig['LRU_PARTNUMBERS'][$SPNL_LRU])." VS ".$BIT_LRU_Hostname."(BIT_lru):".join(",", $BIT_LRU_PartNumbers[$BIT_LRU_Hostname])."\n";
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
					writeLog("Partnumbers Matched for idFlightLeg=".$flight['idFlightLeg']." and SPNL_upload:id=".$swConfig['id']);
					$update_SW_Version_Query = "UPDATE ".$databaseName.".SYS_flight SET lookup_upload_id=".$swConfig['id'].", software_version='".$swConfig['software_version']."', version_updated_date=NOW(), processing_status='PROCESSED', process_log = concat(ifnull(process_log,''), NOW(), '=|BEGIN|Exact Match, SW Version updated to: ".$swConfig['software_version'].", SPNL_upload:id= ".$swConfig['id'].", LRUs Considered: ".join(",", $LRU_Hostnames)."|END|') WHERE idFlightLeg=".$flight['idFlightLeg'].";";
					if (mysqli_query($GLOBALS['dbConnection'], $update_SW_Version_Query)) {
						if(mysqli_commit($GLOBALS['dbConnection'])){
							$flights_updated += 1;
							writeLog("SW version updated successfully for idFlightLeg=".$flight['idFlightLeg']."\nPartnumber Matching as Follows:\n".$LRUs_Matched_Mismatched_string);
						}
					}
				}else{
					// Move on to the next SW Config
					writeLog("Partnumber Mismatch for SPNL_upload:id= ".$swConfig['id'].", LRUs Considered: '".$LRU_HostName_string."', for idFlightLeg=".$flight['idFlightLeg']);
					writeLog("Mismatch Reason: \n".$LRUs_Matched_Mismatched_string);
					//continue;
				}
			}else{
				writeLog("SW Config not found in BIT_confSw for: '".$LRU_HostName_string."' and idFlightLeg=".$flight['idFlightLeg']);
				break;
			}
		}
		writeLog("--- FlightLegId ".$flight['idFlightLeg']." PN Matching Ends ---");
	}
	writeLog("Number of Flight's Software Version Updated: ".$flights_updated."/".$flight_count);
	return array("status"=>true, "message"=>"Script ran successfully without any error, Please check log file ".$GLOBALS['log_filename'].".gz for details");
}

function get_SPNL_LRUHostName_Mapping($airlineId, $aircraft_type, $platform, $databaseName, $swConfig){
	// returns mapping of SPNL LRU Names to BITE LRU Hostname
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
		writeLog("SPNL-Type/SubType Mapping Not found for airlineId=".$airlineId.", ac_config=".$aircraft_type." and platform=".$platform." in banalytics.SPNL_lruType");
		return false;
	}
	
	$SPNL_HOSTNAME_Mapping = array();
	//get list of LRU Hostnames to lookup PN for
	foreach($swConfig['LRU_NAME_SET'] as $lru_name){
		if(in_array(strtolower($lru_name), $GLOBALS['ignore_SPNL_LRUs'])===false){
			$no_lru_found = true;
			$lru_not_found_message = '';
			$SPNL_HOSTNAME_Mapping[$lru_name] = array();
			foreach($SPNL_MAPPING_LRUS[$lru_name] as $LRU_TypeSubType_row){
				$lru_hostname_query = "SELECT DISTINCT hostName FROM ".$databaseName.".BIT_lru WHERE lruType=".$LRU_TypeSubType_row['lruType']." AND lruSubType=".$LRU_TypeSubType_row['lruSubType']." AND hostName<>'' AND hostName IS NOT NULL;";
				$query_result = mysqli_query($GLOBALS['dbConnection'], $lru_hostname_query);
				$result = array();
				while($row=mysqli_fetch_assoc($query_result)){
					$result[] = $row;
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
			// If No LRU Found for any pair of Type/SubType
			if($no_lru_found){
				writeLog($lru_not_found_message." for SPNL_upload:id=".$swConfig['id']." in ".$databaseName.".BIT_lru");
				return array();
			}
		}
	}
	// var_dump($SPNL_HOSTNAME_Mapping);
	return $SPNL_HOSTNAME_Mapping;
}

function writeLog($log_text){
	if($GLOBALS['echoScreen']){
		echo date("Y-m-d_h:i:sa")." # ".$log_text."\n";
	}
	$handle = fopen($GLOBALS['log_filename'], 'a');
	fwrite($handle, date("Y-m-d_h:i:sa")." # ".$log_text."\n");
	fclose($handle);
}

?>
