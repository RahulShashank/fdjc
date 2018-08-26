<?php
	// No Permissions check needed in this file as it will be included in scripts where we are going to check them

	function sqlError($link) {
		echo mysql_errno($link) . ": " . mysql_error($link). "\n";
	}

	function multiexplode ($delimiters,$string) {

		$ready = str_replace($delimiters, $delimiters[0], $string);
		$launch = explode($delimiters[0], $ready);
		return  $launch;
	}

	function getFlightInArray($flightLegs)
	{
		$flightLegsArrayElement = multiexplode(array(","),$flightLegs);

		$flightLegsArray = array();
		foreach($flightLegsArrayElement as $element){

			if(stripos($element,'-') == false){
				array_push($flightLegsArray,$element); 
			}else{
				$flightLegsInternalElement = explode("-",$element);
				$i = 1;
				
				foreach($flightLegsInternalElement as $internalElement)
				{
				
					if($i == 1){
						$lowerLimit = $internalElement;
						$i++;
					}else{
						for($i = $lowerLimit; $i<= $internalElement; $i++){
							array_push($flightLegsArray,$i); 
						}
						$i=1;
					}	
				}
				
			}
		}
		return $flightLegsArray;
	}
	
	//////////////////////////////////////////////////////////////////////
	//PARA: Date Should In YYYY-MM-DD Format
	//RESULT FORMAT:
	// '%y Year %m Month %d Day %h Hours %i Minute %s Seconds'        =>  1 Year 3 Month 14 Day 11 Hours 49 Minute 36 Seconds
	// '%y Year %m Month %d Day'                                    =>  1 Year 3 Month 14 Days
	// '%m Month %d Day'                                            =>  3 Month 14 Day
	// '%d Day %h Hours'                                            =>  14 Day 11 Hours
	// '%d Day'                                                        =>  14 Days
	// '%h Hours %i Minute %s Seconds'                                =>  11 Hours 49 Minute 36 Seconds
	// '%i Minute %s Seconds'                                        =>  49 Minute 36 Seconds
	// '%h Hours                                                    =>  11 Hours
	// '%a Days                                                        =>  468 Days
	//////////////////////////////////////////////////////////////////////
	function dateDifference($date_1 , $date_2 , $differenceFormat = '%a' )
	{
	    $datetime1 = date_create($date_1);
	    $datetime2 = date_create($date_2);
	   
	    $interval = date_diff($datetime1, $datetime2);
	   
	    return $interval->format($differenceFormat);
	   
	}
	
	function trimDBName($database) {
		$name = $database;
		
		if(strpos($database, 'bit') === 0) {
			$name = substr_replace($database, '', 0, 15);
		}
		
		return $name;
	}
	
	function getLruType($hostname) {
		$type = "getLrutype() - error - Type not found";
		
		if (strpos($hostname, 'DSU') === 0) {
			$type = "DSU";
		}
		
		return $type;
	}
	
	function getLruTypeSubGroupOrder($type) {
		switch ($type) {
		    case "DSU":
			$order = 0;
			break;
		    case "ADBG":
			$order = 1;
			break;
		    default:
			$order = 100;
		} 
		
		return $order;
	}
	
	function getFlightPhaseDesc($id) {
		switch ($id) {
		    case '1':
			$desc = "Pre-flight ground";
			break;
		    case '2':
			$desc = "Taxi out";
			break;
		case '3':
			$desc = "Take off";
			break;
		case '4':
			$desc = "Climb";
			break;
		case '5':
			$desc = "Cruise";
			break;
		case '6':
			$desc = "Descent";
			break;
		case '7':
			$desc = "Landed";
			break;
		case '8':
			$desc = "Taxi in";
			break;
		case '9':
			$desc = "Post-flight";
			break;
		    default:
			$desc = "Unknown";
		} 
		
		return $desc;
	}
	
	function getFlightPhaseOrder($id) {
		switch ($id) {
		case '6':
			$order = 4;
			break;
		case '7':
			$order = 3;
			break;
		case '8':
			$order = 2;
			break;
		case '9':
			$order = 1;
			break;
		    default:
			$order = $id;
		} 
		
		return $order;
	}
	
	function getExtAppEventDesc($faultCode) {
		if($faultCode == '42007003')
			$desc = "Communication fault between the server and client";
		if($faultCode == '42007005')
			$desc = "Buffer overrun or underrun";
		else if($faultCode == '42007006')
			$desc = "PID missing";
		else if($faultCode == '42007008')
			$desc = "AVOD Failover";
		else if($faultCode == '42007004')
			$desc = "Cannot retrieve AVBS-VA-VOR parameters";
		else if($faultCode == '42008002')
			$desc = "Connection with server failed";
		else
			$desc = "ExtAppEvent code not known";
		
		return $desc;
	}

	
	function getCriticalFailures() {
		return ["10042400001","10042000101","10042048001",
				"10042049001","10042228001","10042047001",
				"10041400001","10041049001","10041047001",
				"10041045001","10041046001","10423040001",
				"10423090001","10422240001","10045302001",
				"10044305001","10044375001"];
	}
	
	function getCriticalFaults() {
		return ["400", // loss of communication
				"42007003", // communication fault between server and client
				"42001006", // SVDU POST error - CPU fault
				"42001009", // SVDU POST error - FPGA fault
				"42001000", // SVDU POST error - Memory fault
				"42001004", // SVDU POST error - LCD fault
				];
	}

	function getCriticalExtAppEvents() {
		return ["42007008" // AVOD Failover
				];
	}

	function getMonitorStateDesc($monitorStateId) {
		switch ($monitorStateId) {
			case '1':
				$monitorState = "Inactive";
				break;
			case '3':
				$monitorState = "Active";
				break;
		    default:
				$monitorState = "Monitor state unknown";
		} 
		
		return $monitorState;
	}

	function getPieBackgroundColor($index) {
		// switch ($index) {
		// 	case 2:
		// 		return '#F7464A';
		// 		break;

		// 	case 0:
		// 		return '#46BFBD';
		// 		break;

		// 	case 1:
		// 		return '#FDB45C';
		// 		break;
			
		// 	default:
		// 		return '#949FB1';
		// 		break;
		// }

		// Got colors from following link
		// http://www.w3schools.com/tags/ref_colorpicker.asp?colorhex=32CD32

		$color = '';
		switch ($index) {
			case '1':
				$color = '#92AC2D';
				break;


			case '2':
				$color = '#ABC628';
				break;
				

			case '3':
				$color = '#BFD525';
				break;


			case '4':
				$color = '#C7DB2C';
				break;
			
			case '5':
				$color = '#D4E142';
				break;

			case '6':
				$color = '#E1E866';
				break;

			default:
				$color = '#EDED92';
				break;
		}

		return $color;
	}

	function getPieHighlightColor($index) {
		// switch ($index) {
		// 	case 2:
		// 		return '#FF5A5E';
		// 		break;

		// 	case 0:
		// 		return '#5AD3D1';
		// 		break;

		// 	case 1:
		// 		return '#FFC870';
		// 		break;
			
		// 	default:
		// 		return '#A8B3C5';
		// 		break;
		// }

		// Got colors from following link
		// http://www.w3schools.com/tags/ref_colorpicker.asp?colorhex=32CD32

		$color = '';
		switch ($index) {
			case '1':
				$color = '#9DB442';
				break;


			case '2':
				$color = '#B3C56C';
				break;
				

			case '3':
				$color = '#C8D696';
				break;


			case '4':
				$color = '#DEE6C0';
				break;
			
			default:
				$color = '#F4F7EA';
				break;
		}

		return $color;
	}

	function getLruName($lruType, $lruSubType) {
		$lruName = '';

		if($lruType == 30) {
			$lruName = 'DSU-D3';
		}
		else if($lruType == 44) {
			$lruName = 'DSU-D4';
		}
		else if ($lruType == 45) {
			$lruName = 'LAIC';
		}
		else if ($lruType == 24) {
			$lruName = 'AVCD';
		}
		else if ($lruType == 25 || $lruType == 32) {
			$lruName = 'ADBG';
		}
		else if ($lruType == 43) {
			$lruName = 'ICMT-G4';
		}
		else if ($lruType == 15 || $lruType == 29) {
			$lruName = 'ICMT-G3';
		}
		else if ($lruType == 42 && $lruSubType == 3) {
			$lruName = 'OVH-G4';
		}
		else if ($lruType == 42 && ($lruSubType == 1 || $lruSubType == 2))  {
			$lruName = 'SVDU-G4';
		}
		else if ($lruType == 28) {
			$lruName = 'SVDU-G3';
		}
		else if ($lruType == 41 && $lruSubType == 1) {
			$lruName = 'TPMU';
		}
		else {
			$lruName = "Unknown LRU - type: $lruType";
		}

		return $lruName;
	}

	function getBinaryMod($hexMod){
		$hexMod = rtrim($hexMod, "0");

		if($hexMod !=''){
        $lenhexMod= strlen($hexMod);
      
			if($lenhexMod>1)
			{
				$hexval=array();
				$k=0;
				   for($i=0;$i<$lenhexMod;$i++) {
						$hexval[$k]= substr($hexMod, $i, 1); 
						$k++;             
				   }
				   for($i=0;$i<$lenhexMod;$i++) {
						$result.= hexbinval($hexval[$i]);        
				   }
				return ($result);
			}
			else{
				$result=hexbinval($hexMod); 
				return $result;
			}
		}else{
        $result='0000'; 
        return $result;        
		}	
	}

	/*function getDecimalMod($modsBinaryValue) {
		$result = "";
		$array = array();
		$k=0;
		for($i = 0; $i < strlen($modsBinaryValue); $i++){
	
			$val = substr($modsBinaryValue, $i, 1);
	
			if($val==1){
				$array[$k] = $i + 1;
				$k++;
					
			}//end of if else
	
		} //End of for Loop.
	
		$j=count($array);
		for($i = 0; $i < count($array); $i++){
			$result .=$array[$i];
			if($j>1) {
				$result .= ',';
			}
			$j--;
		}

		if($result == '') {
			$result = "0";
		}
	
		return $result;
	}*/
	
	function hexbinval($hexdata) {
        $bindata = '';

        if($hexdata == 1) {
            $bindata = '0001';
        }
        else if($hexdata == 2) {
            $bindata = '0010';
        }
        else if ($hexdata == 3) {
            $bindata = '0011';
        }
        else if ($hexdata == 4) {
            $bindata = '0100';
        } 
        else if($hexdata == 5) {
            $bindata = '0101';
        }
        else if ($hexdata == 6) {
            $bindata = '0110';
        }
        else if ($hexdata == 7) {
            $bindata = '0111';
        } 
        else if($hexdata == 8) {
            $bindata = '1000';
        }
        else if ($hexdata == 9) {
            $bindata = '1001';
        }
        else if ($hexdata == 'A') {
            $bindata = '1010';
        }
         else if($hexdata == 'B') {
            $bindata = '1011';
        }
        else if ($hexdata == 'C') {
            $bindata = '1100';
        }
        else if ($hexdata =='D') {
            $bindata = '1101';
        }
        else if($hexdata == 'E') {
            $bindata = '1110';
        }
        else if ($hexdata == 'F') {
            $bindata = '1111';
        }
        else {
            $bindata = "0000";
        }

        return $bindata;
    }
	
	function getModval($hexMod){
		$hexMod = rtrim($hexMod, "0");
		if($hexMod !=''){
			$lenhexMod= strlen($hexMod);
		  
			if($lenhexMod>1)
			{
				$hexval=array();
				$k=0;
				$binval=array();
				$r=0;
			   for($i=0;$i<$lenhexMod;$i++) {
					$hexval[$k]= substr($hexMod, $i, 1); 
					$k++;             
			   }

			   for($i=0;$i<$lenhexMod;$i++) {
					$result.= hexbinval($hexval[$i]);          
			   }
				$lenbinval=strlen($result);
				
				for($i=0;$i<$lenbinval;$i++){
					$binval[$r]= substr($result, $i, 1); 
					$r++;

				}
				$indexval=array();
				$indexval=(array_keys($binval,1));

				$j = count($indexval);

				for($i = 0; $i < count($indexval); $i++){
					$modval.=$indexval[$i]+1;
					if($j>1) {
						$modval .= ',';
					}
					$j--;

				}
				return $modval;
			}
			else{
				$binval=array();
				$k=0;
				$result=hexbinval($hexMod);
				$lenbinval=strlen($result);

				for($i=0;$i<$lenbinval;$i++){
					$binval[$k]= substr($result, $i, 1); 
					$k++;

				}
				$indexval=array();
				$indexval=(array_keys($binval,1));
					 
				$j = count($indexval);

				for($i = 0; $i < count($indexval); $i++){
						$modval.=$indexval[$i]+1;
						if($j>1) {
							$modval .= ',';
						}
						$j--;
					}      
				return $modval;
			}
		}else{
			$modval=0;                 
			return $modval;        
		}
	}
	

	function formatStringForTimeLine($string) {
		$string = str_replace(',', '', $string);
		$string = str_replace('\'', '', $string);

		return $string;
	}

	function findFirstDigit($text){
	    preg_match('/^\D*(?=\d)/', $text, $m);
	    return isset($m[0]) ? strlen($m[0]) : false;
	}

	function getFlightPhases() {
		return '4,5';
	}

	function getGroundPhases() {
		return '1, 9';
	}

	function getSystemResetSvduRatio() {
		return 30;
	}
	
	// Return flight legs in the form "1-4,6,7,10-19,..."
	// https://www.sitepoint.com/community/t/how-to-add-commas-and-dashes-for-numbers-ie-1-10-or-1-4-13/53189/6
	function reduceFlightLegs($t) {
		$s = '';
		$c = count($t);
		for($i=0; $i<$c; $i++) {
			$start = $t[$i];
			$end = '';
			
			if($i+2 < $c && $start+1 == $t[$i+1] && $start+2 == $t[$i+2]) {
				$i+=2;
				$end = $t[$i];

				while(++$i < $c) {	
					if($end+1 == $t[$i]) {
						$end = $t[$i];
					}
					else {
						break;
					}
				}
			}
			$s .= $end ? $start.' => '.$end.', ' : $start.', ';
		}

		$lastCharacters = substr($s, -2);
		if($lastCharacters == ', ') {
			$s = substr($s, 0, strlen($s) - 2);
		}

		return $s;
	}
?>
