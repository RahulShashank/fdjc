<?php
// Start the session
session_start();
ini_set('max_execution_time', 2000);

if(!$_SESSION['disablePermissionCheck']) {
    require_once('checkEngineeringPermission.php');
}

class BiteAvantXmlParser {
	var $arrOutput = array ();
	var $resParser;
	var $strXmlData;
	var $dbObj = NULL;
	var $iFlightLegId = 0;
	var $strHostName = "";
	var $idefault = 0;
	var $bRemovalFlag = false;
	var $strErrMsg = "";
	var $iNumOfEquip = 0;
	var $iNumOfFaults = 0;
	var $iNumOfFailures = 0;
	var $iNumOfFlightLeg = 0;
	var $isWrongTailSign = false;

	var $strFlightLeg = "";
	var $offloadDate = "";
	var $maxOffloadDate = "";
	var $tailsign = "";
	var $type = "";

	var $flightPhasesQuery = "";
	var $confSwQuery = "";
	var $confSwQueries = array ();
	var $confSwQueryCount = 0;
	var $nbOfConfSw = 0;
	var $cumulativeTotalsQuery = "";
	var $eventsQuery = "";
	var $failuresQuery = "";
	var $faultsQuery = "";
	var $extappeventsQuery = "";
	
	var $airlineId = 0;
	
	function init($dbobj, $tailsign, $type, $flightLegIdCount, $offloadDate, $maxOffloadDate) {
		/*
		 * Testing ********************************************
		 * $db_server = 'localhost';
		 * $db = 'bite_biteifedb_test_test_10152014_tea';
		 * $db_username = 'dbtooluser';
		 * $db_password = 'dbtooluser123';
		 * $this->dbObj = @mysql_connect($db_server,$db_username,$db_password);
		 */
		$this->dbObj = $dbobj;
		$this->iFlightLegId = $flightLegIdCount;
		$this->offloadDate = $offloadDate;
		$this->maxOffloadDate = $maxOffloadDate;
		$this->tailsign = $tailsign;
		$this->type = $type;
		
		// Get the airlineId from tailsign
		$query = "select airlineId from banalytics.aircrafts where tailsign='$this->tailsign'";
		
		if ($stmt = $this->dbObj->prepare($query)) {
		    $stmt->execute();
		    $stmt->bind_result($airlineId);
		    while ($stmt->fetch()) {
// 		        echo "Airline Id is : $airlineId";
		        $this->airlineId = $airlineId;
		    }
		    $stmt->close();
		} else {
		    echo "Error in fetching ariline id" . mysqli_errno($this->dbObj);
		}
// 		if (! mysqli_query ( $this->dbObj, $strQuery )) {
// 		    echo "problem with query $strQuery<br>";
// 		    $this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysql_error () );
// 		    return 0;
// 		}
		
	}
	function parse($strInputXML) {
		$this->iNumOfEquip = 0;
		$this->iNumOfFaults = 0;
		$this->iNumOfFailures = 0;
		$this->iNumOfFlightLeg = 0;
		$this->resParser = xml_parser_create ();
		if ($this->resParser == NULL) {
			$this->strErrMsg = sprintf ( "Failed to create XML parser object.Your Current PHP version : <STRONG> %s <STRONG>.Upgarde to PHP 4 and above", phpversion () );
			return 0;
		}
		xml_set_object ( $this->resParser, $this );
		xml_set_element_handler ( $this->resParser, "tagOpen", "tagClosed" );
		
		xml_set_character_data_handler ( $this->resParser, "tagData" );
		xml_set_notation_decl_handler ( $this->resParser, "ext_ent_handler" );
		// xml_parser_set_option($xml_parser,XML_OPTION_TARGET_ENCODING, "ISO-8859-1").
		
		/*
		 * $this->strXmlData = xml_parse($this->resParser,$strInputXML );
		 * // if(!$this->strXmlData) {
		 * die(sprintf("XML error: %s at line %d",
		 * xml_error_string(xml_get_error_code($this->resParser)),
		 * xml_get_current_line_number($this->resParser)));
		 * }
		 */
		try {
			$fp = fopen ( "$strInputXML", "r" ); // open and reading a XML file.
			
			if (! $fp) {
				$this->strErrMsg = sprintf ( "Can't open %s for import", $strInputXML );
				return 0;
			}
			$strEndIncompleteTag = "";
			while ( $data = fread ( $fp, 4096 ) ) {
				$data = preg_replace ( '/[^[:print:]]/', '', $data );
				// $this->log($data);
				// $this->log("====");
				$bStatus = xml_parse ( $this->resParser, $data, feof ( $fp ) ); // or
				if (! $bStatus) {
					$this->log ( $data );
					$this->log ( xml_get_error_code ( $this->resParser ) );
					$this->strErrMsg = sprintf ( "XML error: %s at line %d", xml_error_string ( xml_get_error_code ( $this->resParser ) ), xml_get_current_line_number ( $this->resParser ) );
					return 0;
				}
			}
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
		xml_parser_free ( $this->resParser );
		// $this->iNumOfFileCnt++;
		
		if($this->isWrongTailSign) {
			// exit right away as we are not going to execute the queries 
			return 0;
		};

		// EXECUTE QUERIES
		if ($this->flightPhasesQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->flightPhasesQuery )) {
				$strQuery = $this->flightPhasesQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("flight phases query is empty");
		}
		

		
		if ($this->cumulativeTotalsQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->cumulativeTotalsQuery )) {
				$strQuery = $this->cumulativeTotalsQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("cumulativeTotalsQuery query is empty");
		}
		
		if ($this->eventsQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->eventsQuery )) {
				$strQuery = $this->eventsQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("events query is empty");
		}
		
		if ($this->failuresQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->failuresQuery )) {
				$strQuery = $this->failuresQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("failures query is empty");
		}
		
		if ($this->faultsQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->faultsQuery )) {
				$strQuery = $this->faultsQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("faults query is empty");
		}
		
		if ($this->extappeventsQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->extappeventsQuery )) {
				$strQuery = $this->extappeventsQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("extappevents query is empty");
		}
		
		return 1;
	}
	function ext_ent_handler($xml_parser, $ent, $base, $sysID, $pubID) {
		echo "$not<br />";
		echo "$sysID<br />";
		echo "$pubID<BR />";
	}
	function tagOpen($parser, $name, $attrs) {
		switch ($name) {
			case "FLIGHTLEGINFO" :
			case "FLIGHTPHASE" :
			case "EQUIPMENTDETAILS" :
			//case "STATICINFO" :
			case "HEALTHINFO" :
			case "FLIGHTLEGHEALTHINFO" :
			case "FINATAINFO" :			
			case "CONFSW" :
			case "REBOOT" :
			case "FAILURE" :
			case "FAULT" :
			case "EXTAPPEVENT" :
			case "SERVICEFAILURE" ://smita
				$this->arrOutput [$name] = array (
						"attrs" => $attrs 
				);
				break;			
			case "REMOVALTIME" :
				$this->arrOutput [$name] = array (
						"attrs" => $attrs 
				);
				break;
			case "EQUIPMENT" :
				$this->arrOutput [$name] = array (
						"attrs" => $attrs 
				);
				$this->strHostName = $this->arrOutput ['EQUIPMENT'] ['attrs'] ['HOSTNAME'];
				break;
			case "STATICINFO" :
				$this->arrOutput [$name] = array (
						"attrs" => $attrs 
				);
				$this->serialNumber = $this->arrOutput ['STATICINFO'] ['attrs'] ['SERIALNUMBER'];
				break;
			case "SERVICE" ://Smita
				$this->arrOutput [$name] = array (
						"attrs" => $attrs 
				);
				$this->idService = $this->arrOutput ['SERVICE'] ['attrs'] ['IDSERVICE'];
				break;				
			case "LRUREMOVAL" :
				$this->bRemovalFlag = true;
				break;
			default :
				// echo "opener name=>$name";
				break;
		}
	}
	function tagData($parser, $tagData) {
		if (trim ( $tagData )) {
			// echo "Data=== $tagData";
		}
	}
	function tagClosed($parser, $name) {
		if ($name == "LRUREMOVAL") {
			$this->bRemovalFlag = false;
		}
		$this->processTagData ( $name );
	}
	public function getErrMsg() {
		return $this->strErrMsg;
	}
	public function getFlighLegCnt() {
		return $this->iNumOfFlightLeg;
	}
	public function getEquipCnt() {
		return $this->iNumOfEquip;
	}
	public function getFailureCnt() {
		return $this->iNumOfFailures;
	}
	public function getFaultCnt() {
		return $this->iNumOfFaults;
	}
	function processTagData($tagclose) {
		$strQuery = "";
		
		if ($tagclose == "FLIGHTLEGINFO") {
		/*
			//TailSign Check Code added.
			$readTailsign = trim(trim($this->arrOutput['FLIGHTLEGINFO']['attrs']['AIRCRAFTTAILSIGN']),'.');
			//if(strpos($readTailsign, $this->tailsign) === false) {
			
			if(strpos($this->tailsign, $readTailsign) === false) {
				if(strpos($readTailsign, $this->type) === false){
					$this->isWrongTailSign = true;
					$this->strErrMsg = "<b>Wrong tailsign</b> - Expecting <i>" . $this->tailsign . "</i> but ";
					if($readTailsign != '') {
						$this->strErrMsg .= "<i>$readTailsign</i>";						
					} else {
						$this->strErrMsg .= "no tailsign";
					}
					//$this->strErrMsg .= " is set in XML file. Check the option \"Disable Tailsign verification\" if this file is for <i>" . $this->tailsign . "</i>";
					return 0;
				}
			}*/
			$strQuery = "INSERT IGNORE SYS_flight (idFlightLeg, flightLeg,flightNumber,departureAirportCode,arrivalAirportCode,aircraftTailSign,aircraftType,createDate,lastUpdate,idOffload) VALUES (" . $this->iFlightLegId . ",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['FLIGHTLEGID'] . "\",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['FLIGHTNUMBER'] . "\",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['DEPARTUREAIRPORT'] . "\",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['ARRIVALAIRPORT'] . "\",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['AIRCRAFTTAILSIGN'] . "\",\"" . $this->type . "\",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['FLIGHTLEGSTARTTIME'] . "\",\"" . $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['FLIGHTLEGSTOPTIME'] . "\",1)";
			$this->strFlightLeg = $this->arrOutput ['FLIGHTLEGINFO'] ['attrs'] ['FLIGHTLEGID'];
			$this->arrOutput = array ();
		} else if ($tagclose == "FLIGHTPHASE") {
						
			if ($this->flightPhasesQuery == "") {
				$this->flightPhasesQuery = "INSERT IGNORE SYS_flightPhase (idFlightPhase,startTime,endTime,idFlightLeg) VALUES ";
			} else {
				$this->flightPhasesQuery .= ' , ';
			}
			
			$this->flightPhasesQuery .= " (\"" . $this->arrOutput ['FLIGHTPHASE'] ['attrs'] ['FLIGHTPHASEID'] . "\",\"" . $this->arrOutput ['FLIGHTPHASE'] ['attrs'] ['FLIGHTPHASESTARTTIME'] . "\",\"" . $this->arrOutput ['FLIGHTPHASE'] ['attrs'] ['FLIGHTPHASEENDTIME'] . "\"," . $this->iFlightLegId . ")";
			
			$this->arrOutput = array ();
			$this->iNumOfFlightLeg ++;
		} else if ($tagclose == "HEALTHINFO") {
			// $this->log($this->arrOutput);
			if (! $this->bRemovalFlag) {
				$offloadDate = $this->offloadDate;
				//$this->strHostName = $this->arrOutput ['EQUIPMENT'] ['attrs'] ['HOSTNAME'];
				$hostName = $this->strHostName;
				//$serialNumber = $this->arrOutput ['STATICINFO'] ['attrs'] ['SERIALNUMBER'];
				$serialNumber = $this->serialNumber;
				$lruType = $this->arrOutput ['STATICINFO'] ['attrs'] ['LRUTYPE'];
				$lruSubType = $this->arrOutput ['STATICINFO'] ['attrs'] ['LRUSUBTYPE'];
				$hwPartNumber = $this->arrOutput ['STATICINFO'] ['attrs'] ['HWPARTNUMBER'];
				$macAddress = $this->arrOutput ['STATICINFO'] ['attrs'] ['MACADDRESS'];
				$ipAddress = $this->arrOutput ['STATICINFO'] ['attrs'] ['IPADDRESS'];
				$model = $this->arrOutput ['STATICINFO'] ['attrs'] ['MODEL'];
				$revision = $this->arrOutput ['STATICINFO'] ['attrs'] ['REVISION'];
				$totalPowerOnTime = $this->arrOutput ['HEALTHINFO'] ['attrs'] ['TOTALPOWERONTIME'];
				$totalRebootNumber = $this->arrOutput ['HEALTHINFO'] ['attrs'] ['TOTALREBOOTNUMBER'];
				$cmdRebootNumber = $this->arrOutput ['HEALTHINFO'] ['attrs'] ['CMDREBOOTNUMBER'];
				

				$strOffloadDate = strtotime($this->offloadDate);
				$strMaxOffloadDate = strtotime($this->maxOffloadDate);
				
				if($strOffloadDate > $strMaxOffloadDate) {
					//echo "recent offload<br>";
					// the offload file is more recent than what we have at the moment
					$strQuery = "INSERT INTO BIT_lru (hostName,lruType,lruSubType,hwPartNumber,serialNumber,macAddress,ipAddress,model,revision,totalPowerOnTime,totalRebootNumber,cmdRebootNumber,idStaticConfLru, lastUpdate, idFlightLeg)
 									VALUES (\"" . $this->strHostName . "\",\"" . $lruType . "\",\"" . $lruSubType . "\",\"" . $hwPartNumber . "\",\"" . $serialNumber . "\",\"" . $macAddress . "\",\"" . $ipAddress . "\",\"" . $model . "\",\"" . $revision . "\",\"" . $totalPowerOnTime . "\",\"" . $totalRebootNumber . "\",\"" . $cmdRebootNumber . "\"," . $this->idefault . ",'" . $offloadDate . "', " . $this->iFlightLegId . ")
		 							ON DUPLICATE KEY UPDATE
		 								ipAddress='$ipAddress', model='$model', revision='$revision', totalPowerOnTime='$totalPowerOnTime', totalRebootNumber='$totalRebootNumber', cmdRebootNumber='$cmdRebootNumber', lastUpdate='$offloadDate', idFlightLeg='$this->iFlightLegId'";
				} else {
					// the offload file is more older and should not erase the newest data
					$strQuery = "INSERT INTO BIT_lru (hostName,lruType,lruSubType,hwPartNumber,serialNumber,macAddress,ipAddress,model,revision,totalPowerOnTime,totalRebootNumber,cmdRebootNumber,idStaticConfLru, lastUpdate, idFlightLeg)
								SELECT * FROM  ( SELECT \"" . $this->strHostName . "\" AS v1,\"" . $lruType . "\" AS v2,\"" . $lruSubType . "\" AS v3,\"" . $hwPartNumber . "\" AS v4,\"" . $serialNumber . "\" AS v5,\"" . $macAddress . "\" AS v6,\"" . $ipAddress . "\" AS v7,\"" . $model . "\" AS v8,\"" . $revision . "\" AS v9,\"" . $totalPowerOnTime . "\" AS v10,\"" . $totalRebootNumber . "\" AS v11,\"" . $cmdRebootNumber . "\" AS v12," . $this->idefault . " AS v13,'" . $offloadDate . "' AS v14, " . $this->iFlightLegId . " AS v15) AS tmp 
								WHERE NOT EXISTS (
									SELECT idLru FROM BIT_lru WHERE hostName='$hostName' AND serialNumber='$serialNumber'
								) ";
					//echo "old offload<br>";
					
					// check if the host name and serial number combination exists already
					$oldDataQuery = "SELECT idLru FROM BIT_lru WHERE hostName='$hostName' AND serialNumber='$serialNumber' order by idLru";
					
					$oldDataResult = mysqli_query($this->dbObj, $oldDataQuery);
					$idLru = 0;
					if($oldDataResult) {
					    while ($oldDataRow = mysqli_fetch_assoc($oldDataResult)) {
					        $idLru = $oldDataRow['idLru'];
					    }
					}
					
					if($idLru) {
					    // insert into bit_lru_history table
					    $query = "INSERT INTO bit_lru_history (host_name,lru_type,lru_sub_type,hw_part_number,serial_number,mac_address,ip_address,model,revision,total_power_on_time,total_reboot_number,cmd_reboot_number,id_static_conf_lru,last_update,flightleg_id,bit_lru_id)
                        VALUES ('" . $this->strHostName . "','" . $lruType . "','" . $lruSubType . "','" . $hwPartNumber . "','" . $serialNumber . "','" . $macAddress . "','" . $ipAddress . "','" . $model . "','" . $revision . "','" . $totalPowerOnTime . "','" . $totalRebootNumber . "','" . $cmdRebootNumber . "'," . $this->idefault . ",'" . $offloadDate . "', " . $this->iFlightLegId . "," . $idLru . ")";
					    
					    if (!mysqli_query($this->dbObj, $query)) {
					        error_log("Problem in updating bit_lru_history table:" . mysqli_error($this->dbObj));
					    }
					}
				}
				
				// Insert/update the HostName Serial Number data into the master table.
				// Get the airlineId from tailsign
				if($strOffloadDate > $strMaxOffloadDate) {
				    $serialNumberFromDB = 0;
				    $id = 0;
				    $action = "";
				    $query = "select id, serial_number from banalytics.serialnumber_info where airline_id=" . $this->airlineId . " and tailsign='$this->tailsign' and host_name='$hostName'";
				    // 				echo "Query: $query";
				    if ($stmt = $this->dbObj->prepare($query)) {
				        $stmt->execute();
				        $stmt->bind_result($id, $sn);
				        if ($stmt->fetch()) {
				            $serialNumberFromDB = $sn;
				            $id = $id;
				            if(!empty($serialNumber) and ($serialNumber != $sn)) {
				                $action = "UPDATE";
				            }
				        } else {
				            $action = "INSERT";
				        }
				        $stmt->close();
				        
				        if(!empty($action) and $action == "INSERT") {
				            
				            $insertTSMasterQuery = "insert into banalytics.serialnumber_info(host_name, serial_number, tailsign, airline_id, last_updated_time) values ('$hostName', '$serialNumber', '$this->tailsign',$this->airlineId,'$offloadDate')";
				            if (!mysqli_query($this->dbObj, $insertTSMasterQuery)) {
				                echo "problem with query $insertTSMasterQuery<br>";
				                echo mysqli_error($this->dbObj);
				            }
				        }else if(!empty($action) and $action == "UPDATE") {
				            // Update this new sn and offloadDate in master table
				            $updateTSMasterQuery = "update banalytics.serialnumber_info set serial_number='$serialNumber', last_updated_time='$offloadDate' where id=$id";
				            if (!mysqli_query($this->dbObj, $updateTSMasterQuery)) {
				                echo "problem with query $updateTSMasterQuery<br>";
				                echo mysqli_error($this->dbObj);
				            }
				        }
				    } else {
				        echo "Error in fetching serial Number " . mysqli_errno($this->dbObj);
				    }
				} else {
    			    if(!empty($serialNumber)) {
    			        $query = "insert into banalytics.serialnumber_info(host_name, serial_number, tailsign, airline_id, last_updated_time)
                                    SELECT * FROM (SELECT '$hostName', '$serialNumber', '$this->tailsign',$this->airlineId,'$offloadDate') AS tmp WHERE NOT EXISTS (
                                    SELECT id FROM banalytics.serialnumber_info WHERE airline_id=$this->airlineId AND tailsign='$this->tailsign' AND host_name='$hostName')";
    
    			        if (!mysqli_query($this->dbObj, $query)) {
    			            echo "problem with query $query<br>";
    			            echo mysqli_error($this->dbObj);
    			        }
    			    }
				}
				
				// update the host name and serial number combination in the serialnumber_history table
// 				if($strOffloadDate > $strMaxOffloadDate) {
// 				    if(!empty($serialNumber)) {
// 				        $query = "INSERT IGNORE banalytics.serialnumber_history(airline_id,tailsign,host_name,serial_number, last_updated_time)
//            				        VALUES ($this->airlineId,'$this->tailsign','$hostName','$serialNumber','$offloadDate')";

// 				        if (!mysqli_query($this->dbObj, $query)) {
// 				            error_log("Problem with query: $query");
// 				            error_log("Problem in updating serialnumber_history table:" . mysqli_error($this->dbObj));
// 				        }
// 				    }
// 				}
				
				// $this->log($strQuery);
				$this->arrOutput = array ();
				$this->iNumOfEquip ++;
			}
			
			// echo "$strQuery";
		}
		
		else if ($tagclose == "CONFSW") {
						
			$offloadDate = $this->offloadDate;
			$hostName = $this->strHostName;
			$description = $this->arrOutput ['CONFSW'] ['attrs'] ['DESCRIPTION'];
			$partNumber = $this->arrOutput ['CONFSW'] ['attrs'] ['PARTNUMBER'];
			

			$strOffloadDate = strtotime($this->offloadDate);
			$strMaxOffloadDate = strtotime($this->maxOffloadDate);
			
			if($strOffloadDate > $strMaxOffloadDate) {
				//echo "recent offload<br>";
				// the offload file is more recent than what we have at the moment
				$strQuery = "INSERT INTO BIT_confSw (hostName,description,partNumber,lastUpdate,idFlightLeg)
 									VALUES (\"" . $this->strHostName . "\",\"" . $description . "\",\"" . $partNumber . "\",\"" . $offloadDate . "\", ".$this->iFlightLegId.")
 									ON DUPLICATE KEY UPDATE
 									lastUpdate='$offloadDate', idFlightLeg=" . $this->iFlightLegId;
			} else {
				// the offload file is more older and should not erase the newest data
				$strQuery = "INSERT INTO BIT_confSw (hostName,description,partNumber,lastUpdate,idFlightLeg)
								SELECT * FROM (SELECT \"" . $this->strHostName . "\" AS v1,\"" . $description . "\" AS v2,\"" . $partNumber . "\" AS v3,\"" . $offloadDate . "\" AS v4, ".$this->iFlightLegId." AS v5) AS tmp
								WHERE NOT EXISTS (
									SELECT idConfSW FROM BIT_confSw WHERE hostName='$hostName' AND partNumber='$partNumber'
								) ";
				
				// check if the host name and part number combination exists already
				$oldDataQuery = "SELECT idConfSw FROM BIT_confSw WHERE hostName='$hostName' AND partNumber='$partNumber' order by idConfSw";
				
				$oldDataResult = mysqli_query($this->dbObj, $oldDataQuery);
				$idConfSw = 0;
				if($oldDataResult) {
				    while ($oldDataRow = mysqli_fetch_assoc($oldDataResult)) {
				        $idConfSw = $oldDataRow['idConfSw'];
				    }
				}
				
				if($idConfSw) {
				    // insert into BIT_confsw_history table
				    $query = "INSERT bit_confsw_history(host_name,description,part_number,last_update_date,flightleg_id,idConfSw)
   				        VALUES ('" . $this->strHostName . "','" . $description . "','" . $partNumber . "','" . $offloadDate . "', ".$this->iFlightLegId."," . $idConfSw . ")";
				    
				    if (!mysqli_query($this->dbObj, $query)) {
				        error_log("Problem in updating bit_confsw_history table:" . mysqli_error($this->dbObj));
				    }
				}
			}
			
			$this->arrOutput = array ();
		} else if ($tagclose == "REBOOT") {
						
			if ($this->eventsQuery == "") {
				$this->eventsQuery = "INSERT IGNORE BIT_events (eventName,eventType,eventData,eventStatus,eventCount,eventInfo,idFlightLeg,lastUpdate,serialNumber) VALUES ";
			} else {
				$this->eventsQuery .= ' , ';
			}
			
			$this->eventsQuery .= " (\"" . $this->arrOutput ['REBOOT'] ['attrs'] ['REBOOTTYPE'] . "\",\"" . "BOOT" . "\",\"" . $this->strHostName . "\",\"" . "ON" . "\",\"" . "1" . "\",\"" . $this->arrOutput ['REBOOT'] ['attrs'] ['REBOOTREASON'] . "\"," . $this->iFlightLegId . ",\"" . $this->arrOutput ['REBOOT'] ['attrs'] ['TIME'] . "\",\"" . $this->serialNumber . "\")";
			
			$this->arrOutput = array ();
			// echo "$strQuery";
			// $this->log($strQuery);
		} else if ($tagclose == "FAILURE") {
				
			if ($this->failuresQuery == "") {
				$this->failuresQuery = "INSERT IGNORE BIT_failure (idFlightLeg,failureCode,param1,probability,correlationDate,monitorState,accusedHostName,isAmsSent,lastUpdate,logAction,logActionTime,legFailureCount,serialNumber) VALUES ";
			} else {
				$this->failuresQuery .= ' , ';
			}
			
			$this->failuresQuery .= " (" . $this->iFlightLegId . ",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['FAILURECODE'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['PARAM1'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['PROBABILITY'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['CORRELATIONDATE'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['MONITORSTATE'] . "\",\"" . $this->strHostName . "\",\"" . $this->idefault . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['LASTUPDATE'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['LOGACTION'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['LOGACTIONTIME'] . "\",\"" . $this->arrOutput ['FAILURE'] ['attrs'] ['LEGFAILURECOUNT'] . "\",\"" . $this->serialNumber . "\")";
			
			$this->arrOutput = array ();
			// $this->log($strQuery);
			// echo "$strQuery";
			$this->iNumOfFailures ++;
		} else if ($tagclose == "FAULT") {
		
			
			if ($this->faultsQuery == "") {
				$this->faultsQuery = "INSERT IGNORE BIT_fault (hostName,reportingHostName,param1,param2,param3,param4,detectionTime,faultCode,monitorState,inhibited,faultStatus,insertionTime,clearingTime,idFlightLeg,lastUpdate,serialNumber) VALUES ";
			} else {
				$this->faultsQuery .= ' , ';
			}
			
			if( is_numeric($this->arrOutput ['FAULT'] ['attrs'] ['PARAM1']) ) {
				$param1 = $this->arrOutput ['FAULT'] ['attrs'] ['PARAM1'];
			} else {
				$param1 = 9999;
			}
			if( is_numeric($this->arrOutput ['FAULT'] ['attrs'] ['PARAM2']) ) {
				$param2 = $this->arrOutput ['FAULT'] ['attrs'] ['PARAM2'];
			} else {
				$param2 = 9999;
			}
			if( is_numeric($this->arrOutput ['FAULT'] ['attrs'] ['PARAM3']) ) {
				$param3 = $this->arrOutput ['FAULT'] ['attrs'] ['PARAM3'];
			} else {
				$param3 = 9999;
			}
			if( is_numeric($this->arrOutput ['FAULT'] ['attrs'] ['PARAM4']) ) {
				$param4 = $this->arrOutput ['FAULT'] ['attrs'] ['PARAM4'];
			} else {
				$param4 = 9999;
			}
			$this->faultsQuery .= " (\"" . $this->strHostName . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['REPORTINGHOSTNAME'] . "\",\"" . $param1 . "\",\"" . $param2 . "\",\"" . $param3 . "\",\"" . $param4 . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['DETECTIONTIME'] . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['FAULTCODE'] . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['MONITORSTATE'] . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['INHIBITED'] . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['FAULTSTATUS'] . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['INSERTIONTIME'] . "\",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['CLEARINGTIME'] . "\"," . $this->iFlightLegId . ",\"" . $this->arrOutput ['FAULT'] ['attrs'] ['LASTUPDATE'] . "\",\"" . $this->serialNumber . "\")";
			
			$this->arrOutput = array ();
			// $this->log($strQuery);
			//echo "$this->serialNumber";
			//echo "$this->faultsQuery";
			
			$this->iNumOfFaults ++;
		} else if ($tagclose == "EXTAPPEVENT") {
			
			
			
			if ($this->extappeventsQuery == "") {
				$this->extappeventsQuery = "INSERT IGNORE BIT_extAppEvent (hostName,reportingHostName,faultCode,param1, param2, param3, param4, detectionTime,lastUpdate,idFlightLeg,serialNumber) VALUES ";
			} else {
				$this->extappeventsQuery .= ' , ';
			}
			
			$param1 = str_replace('"','',$this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['PARAM1']);
			
			$this->extappeventsQuery .= " (\"" . $this->strHostName . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['REPORTINGHOSTNAME'] . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['FAULTCODE'] . "\",\"" . $param1 . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['PARAM2'] . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['PARAM3'] . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['PARAM4'] . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['DETECTIONTIME'] . "\",\"" . $this->arrOutput ['EXTAPPEVENT'] ['attrs'] ['LASTUPDATE'] . "\"," . $this->iFlightLegId . ",\"" . $this->serialNumber . "\")";
			
			$this->arrOutput = array ();
			// $this->log($strQuery);
		} else if ($tagclose == "REMOVALTIME") {
			// $this->log($this->arrOutput);
			//$this->strHostName = $this->arrOutput ['EQUIPMENT'] ['attrs'] ['HOSTNAME'];
			$strQuery = "INSERT IGNORE BIT_removedLru (hostName,lruType,lruSubType,hwPartNumber,serialNumber, newSerialNumber,macAddress,ipAddress,model,revision,totalPowerOnTime,totalRebootNumber,cmdRebootNumber,idStaticConfLru,removalDate, idFlightLeg) 
						VALUES (\"" . $this->strHostName . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['LRUTYPE'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['LRUSUBTYPE'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['HWPARTNUMBER'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['SERIALNUMBER'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['NEWSERIALNUMBER'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['MACADDRESS'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['IPADDRESS'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['MODEL'] . "\",\"" . $this->arrOutput ['STATICINFO'] ['attrs'] ['REVISION'] . "\",\"" . $this->arrOutput ['HEALTHINFO'] ['attrs'] ['TOTALPOWERONTIME'] . "\",\"" . $this->arrOutput ['HEALTHINFO'] ['attrs'] ['TOTALREBOOTNUMBER'] . "\",\"" . $this->arrOutput ['HEALTHINFO'] ['attrs'] ['CMDREBOOTNUMBER'] . "\"," . $this->idefault . ",\"" . $this->arrOutput ['REMOVALTIME'] ['attrs'] ['REMOVALDATE'] . "\"," . $this->iFlightLegId . ")";
			
			$this->arrOutput = array ();
			// $this->log($strQuery);
		}else if ($tagclose == "SERVICEFAILURE") {//Smita
			// $this->log($this->arrOutput);
			//$this->strHostName = $this->arrOutput ['EQUIPMENT'] ['attrs'] ['HOSTNAME'];
			$strQuery = "INSERT IGNORE BIT_serviceFailure (accusedHostname,failureCode,monitorState,correlationDate,idService,lastUpdate,idFlightLeg) 
						VALUES (\"" . $this->arrOutput ['SERVICEFAILURE'] ['attrs'] ['ACCUSEDHOSTNAME'] . "\",\"" . $this->arrOutput ['SERVICEFAILURE'] ['attrs'] ['FAILURECODE'] . "\",\"" . $this->arrOutput ['SERVICEFAILURE'] ['attrs'] ['MONITORSTATE'] . "\",\"" . $this->arrOutput ['SERVICEFAILURE'] ['attrs'] ['CORRELATIONDATE'] . "\"," . $this->idService . ",\"" . $this->arrOutput ['SERVICEFAILURE'] ['attrs'] ['LASTUPDATE'] . "\"," . $this->iFlightLegId . ")";
			
			$this->arrOutput = array ();
			// $this->log($strQuery);
		}else if ($tagclose == "EQUIPMENT") {
			
			$this->strHostName = "";
			// $this->log($this->arrOutput);
			$this->arrOutput = array ();
		}
		
		if (! empty ( $strQuery )) {
			// echo "Execute query: $strQuery<br>";
			
			if (! mysqli_query ( $this->dbObj, $strQuery )) {
				echo "problem with query $strQuery<br>";
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysql_error () );
				return 0;
			}
			
			// get the flightlegid
// 			if ($tagclose == "FLIGHTLEGINFO") {
			    
// 			}
		}
	}
	function log($data) {
		$handle = @fopen ( "smartool.log", "a+" );
		if ($handle) {
			fwrite ( $handle, print_r ( $data, true ) );
			fwrite ( $handle, "\n" );
		}
		fclose ( $handle );
	}
}
?>
