<?php
// Start the session
session_start();
ini_set('max_execution_time', 2000);

if(!$_SESSION['disablePermissionCheck']) {
    require_once('checkEngineeringPermission.php');
}

class EventXmlParser {
	var $arrOutput = array ();
	var $resParser;
	var $strXmlData;
	var $dbObj = NULL;
	var $iFlightLegId = 0;
	var $iEventFlightLegId = 0;
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
	var $tailsign = "";
	var $eventsTotalsQuery = "";
	// var $dbname="";
	function init($dbobj, $tailsign, $eventFlightLegIdCount) {
		/*
		 * Testing ********************************************
		 * $db_server = 'localhost';
		 * $db = 'bite_biteifedb_test_test_10152014_tea';
		 * $db_username = 'dbtooluser';
		 * $db_password = 'dbtooluser123';
		 * $this->dbObj = @mysql_connect($db_server,$db_username,$db_password);
		 */
		$this->dbObj = $dbobj;
		//$this->aircraftId = $aircraftId;
		$this->tailsign = $tailsign;
		//$this->iEventFlightLegId = 0;
		$this->iEventFlightLegId = $eventFlightLegIdCount;
		// $this->dbname=$dbname;
		// @mysql_select_db($dbname);
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
		if ($this->eventsTotalsQuery != "") {
			if (! mysqli_query ( $this->dbObj, $this->eventsTotalsQuery )) {
				$strQuery = $this->eventsTotalsQuery;
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysqli_error ( $this->dbObj ) );
				return 0;
			}
		} else {
			// $this->log("flight phases query is empty");
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
			case "SYSTEMEVENTINFO" :
			case "EVENTS" :
				$this->arrOutput [$name] = array (
						"attrs" => $attrs 
				);
				break;
			default :
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
		if ($tagclose == "SYSTEMEVENTINFO") {
			// $this->log($this->arrOutput);
			/*
			 * $strQuery="INSERT INTO event_offloads (name,aircraftId,flightState,flightLegId,eventStartTime,eventStopTime,departureAirport,arrivalAirport,aircraftTailSign) VALUES ('".
			 * $this->dbname."','".
			 * $this->aircraftId."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['FLIGHTSTATE']."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['FLIGHTLEGID']."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['EVENTSTARTTIME']."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['EVENTSTOPTIME']."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['DEPARTUREAIRPORT']."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['ARRIVALAIRPORT']."','".
			 * $this->arrOutput['SYSTEMEVENTINFO']['attrs']['AIRCRAFTTAILSIGN']."')";
			 */
			if(!$_SESSION['disableTailsignCheck']) {
				$readTailsign = $this->arrOutput['SYSTEMEVENTINFO']['attrs']['AIRCRAFTTAILSIGN'];
				if(strpos($readTailsign, $this->tailsign) === false) {
					$this->isWrongTailSign = true;
					$this->strErrMsg = "<b>Wrong tailsign</b> - Expecting <i>" . $this->tailsign . "</i> but ";
					if($readTailsign != '') {
						$this->strErrMsg .= "<i>$readTailsign</i>";						
					} else {
						$this->strErrMsg .= "no tailsign";
					}
					$this->strErrMsg .= " is set in XML file. Check the option \"Disable Tailsign verification\" if this file is for <i>" . $this->tailsign . "</i>";
					return 0;
				}
			}

			$this->arrOutput = array ();
			
			// @mysql_select_db("BAnalytics");
		} else if ($tagclose == "EVENTS") {
			// $this->log("param2 -".$this->arrOutput['EVENTS']['attrs']['PARAM2']."-");
			
			if (! isset ( $this->arrOutput ['EVENTS'] ['attrs'] ['PARAM2'] )) {
				$param2 = '';
			} else {
				$param2 = $this->arrOutput ['EVENTS'] ['attrs'] ['PARAM2'];
			}
			
			if (! isset ( $this->arrOutput ['EVENTS'] ['attrs'] ['PARAM3'] )) {
				$param3 = '';
			} else {
				$param3 = $this->arrOutput ['EVENTS'] ['attrs'] ['PARAM3'];
			}
			
			if (! isset ( $this->arrOutput ['EVENTS'] ['attrs'] ['EVENTSOURCE'] )) {
				$eventsource = '';
			} else {
				$eventsource = $this->arrOutput ['EVENTS'] ['attrs'] ['EVENTSOURCE'];
			}
			
			// $this->log($this->arrOutput);
			//$strQuery = "INSERT INTO services_events (eventName,eventTime,eventSource,param1,param2,param3) VALUES ('" . $this->arrOutput ['EVENTS'] ['attrs'] ['EVENTNAME'] . "','" . $this->arrOutput ['EVENTS'] ['attrs'] ['EVENTTIME'] . "','" . $eventsource . "','" . $this->arrOutput ['EVENTS'] ['attrs'] ['PARAM1'] . "','" . $param2 . "','" . $param3 . "')";
			// $this->iFlightLegId.")";
			// $this->log($strQuery);
			// $this->arrOutput=array();
			// $this->iNumOfFlightLeg++;
			
			if ($this->eventsTotalsQuery == "") {
				$this->eventsTotalsQuery = "INSERT IGNORE services_events (eventName,eventTime,eventSource,param1,param2,param3, idEventFlightLeg ) VALUES ";
			} else {
				$this->eventsTotalsQuery .= ' , ';
			}
				
			    $this->eventsTotalsQuery .= " ('" . $this->arrOutput ['EVENTS'] ['attrs'] ['EVENTNAME'] . "','" . $this->arrOutput ['EVENTS'] ['attrs'] ['EVENTTIME'] . "','" . $eventsource . "','" . $this->arrOutput ['EVENTS'] ['attrs'] ['PARAM1'] . "','" . $param2 . "','" . $param3 . "', " . $this->iEventFlightLegId . " )";
				
		}
		
		if (! empty ( $strQuery )) {
			if (! mysqli_query ( $this->dbObj, $strQuery )) {
				$this->strErrMsg = sprintf ( "Error performing query,\n Query: %s \n MySQL: ", trim ( nl2br ( htmlentities ( $strQuery ) ) ), mysql_error () );
				return 0;
			}
			if ($tagclose == "FLIGHTLEGINFO") {
				$this->iFlightLegId = mysql_insert_id ();
			}
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
