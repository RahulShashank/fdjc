<?php
//Fields that are to be read from Mongodb
$fields = array('TGS_ONAIR.applicationAvailable',
				'TGS_ONAIR.serviceAvailable',
				'TGS_ONAIR.currentActiveUsers',
				'TGS_FLIGHT.altitude',
				'timestamp',//logfile
				'TGS_FLIGHT.flightPhase',
				'tailSign',//bitedb
				'TGS_FLIGHT.latitude',
				'TGS_FLIGHT.longitude',
				'TGS_FLIGHT.cityPair',
				'TGS_FLIGHT.flightNumber',
				'TGS_ONAIR.gsmConnexTsSystemState',
				'TGS_ONAIR.gsmConnexServiceAllowedIndication',
				'TGS_ONAIR.gsmConnexMobilesAllowedIndication',
				'TGS_ONAIR.serviceAvailableDetail',
				'TGS_ONAIR.applicationAvailableDetail',
				'TGS_ONAIR.backhaulLinkAvailableBitrate',
				'TGS_ONAIR.backhaulLinkNumberActiveContexts',
				'TGS_ONAIR.btsARFCN',
				'TGS_ONAIR.inputBtsOverruleAllowIsOn',
				'TGS_ONAIR.ncuLedPwr',
				'TGS_ONAIR.ncuLedSysOk',
				'TGS_ONAIR.ncuErrorMsg',
				'TGS_ONAIR.ncuLedBootRdy',
				'THALES_FLIGHT.asdSbbLongitude',
				'THALES_FLIGHT.asdSbbLatitude',
				'THALES_FLIGHT.asdSbbAltitude',
				'THALES_FLIGHT.asdSysIfeSystemMode',
				'ASC_SHARED.Flight Phase',
				'PING_STATUS.AVCD1',
				'PING_STATUS.SBB-1',
				'PING_STATUS.BTS1',
				'PING_STATUS.BTS2',
				'PING_STATUS.BTS1-CS',
				'PING_STATUS.BTS2-CS',
				'PING_STATUS.DSU1',
				'PING_STATUS.DSU2',
				'PING_STATUS.NCU',
				'PING_STATUS.NCU-ADB',
				'PING_STATUS.BTS1-ADB',
				'PING_STATUS.BTS2-ADB',
				'PING_STATUS.CWLU1',
				'PING_STATUS.CWLU2',
				'PING_STATUS.CWLU3',
				'P5_PORT_STATUS.speed',
				'P5_PORT_STATUS.status',
				'P5_PORT_STATUS.statusChange',
				'SERVICE_STATUS.gsm status',				
				'SERVICE_STATUS.vocem status',
				'SERVICE_STATUS.agp status',
				'SERVICE_STATUS.agprs status',
				'SERVICE_STATUS.stu status',
				'SERVICE_STATUS.nXt_Agent status',
				'SERVICE_STATUS.linkcontroller status',
				'SERVICE_STATUS.named status',
				'SERVICE_STATUS.snpd status',
				'SDU_STATUS.asuSduInfoOverallStatus',
				'SDU_STATUS.asuSduInfoCC1Status',
				'SDU_STATUS.asuSduInfoCC2Status',
				'SDU_STATUS.asuSduInfoAntennaBus',
				'SDU_STATUS.asuDlnaInfoOverallStatus',
				'SDU_STATUS.asuAntInfoOverallStatus',
				'SYS_DATA.nslookup',
				'idFlightLeg');

//Query string
//$where=array('TGS_FLIGHT.altitude' => array('$ne' => 0));


//$where = array('$and' => array(	array("TGS_FLIGHT.altitude" => array('$ne' => 0)),
//								array("tailSign" => $tailSign)));

$where = array('$and' => array(	array("timestamp" => array('$gte' => $firstTimeStamp,'$lte' => $lastTimeStamp )),
								array("tailSign" => $tailSign)));

//echo var_dump($where).'<br>';								
								
$cursor = $collectionActivity->find($where,$fields);

//echo var_dump($cursor).'<br>';

foreach ($cursor as $doc) {

	//echo var_dump($doc).'<br>';
	
	$altitudeValue = $doc['TGS_FLIGHT']['altitude'];
	$flightPhase = $doc['TGS_FLIGHT']['flightPhase'];
	
	//Condition to take climb/decent time when altitude value is -ve
	if (($recordClimbTimeAsStartTime == '0') && 
		($isAltitudeRecordingOn == '0') && 
		($altitudeValue != null) && 
		($altitudeValue < 0) && 
		($flightPhase == 'climb'))
	{
		$recordClimbTimeAsStartTime = '1';
	}
	elseif(($recordDecentTimeAsEndTime == '0') && 
			($isAltitudeRecordingOn == '1') && 
			($altitudeValue != null) && 
			($altitudeValue < 0) && 
			(($flightPhase == 'descentapproach') || ($flightPhase == 'taxiin')))
	{
		$currentFlightPhase = $flightPhase;
		
		if($prevFlightPhase != $currentFlightPhase)
		{
		$prevFlightPhase = 'descentapproach';
		$recordDecentTimeAsEndTime = '1';
		}
		else
		{
		$prevFlightPhase = $flightPhase;
		$prevTimeStamp = $doc['timestamp'];
		}
	}
	else
	{
	//donothing
	}


	
	if ((	($recordClimbTimeAsStartTime == '1') || 
			($altitudeValue >= $altThreshold)) && 
			($isAltitudeRecordingOn == '0'))
	{
		// **********	MAIN CASE 1: 	Start recording when altitude is above 10K FT or consider ******************* //
		// **********					Climb time when altitude is -ve and phase is climb ******************* //
		
		//Record first starttime and its ABOVE 10K FT
		$timeStamp = $doc['timestamp'];
		if(!is_array($tempOmtsOffArray))
		{
			$tempOmtsOffArray = array();
		}
		if(!is_array($tempWifiOffArray))
		{
			$tempWifiOffArray = array();
		}
			
	   if($doc['TGS_ONAIR']['serviceAvailable'] == 'true')
	   {
			$wifiStatusOnTimeStart10K = $doc['timestamp'];
			$tempWifiOnArray['description'] = "WIFI-ON";
			$tempWifiOnArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
			$tempWifiOnArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
			$tempWifiOnArray['startTime'] = $doc['timestamp'];
			$isWifiOnTimeRecording = '1';
			// important debug echo
			//echo "<br>Altitude Above 10KFT Wifi is ON and Its START Time is $wifiStatusOnTimeStart10K<br>";
	   }
	   elseif($doc['TGS_ONAIR']['serviceAvailable'] == 'false')
	   {
			$wifiStatusOffTimeStart10K = $doc['timestamp'];
			$tempWifiOffArray['description'] = "WIFI-OFF";
			$tempWifiOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
			$tempWifiOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];
			$tempWifiOffArray['startTime'] = $doc['timestamp'];		
			$tempWifiOffArray['computedFailure'] = "Unknown";
			$tempWifiOffArray['manualFailureEntry'] = "";			
			$isWifiOffTimeRecording = '1';	 
			// important debug echo
			//echo "<br>Altitude Above 10KFT Wifi is OFF and Its START Time is $wifiStatusOffTimeStart10K<br>";	   
	   }
	   else
	   {
			//do nothing
	   }
	   
	   //Wifi Restricted Area
	   if(	($doc['TGS_ONAIR']['serviceAvailableDetail'] == "restrictedAirspaceRegion") && 
			($isWifiRestrictedAreaRecording == '0'))
	   {
	   
			wifiRestrictedAreaStartTime($doc['timestamp'],
										$doc['TGS_FLIGHT']['latitude'],
										$doc['TGS_FLIGHT']['longitude'],
										$tempWifiRestrictedAreaArray,
										$tempWifiRestrictedAreaArrayCoordinates);
										
			$isWifiRestrictedAreaRecording = '1';
	   }
	   
	   //Check For OMTS Restricted Area
	   if(	($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == "normal") && 
			($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == "false") && 
			($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication'] == "false"))  // For OMTS Restricted and Off State
	   {
			
			if($isOmtsOffTimeRecording == '0')
			{
				//echo "OMTS OFF START Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOffArray['description'] = "OMTS-OFF";
				$tempOmtsOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['startTime'] = $doc['timestamp'];		
				$tempOmtsOffArray['computedFailure'] = "Restricted Area ";
				$tempOmtsOffArray['manualFailureEntry'] = "";				
				$isOmtsOffTimeRecording = '1';
			}
			
			if($isOmtsRestrictTimeRecording == '0')
			{
				//echo "OMTS RESTRICTED Area START Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsRestrictedArray['description'] = "OMTS-RESTICTED";
				$tempOmtsRestrictedArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsRestrictedArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsRestrictedArray['startTime'] = $doc['timestamp'];						
				$isOmtsRestrictTimeRecording = '1';
			}
						
	   }
	   
	   //Check for OMTS ON state
	   if($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == "true") // For OMTS On State
	   {			
			if($isOmtsOnTimeRecording == '0')
			{
				$tempOmtsOnArray['description'] = "OMTS-ON";
				$tempOmtsOnArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOnArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOnArray['startTime'] = $doc['timestamp'];					
				$isOmtsOnTimeRecording = '1';
			}			
	   }	   
	   
	   //Check for OMTS OFF State
	   if(($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == 'false')) // For OMTS Off State
	   {
			if($isOmtsOffTimeRecording == '0')
			{

				$tempOmtsOffArray['description'] = "OMTS-OFF";
				$tempOmtsOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['startTime'] = $doc['timestamp'];	
				$tempOmtsOffArray['computedFailure'] = "Unknown";
				$tempOmtsOffArray['manualFailureEntry'] = "";				
				$isOmtsOffTimeRecording = '1';
			}
	   }
	   
	   //SB:Iterations for SBB-1
	   //BeR: SDU down issue
	    if($iterationSBB1<4){
		   if($doc['PING_STATUS']['SBB-1'] == 'KO'){
				$iterationSBB1 = $iterationSBB1+1;
		   }else{
				$iterationSBB1 = 0;
		   }
		}
	   
	   //ARP table overflow issue
	   if($iterationARP<4){
		   if(($doc['PING_STATUS']['BTS1'] == 'KO') &&($doc['PING_STATUS']['BTS2'] == 'KO') &&($doc['PING_STATUS']['NCU'] == 'OK') &&($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&($doc['PING_STATUS']['DSU2'] == 'OK') &&($doc['PING_STATUS']['BTS1-CS'] == 'OK') &&($doc['PING_STATUS']['BTS2-CS'] == 'OK') &&($doc['PING_STATUS']['BTS1-ADB'] == 'OK') &&($doc['PING_STATUS']['BTS2-ADB'] == 'OK')){
				$iterationARP = $iterationARP+1;
		   }else{
				$iterationARP = 0;
		   }
	    }
		
		
		//Flight data stuck issue
		/*BeR:
		Flight data stuck issue updated:
		for some reason, the filter does not go through instances where altitude = -1000 (something to be debugged maybe later)
		in some cases of flight data stuck, ASC shared memory can be empty
		hence the filter I updated is only checking for latitude and longitude stuck. In that way with consecutive check lower to 3 it works ok (considering we are browsing climb-cruise-descent event, it is abnormal to have 3 successive iteration with a same altitude and longitude
		*/
		if($iterationFlight<3){
			if($prevLatitude != 'stuck' && $prevLongitude != 'stuck'){
				if((($doc['THALES_FLIGHT']['asdSbbLatitude'] == $prevLatitude)||($doc['THALES_FLIGHT']['asdSbbLongitude'] == $prevLongitude))){
					$iterationFlight = $iterationFlight+1;
					$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
					$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
				}else{
					$iterationFlight = 0;
					$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
					$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
				}
			}else{
				//check for first iteration
				$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
				$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
			}
		}
		
		
		//BeR: Antenna/BSU issue for conn tool log v1
		//checking condition from conn tool log v2 and discard this test if v2 (additional accurate filter with v2 for antenna and sdu and channel cards failures)
		if (!isset($doc['P5_PORT_STATUS']['speed'])){
			if($iterationsAntenna<10){
				if((($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') ||($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error')) &&
						($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
						($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
						($doc['PING_STATUS']['SBB-1'] == 'OK') &&
						($doc['TGS_ONAIR']['serviceAvailableDetail']!='restrictedAirspaceRegion') &&
						($doc['TGS_ONAIR']['serviceAvailable'] == 'false')){
							$iterationsAntenna = $iterationsAntenna+1;
				}else{
					$iterationsAntenna = 0;
					}	
			}
		}
		//BeR: SDU channel card 1 deregistration for conn tool log v1
		//checking condition from conn tool log v2 and discard this test if v2 (new CC test available via snmp variable in v2 is more reliable)
		if (!isset($doc['SDU_STATUS']['asuSduInfoCC1Status'])){
			if ($iterationsSDUCC1<4){
				if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&
					($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
					($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
					($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'startup')){
						$iterationsSDUCC1 = $iterationsSDUCC1+1;
				}else{
					$iterationsSDUCC1 = 0;
					}
			}
		}
		
		//BeR: NCU boot and GisDB issues
		if ($iterationsNCU_GisDB<4){
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'OK') &&
				($doc['PING_STATUS']['BTS1'] == 'OK') && 
				($doc['PING_STATUS']['BTS2'] == 'OK')){
					$iterationsNCU_GisDB = $iterationsNCU_GisDB+1;
			}else{
				$iterationsNCU_GisDB = 0;
			}			
		}
		
		//BeR: GSM stuck in startup and CC1 OK
		if ($iterationsGSMstartupCC1<4){
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0)){
					$iterationsGSMstartupCC1 = $iterationsGSMstartupCC1+1;
			}else{
				$iterationsGSMstartupCC1 = 0;
			}			
		}

		//BeR: WiFi and/or GSM inop after short satcom short disconnection
		//14Sep17: added BTS1 and BTS2 check to avoid conflict with ARP overflow issue
		//22Sep17: removing the filter. Despite adjustements, it is often triggered for incorrect reason.
		/*
		if ($iterationsWiFi_GSM_disconnect<10){
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'OK') &&
				($doc['PING_STATUS']['CWLU1'] == 'OK') &&
				($doc['PING_STATUS']['BTS1'] == 'OK') &&
				($doc['PING_STATUS']['BTS2'] == 'OK') &&
				((($doc['TGS_ONAIR']['applicationAvailable'] == 'false') && ($doc['TGS_ONAIR']['serviceAvailable'] == 'false') && ($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') && ($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable')) || ((($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error') || ($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup')) && ($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='false')))){
					$iterationsWiFi_GSM_disconnect = $iterationsWiFi_GSM_disconnect+1;
				}else{
					$iterationsWiFi_GSM_disconnect = 0;
				}	
		}
		*/
		
		//BeR: NCU unreachable if DSU1 or DSU2 is down
		if ($iterations_NCU_DSU_down<4){			
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['NCU-ADB'] == 'KO') &&
				($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&	
				(($doc['PING_STATUS']['DSU1'] == 'KO') || ($doc['PING_STATUS']['DSU2'] == 'KO'))){
					$iterations_NCU_DSU_down = $iterations_NCU_DSU_down+1;
				}else{
					$iterations_NCU_DSU_down = 0;					
				}					
		}
		
		//BeR: WiFi serviceAvailable only stuck on FALSE
		if ($iterationsWiFi_FALSE<10){	
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'false') &&		
				($doc['TGS_ONAIR']['applicationAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'noFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 10) &&
				($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0)){
					$iterationsWiFi_FALSE = $iterationsWiFi_FALSE+1;
				}else{
					$iterationsWiFi_FALSE = 0;					
				}	
		}

		//BeR: OMTS ground test mode active
		if ($iterationsOMTS_groundTest<4){
			if($doc['TGS_ONAIR']['inputBtsOverruleAllowIsOn'] == 'true'){
					$iterationsOMTS_groundTest = $iterationsOMTS_groundTest+1;
			}else{
				$iterationsOMTS_groundTest = 0;
			}			
		}
		
		//BeR: snmp service not starting on DSU-C
		//putting less parameter check than on the script. This should be sufficient to detect the issue.
		if ($iterationsSNMPservice_down<4){
			if(($doc['TGS_ONAIR']['applicationAvailableDetail'] == '') &&
				($doc['TGS_FLIGHT']['flightPhase'] == '') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == '')){
					$iterationsSNMPservice_down = $iterationsSNMPservice_down+1;
			}else{
				$iterationsSNMPservice_down = 0;
				}
		}		

		//BeR: system mode not in service
		if ($iterations_SystemMode<10){
			if($doc['THALES_FLIGHT']['asdSysIfeSystemMode'] != 'service'){
					$iterations_SystemMode = $iterations_SystemMode+1;
			}else{
				$iterations_SystemMode = 0;
				}
		}			

		//BeR: OMA switch off issue
		if ($iterations_SwitchOff<4){
			if(($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['BTS1'] == 'KO') &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['CWLU1'] == 'KO') &&
				($doc['PING_STATUS']['CWLU2'] == 'KO') &&
				($doc['PING_STATUS']['CWLU3'] == 'KO') &&
				($doc['PING_STATUS']['BTS2'] == 'KO')){
					$iterations_SwitchOff = $iterations_SwitchOff+1;
			}else{
				$iterations_SwitchOff = 0;
			}			
		}		

		//BeR: OMTS KO due to DSU3 down issue
		if ($iterations_OMTS_DSU3_down<4){
			if(($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['CWLU1'] == 'KO') &&
				($doc['PING_STATUS']['CWLU2'] == 'OK') &&
				($doc['PING_STATUS']['CWLU3'] == 'OK')){
					$iterations_OMTS_DSU3_down = $iterations_OMTS_DSU3_down+1;
			}else{
				$iterations_OMTS_DSU3_down = 0;
			}			
		}

		//BeR: OMTS KO due to ADBG down issue
		if ($iterations_OMTS_ADBG_down<4){
			if(($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'OK') &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['BTS1'] == 'KO') &&
				($doc['PING_STATUS']['BTS2'] == 'KO') &&
				($doc['PING_STATUS']['BTS1-CS'] == 'OK') &&
				($doc['PING_STATUS']['BTS2-CS'] == 'OK') &&
				($doc['PING_STATUS']['BTS1-ADB'] == 'KO') &&
				($doc['PING_STATUS']['BTS2-ADB'] == 'KO')){
					$iterations_OMTS_ADBG_down = $iterations_OMTS_ADBG_down+1;
			}else{
				$iterations_OMTS_ADBG_down = 0;
			}			
		}

		//BeR: GSM MIB unresponsive
		if ($iterations_GSM_MIB_down<10){
			if(($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == 'No') &&
				($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication'] == 'No') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'No')){
					$iterations_GSM_MIB_down = $iterations_GSM_MIB_down+1;
			}else{
				$iterations_GSM_MIB_down = 0;
			}			
		}
		
		//BeR: WiFi stuck on auto service enable false
		if ($iterations_WiFi_autoServiceEnableFalse<5){
			if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'autoServiceEnableFalse'){
					$iterations_WiFi_autoServiceEnableFalse = $iterations_WiFi_autoServiceEnableFalse+1;
			}else{
				$iterations_WiFi_autoServiceEnableFalse = 0;
			}			
		}		

		//BeR: WiFi stuck on Fap disabled
		if ($iterations_WiFi_FapDisabled<5){
			if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'FapDisabledService'){
					$iterations_WiFi_FapDisabled = $iterations_WiFi_FapDisabled+1;
			}else{
				$iterations_WiFi_FapDisabled = 0;
			}			
		}
		
		//BeR: NCU unreachable due to ADB KO
		if ($iterations_NCU_ADBG_down<4){
			if(($doc['PING_STATUS']['NCU'] == 'KO') && ($doc['PING_STATUS']['NCU-ADB'] == 'KO') && ($doc['PING_STATUS']['DSU1'] == 'OK') && ($doc['PING_STATUS']['DSU2'] == 'OK')){
					$iterations_NCU_ADBG_down = $iterations_NCU_ADBG_down+1;
			}else{
				$iterations_NCU_ADBG_down = 0;
			}			
		}		

		//BeR: NCU KO
		if ($iterations_NCU_KO<4){
			if(($doc['PING_STATUS']['NCU'] == 'KO') && ($doc['PING_STATUS']['NCU-ADB'] == 'OK') && ($doc['PING_STATUS']['DSU1'] == 'OK') && ($doc['PING_STATUS']['DSU2'] == 'OK')){
					$iterations_NCU_KO = $iterations_NCU_KO+1;
			}else{
				$iterations_NCU_KO = 0;
			}			
		}		
		
		//BeR: DSU-C port P5 check (from conn tool v2)
		//testing if P5.speed exist to confirm conn tool v2
		if (isset($doc['P5_PORT_STATUS']['speed'])){
			if ($iterations_DSU_P5<4){
				if(($doc['P5_PORT_STATUS']['speed'] != '100Mbps') || ($doc['P5_PORT_STATUS']['status'] != 'Pass')){
						$iterations_DSU_P5 = $iterations_DSU_P5+1;
				}else{
					$iterations_DSU_P5 = 0;
				}			
			}					
		}

		//BeR: AVCD KO
		if ($iterations_AVCD<4){
			if(($doc['PING_STATUS']['AVCD1'] == 'KO')){
					$iterations_AVCD = $iterations_AVCD+1;
			}else{
				$iterations_AVCD = 0;
			}			
		}

		//BeR: NCU LED or error message checks (from conn tool v2)
		//testing if TGS_ONAIR.ncuErrorMsg exist to confirm conn tool v2
		if (isset($doc['TGS_ONAIR']['ncuErrorMsg'])){
			if ($iterations_NCU_LedOrErrorMsg<4){
				if(($doc['TGS_ONAIR']['ncuErrorMsg'] != 'FAIL') || ($doc['TGS_ONAIR']['ncuLedPwr'] != 'on(1)') || ($doc['TGS_ONAIR']['ncuLedSysOk'] != 'on(1)') || ($doc['TGS_ONAIR']['ncuLedBootRdy'] != 'on')){
						$iterations_NCU_LedOrErrorMsg = $iterations_NCU_LedOrErrorMsg+1;
				}else{
					$iterations_NCU_LedOrErrorMsg = 0;
				}			
			}					
		}
		
		
		//BeR: Connectivity SW processes checks (from conn tool v2)
		//testing if SERVICE_STATUS.gsm status exists to confirm conn tool v2
		if (isset($doc['SERVICE_STATUS']['gsm status'])){
			if ($iterations_ProcessSW<4){
				if(($doc['SERVICE_STATUS']['gsm status'] != 'running...') || ($doc['SERVICE_STATUS']['vocem status'] != 'running...') || ($doc['SERVICE_STATUS']['agp status'] != 'running...') || ($doc['SERVICE_STATUS']['agprs status'] != 'running...') || ($doc['SERVICE_STATUS']['stu status'] != 'running...') || ($doc['SERVICE_STATUS']['nXt_Agent status'] != 'running') || ($doc['SERVICE_STATUS']['linkcontroller status'] != 'running...') || ($doc['SERVICE_STATUS']['named status'] != 'running...') || ($doc['SERVICE_STATUS']['snpd status'] != 'running...')){
						$iterations_ProcessSW = $iterations_ProcessSW+1;
				}else{
					$iterations_ProcessSW = 0;
				}			
			}					
		}

		//BeR: SDU health check (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterations_SDU_health<4){
				if($doc['SDU_STATUS']['asuSduInfoOverallStatus'] != 'pass(1)'){
						$iterations_SDU_health = $iterations_SDU_health+1;
				}else{
					$iterations_SDU_health = 0;
				}			
			}					
		}

		//BeR: SDU CC1 down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsSDUCC1_v2<4){
				if($doc['SDU_STATUS']['asuSduInfoCC1Status'] != 'pass(1)'){
						$iterationsSDUCC1_v2 = $iterationsSDUCC1_v2+1;
				}else{
					$iterationsSDUCC1_v2 = 0;
				}			
			}					
		}

		//BeR: SDU CC2 down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsSDUCC2_v2<4){
				if($doc['SDU_STATUS']['asuSduInfoCC2Status'] != 'pass(1)'){
						$iterationsSDUCC2_v2 = $iterationsSDUCC2_v2+1;
				}else{
					$iterationsSDUCC2_v2 = 0;
				}			
			}					
		}

		//BeR: SDU-Antenna bus down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsSDU_Ant_bus<4){
				if($doc['SDU_STATUS']['asuSduInfoAntennaBus'] != 'pass(1)'){
						$iterationsSDU_Ant_bus = $iterationsSDU_Ant_bus+1;
				}else{
					$iterationsSDU_Ant_bus = 0;
				}			
			}					
		}

		//BeR: DLNA down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterations_DLNA<4){
				if($doc['SDU_STATUS']['asuDlnaInfoOverallStatus'] != 'pass(1)'){
						$iterations_DLNA = $iterations_DLNA+1;
				}else{
					$iterations_DLNA = 0;
				}			
			}					
		}
		
		//BeR: Antenna down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsAntenna_v2<4){
				if (($doc['SDU_STATUS']['asuAntInfoOverallStatus'] != 'pass(1)') && ($doc['SDU_STATUS']['asuSduInfoAntennaBus'] == 'pass(1)')){
						$iterationsAntenna_v2 = $iterationsAntenna_v2+1;
				}else{
					$iterationsAntenna_v2 = 0;
				}			
			}					
		}		

		//BeR: nslookup unsuccessful (from conn tool v2)
		//testing if SYS_DATA.nslookup exists to confirm conn tool v2
		if (isset($doc['SYS_DATA']['nslookup'])){
			if ($iterations_nslookup<4){
				if($doc['SYS_DATA']['nslookup'] == '0'){
						$iterations_nslookup = $iterations_nslookup+1;
				}else{
					$iterations_nslookup = 0;
				}			
			}					
		}

	   //BeR: ground failure check
	    if($iterations_groundFailure<4){
		   if(($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'groundSystemError') || ($doc['TGS_ONAIR']['applicationAvailableDetail'] == '14')){
				$iterations_groundFailure = $iterations_groundFailure+1;
		   }else{
				$iterations_groundFailure = 0;
		   }
		}

	   //BeR: WiFi only down - commFailure (ISE-4422)
	    if($iterations_WiFi_CommFailure<5){
		   if( ($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['TGS_ONAIR']['applicationAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['serviceAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='normal') &&
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='true') ) {
					$iterations_WiFi_CommFailure = $iterations_WiFi_CommFailure+1;
			}else{
				$iterations_WiFi_CommFailure = 0;
		   }
		}

		
	   //Check for any Failures available
	   
	   //BeR: removing SBB/SDU down one time check. Being replaced by check with several iterations
	   /*
	   if($doc['PING_STATUS']['SBB-1'] == 'KO')
	   {
			failureRecordForWifiEvent("SBB1 Down", $tempWifiOffArray);
			failureRecordForOmtsEvent("SBB1 Down", $tempOmtsOffArray);			
		
	   }
	   */
	   
	   //BeR removing BTS KO filter which is not a root cause. Replaced with ARP overflow issue
	   /*
	   if(	$doc['PING_STATUS']['BTS1'] == 'KO' && 
			$doc['PING_STATUS']['BTS2'] == 'KO')
	   {
			failureRecordForOmtsEvent("BTS 1 & 2 Down", $tempOmtsOffArray);
		
	   }	
		*/
		
		//BeR: removing FapDisable which is not relevant per the log review done and can be triggered for incorrect reason
		/*
   	   if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'FapDisabledService')
	   {
			failureRecordForWifiEvent("FapDisabledService", $tempWifiOffArray);
			failureRecordForOmtsEvent("FapDisabledService", $tempOmtsOffArray);	
	
	   }	   
	   */
	   
	   //BeR: removing ground error check. Not relevant for now
	   /*
   	   if( ($doc['TGS_ONAIR']['applicationAvailableDetail'] == '14' || 
			$doc['TGS_ONAIR']['applicationAvailableDetail'] == 'groundSystemError'))
	   {
			failureRecordForWifiEvent("Ground Error", $tempWifiOffArray);
			failureRecordForOmtsEvent("Ground Error", $tempOmtsOffArray);	

	   }
		*/

		 //SB:Filter for more Check
		if($doc['TGS_ONAIR']['serviceAvailable'] == 'false'){
			//WiFi inop with serviceAvailable only stuck on FALSE issue==1
			//BeR: removing single check of WiFi stuck on FALSE. Too many unexpected trigger due to usual hiccup at start of cruise
			/*
			if(($doc['TGS_ONAIR']['applicationAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'noFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 10))			
			{
				failureRecordForWifiEvent("ISE-3984 WiFi inop - WiFi serviceAvailable only stuck on FALSE issue detected", $tempWifiOffArray);
			}
			*/
			
			//WiFi+GSM inop after SDU short disconnection==14
			//BeR: removing regular WiFi GSM short disconnection check and adding an iteration check instead
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['TGS_ONAIR']['applicationAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error') &&
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='false') &&
				($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication']=='false')) 
			{
				failureRecordForWifiEvent("issue ISE-3839 WiFi+GSM inop after SDU short disconnection detected", $tempWifiOffArray);
			}
			*/
		}
		
		if($doc['TGS_ONAIR']['serviceAvailable'] == 'true'){
			//SDU CC1 de registration issue==3
			//BeR: removing one time check and adding iteration check for SDU CC1 deregistration
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'startup'))			
			{
				failureRecordForWifiEvent("SDU channel card 1 de registration issue detected", $tempWifiOffArray);
			}
			*/
			
			//NCU boot up error issues==4
			//BeR: removing regular NCU boot check and adding a NCU+GisDb iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['btsARFCN'] == '-1'))			
			{
				failureRecordForWifiEvent("potential NCU bootup error issue detected", $tempWifiOffArray);
			}
			*/
			
			//GSM stuck in startup with CC1 ok==7
			//BeR: removing regular GSM startup/CC1 ok check and adding iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0)) 
			{
				failureRecordForWifiEvent("issue ISE-4049 GSM stuck in startup - CC up and logtable ok detected", $tempWifiOffArray);
			}
			*/
			
			//GisDB connection issue==9
			//BeR: removing regular GisDB issue check and adding a NCU+GisDb iteration check instead
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['btsARFCN'] == 580) &&
				($doc['PING_STATUS']['BTS1'] == 'OK') && 
				($doc['PING_STATUS']['BTS2'] == 'OK'))			
			{
				failureRecordForWifiEvent("potential GisDB connection issue detected", $tempWifiOffArray);
			}
			*/
			
			//NCU unreachable due to either DSU1 or DSU2 down issue==10
			//BeR: removing NCU/DSU down to update it to iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['NCU-ADB'] == 'KO') &&
				(($doc['PING_STATUS']['DSU1'] == 'KO') || ($doc['PING_STATUS']['DSU2'] == 'KO')))			
			{
				failureRecordForWifiEvent("NCU unreachable due to either DSU1 or DSU2 down issue detected", $tempWifiOffArray);
			}
			*/
		}
		
		//no active user issue
		if($totalActiveUser == 0){
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') && ($doc['TGS_ONAIR']['applicationAvailable'] == 'true') && ($doc['TGS_ONAIR']['currentActiveUsers'] == 0)){
					$userValue = 1;
			}else{
				$totalActiveUser = $doc['TGS_ONAIR']['currentActiveUsers'];
			}
		}

		//BeR: stuck on restrictedAirspace issue. Failure is considered only if all CRUISE iteration are in restrictedAirspaceRegion
		if($AirspaceCheck == 'restrictedAirspaceRegion'){
			if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'restrictedAirspaceRegion'){
					$RestrictedAirspace = 1;
			}else{
				$AirspaceCheck = $doc['TGS_ONAIR']['serviceAvailableDetail'];
			}
		}
		
		   $isAltitudeRecordingOn = '1';
		   $tempAltitudeAbove10KArray['tailSign'] = $doc['tailSign'];
		   $tempAltitudeAbove10KArray['altitude10KStartTime'] = $doc['timestamp'];
		   $tempAltitudeAbove10KArray['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
		   $tempAltitudeAbove10KArray['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];
			//echo "10K FT Start Time is $timeStamp <br>";
	}
	elseif((($recordDecentTimeAsEndTime == '1') || 
			(($altitudeValue != null ) && 
			($altitudeValue < $altThreshold))) && 
			($isAltitudeRecordingOn == '1'))  
	{ 
		// ****************** MAIN CASE 3: Altitude is getting below 10K FT or Decent end time is reached ************* //
		
		if($recordDecentTimeAsEndTime == '1')
		{
			$timeStamp = $prevTimeStamp;
		}
		else
		{
			$timeStamp = $doc['timestamp'];	
		}
		
		if(!is_array($tempOmtsOffArray))
		{
			$tempOmtsOffArray = array();
		}
		if(!is_array($tempWifiOffArray))
		{
			$tempWifiOffArray = array();
		}		
		
		//Record the end time of the flight and its going to be BELOW 10K FT
		if (($altitudeValue > 0) || 
			($recordDecentTimeAsEndTime == '1'))
		{
		
		   //Wifi Restricted Area
		   if($isWifiRestrictedAreaRecording == '1')
		   {
		   
				wifiRestrictedAreaEndTime(	$doc['timestamp'],
											$doc['TGS_FLIGHT']['latitude'],
											$doc['TGS_FLIGHT']['longitude'],
											$tempWifiRestrictedAreaArray,
											$tempWifiRestrictedAreaArrayCoordinates,
											$totalTimeDurationWifiRestrictedArea);
											
				array_push($wifiAvailabilityEventsArray,$tempWifiRestrictedAreaArray);		
				unset($tempWifiRestrictedAreaArray);				
				$tempWifiRestrictedAreaArray = array();		
			
				$isWifiRestrictedAreaRecording = '0';
		   }
		   			   
			if(	($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == "normal") && 
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == "false") && 
				($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication'] == "false"))  // For OMTS Restricted and Off State
			{
				
				if($isOmtsOffTimeRecording == '1')
				{
					$tempOmtsOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempOmtsOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
					$tempOmtsOffArray['endTime'] = $doc['timestamp'];	
					$tempOmtsOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempOmtsOffArray['computedFailure'])));
					$tempOmtsOffArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempOmtsOffArrayCoordinates['startLongitude'],$tempOmtsOffArrayCoordinates['startLatitude']),
																array($tempOmtsOffArrayCoordinates['endLongitude'],$tempOmtsOffArrayCoordinates['endLatitude'])
														));			

					$timeFirstOmts  = strtotime($tempOmtsOffArray['startTime']);
					$timeSecondOmts = strtotime($tempOmtsOffArray['endTime']);
					$timeDurationOmtsOff = $timeSecondOmts - $timeFirstOmts;
					$totalTimeDurationOmtsOff = $totalTimeDurationOmtsOff + $timeDurationOmtsOff;
				
					array_push($omtsAvailabilityEventsArray,$tempOmtsOffArray);		
					unset($tempOmtsOffArray);				
					$tempOmtsOffArray = array();						
					$isOmtsOffTimeRecording = '0';
				}
				
				if($isOmtsRestrictTimeRecording == '1')
				{
					$tempOmtsRestrictedArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempOmtsRestrictedArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
					$tempOmtsRestrictedArray['endTime'] = $doc['timestamp'];		
					$tempOmtsRestrictedArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempOmtsRestrictedArrayCoordinates['startLongitude'],$tempOmtsRestrictedArrayCoordinates['startLatitude']),
																array($tempOmtsRestrictedArrayCoordinates['endLongitude'],$tempOmtsRestrictedArrayCoordinates['endLatitude'])
														));		

					$timeFirstOmts  = strtotime($tempOmtsRestrictedArray['startTime']);
					$timeSecondOmts = strtotime($tempOmtsRestrictedArray['endTime']);
					$timeDurationOmtsRestricted = $timeSecondOmts - $timeFirstOmts;
					$totalTimeDurationOmtsRestricted = $totalTimeDurationOmtsRestricted + $timeDurationOmtsRestricted;
					
					array_push($omtsAvailabilityEventsArray,$tempOmtsRestrictedArray);		
					unset($tempOmtsRestrictedArray);				
					$tempOmtsRestrictedArray = array();						
					$isOmtsRestrictTimeRecording = '0';
				}
				if($isOmtsOnTimeRecording == '1')
				{
					$tempOmtsOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempOmtsOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
					$tempOmtsOnArray['endTime'] = $doc['timestamp'];	
					$tempOmtsOnArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempOmtsOnArrayCoordinates['startLongitude'],$tempOmtsOnArrayCoordinates['startLatitude']),
																array($tempOmtsOnArrayCoordinates['endLongitude'],$tempOmtsOnArrayCoordinates['endLatitude'])
														));	

					$timeFirstOmts  = strtotime($tempOmtsOnArray['startTime']);
					$timeSecondOmts = strtotime($tempOmtsOnArray['endTime']);
					$timeDurationOmtsOn = $timeSecondOmts - $timeFirstOmts;
					$totalTimeDurationOmtsOn = $totalTimeDurationOmtsOn + $timeDurationOmtsOn;
														
														
					array_push($omtsAvailabilityEventsArray,$tempOmtsOnArray);		
					unset($tempOmtsOnArray);				
					$tempOmtsOnArray = array();						
					$isOmtsOnTimeRecording = '0';
				}				
				
			}
		   
		   if($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == "true") // For OMTS On State
		   {
				
				if($isOmtsOnTimeRecording == '1')
				{
					$tempOmtsOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempOmtsOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
					$tempOmtsOnArray['endTime'] = $doc['timestamp'];	
					$tempOmtsOnArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempOmtsOnArrayCoordinates['startLongitude'],$tempOmtsOnArrayCoordinates['startLatitude']),
																array($tempOmtsOnArrayCoordinates['endLongitude'],$tempOmtsOnArrayCoordinates['endLatitude'])
														));	

					$timeFirstOmts  = strtotime($tempOmtsOnArray['startTime']);
					$timeSecondOmts = strtotime($tempOmtsOnArray['endTime']);
					$timeDurationOmtsOn = $timeSecondOmts - $timeFirstOmts;
					$totalTimeDurationOmtsOn = $totalTimeDurationOmtsOn + $timeDurationOmtsOn;
				
					array_push($omtsAvailabilityEventsArray,$tempOmtsOnArray);		
					unset($tempOmtsOnArray);				
					$tempOmtsOnArray = array();						
					$isOmtsOnTimeRecording = '0';
				}
				
		   }	   
		   
		   if(	($doc['TGS_ONAIR']['gsmConnexTsSystemState'] != 'normal') && 
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == 'false')) // For OMTS Off State
		   {
				if($isOmtsOffTimeRecording == '1')
				{

				$tempOmtsOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['endTime'] = $doc['timestamp'];
				$tempOmtsOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempOmtsOffArray['computedFailure'])));
				$tempOmtsOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsOffArrayCoordinates['startLongitude'],$tempOmtsOffArrayCoordinates['startLatitude']),
															array($tempOmtsOffArrayCoordinates['endLongitude'],$tempOmtsOffArrayCoordinates['endLatitude'])
													));			

				$timeFirstOmts  = strtotime($tempOmtsOffArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsOffArray['endTime']);
				$timeDurationOmtsOff = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsOff = $totalTimeDurationOmtsOff + $timeDurationOmtsOff;

				array_push($omtsAvailabilityEventsArray,$tempOmtsOffArray);		
				unset($tempOmtsOffArray);				
				$tempOmtsOffArray = array();						
				$isOmtsOffTimeRecording = '0';
				}
				
		   }
	   
		   if($doc['TGS_ONAIR']['serviceAvailable'] == 'true')
		   {
				if($isWifiOnTimeRecording == '1'){
					$wifiStatusOnTimeEnd10K = $timeStamp;
					$tempWifiOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempWifiOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	 
					$tempWifiOnArray['endTime'] = $timeStamp;
					$tempWifiOnArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempWifiOnArrayCoordinates['startLongitude'],$tempWifiOnArrayCoordinates['startLatitude']),
																array($tempWifiOnArrayCoordinates['endLongitude'],$tempWifiOnArrayCoordinates['endLatitude'])
														));
					array_push($wifiAvailabilityEventsArray,$tempWifiOnArray);
					unset($tempWifiOnArray);
					unset($tempWifiOnArrayCoordinates);
					$tempWifiOnArrayCoordinates = array();
					$tempWifiOnArray = array();
					
					$timeFirst  = strtotime($wifiStatusOnTimeStart10K);
					$timeSecond = strtotime($wifiStatusOnTimeEnd10K);
					$timeDurationOn = $timeSecond - $timeFirst;
					$totalTimeDurationOn = $totalTimeDurationOn + $timeDurationOn;
					
					$isWifiOnTimeRecording = '0';	
					// important debug echo
					//echo "Altitude below 10KFT Wifi is ON and Its END Time is $wifiStatusOnTimeEnd10K and Duration On Time $timeDurationOn<br>";	   
				}
		   }
		   elseif($doc['TGS_ONAIR']['serviceAvailable'] == 'false')
		   {
				if($isWifiOffTimeRecording == '1'){
					$wifiStatusOffTimeEnd10K = $timeStamp;
					$tempWifiOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempWifiOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];
					$tempWifiOffArray['endTime'] = $timeStamp;
					$tempWifiOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempWifiOffArray['computedFailure'])));
					$tempWifiOffArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempWifiOffArrayCoordinates['startLongitude'],$tempWifiOffArrayCoordinates['startLatitude']),
																array($tempWifiOffArrayCoordinates['endLongitude'],$tempWifiOffArrayCoordinates['endLatitude'])
														));		
					array_push($wifiAvailabilityEventsArray,$tempWifiOffArray);
					unset($tempWifiOffArray);
					unset($tempWifiOffArrayCoordinates);
					$tempWifiOffArrayCoordinates = array();
					$tempWifiOffArray = array();
					
					$timeFirst  = strtotime($wifiStatusOffTimeStart10K);
					$timeSecond = strtotime($wifiStatusOffTimeEnd10K);
					$timeDurationOff = $timeSecond - $timeFirst;
					$totalTimeDurationOff = $totalTimeDurationOff + $timeDurationOff;		
					
					$isWifiOffTimeRecording = '0';	   
					// important debug echo
					//echo "Altitude below 10KFT Wifi is OFF and Its END Time is $wifiStatusOffTimeEnd10K and Duration Off Time $timeDurationOff <br>";
				}
		   }
		   else
		   {
				//do nothing
		   }
		   		
			// ***************** START - If Still There are some Event is In On State Close it ****************** //
			
				// Stop WIFI ON Recording			
				if($isWifiOnTimeRecording == '1'){
					$wifiStatusOnTimeEnd10K = $timeStamp;
					$tempWifiOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempWifiOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	 
					$tempWifiOnArray['endTime'] = $timeStamp;
					$tempWifiOnArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempWifiOnArrayCoordinates['startLongitude'],$tempWifiOnArrayCoordinates['startLatitude']),
																array($tempWifiOnArrayCoordinates['endLongitude'],$tempWifiOnArrayCoordinates['endLatitude'])
														));
					
					array_push($wifiAvailabilityEventsArray,$tempWifiOnArray);
					unset($tempWifiOnArray);
					unset($tempWifiOnArrayCoordinates);
					$tempWifiOnArrayCoordinates = array();
					$tempWifiOnArray = array();
					
					$timeFirst  = strtotime($wifiStatusOnTimeStart10K);
					$timeSecond = strtotime($wifiStatusOnTimeEnd10K);
					$timeDurationOn = $timeSecond - $timeFirst;
					$totalTimeDurationOn = $totalTimeDurationOn + $timeDurationOn;
					
					$isWifiOnTimeRecording = '0';	
					// important debug echo
					//echo "Altitude below 10KFT Wifi is ON and Its END Time is $wifiStatusOnTimeEnd10K and Duration On Time $timeDurationOn<br>";	   
				}

				// Stop WIFI OFF Recording				
				if($isWifiOffTimeRecording == '1'){
					$wifiStatusOffTimeEnd10K = $timeStamp;
					$tempWifiOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempWifiOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];
					$tempWifiOffArray['endTime'] = $timeStamp;
					$tempWifiOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempWifiOffArray['computedFailure'])));
					$tempWifiOffArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempWifiOffArrayCoordinates['startLongitude'],$tempWifiOffArrayCoordinates['startLatitude']),
																array($tempWifiOffArrayCoordinates['endLongitude'],$tempWifiOffArrayCoordinates['endLatitude'])
														));	
					array_push($wifiAvailabilityEventsArray,$tempWifiOffArray);
					unset($tempWifiOffArray);
					unset($tempWifiOffArrayCoordinates);
					$tempWifiOffArrayCoordinates = array();
					$tempWifiOffArray = array();
					
					$timeFirst  = strtotime($wifiStatusOffTimeStart10K);
					$timeSecond = strtotime($wifiStatusOffTimeEnd10K);
					$timeDurationOff = $timeSecond - $timeFirst;
					$totalTimeDurationOff = $totalTimeDurationOff + $timeDurationOff;		
					
					$isWifiOffTimeRecording = '0';	   
					// important debug echo
					//echo "Altitude below 10KFT Wifi is OFF and Its END Time is $wifiStatusOffTimeEnd10K and Duration Off Time $timeDurationOff <br>";
				}
				
				// Stop WIFI Restricted Recording				
				if($isWifiRestrictedAreaRecording == '1')
				{

					wifiRestrictedAreaEndTime(	$doc['timestamp'],
												$doc['TGS_FLIGHT']['latitude'],
												$doc['TGS_FLIGHT']['longitude'],
												$tempWifiRestrictedAreaArray,
												$tempWifiRestrictedAreaArrayCoordinates,
												$totalTimeDurationWifiRestrictedArea);
												
					array_push($wifiAvailabilityEventsArray,$tempWifiRestrictedAreaArray);		
					unset($tempWifiRestrictedAreaArray);				
					$tempWifiRestrictedAreaArray = array();		

					$isWifiRestrictedAreaRecording = '0';
				}
		   
				// Stop OMTS ON Recording
				if($isOmtsOnTimeRecording == '1')
				{

					$tempOmtsOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempOmtsOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
					$tempOmtsOnArray['endTime'] = $doc['timestamp'];	
					$tempOmtsOnArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempOmtsOnArrayCoordinates['startLongitude'],$tempOmtsOnArrayCoordinates['startLatitude']),
																array($tempOmtsOnArrayCoordinates['endLongitude'],$tempOmtsOnArrayCoordinates['endLatitude'])
														));	

					$timeFirstOmts  = strtotime($tempOmtsOnArray['startTime']);
					$timeSecondOmts = strtotime($tempOmtsOnArray['endTime']);
					$timeDurationOmtsOn = $timeSecondOmts - $timeFirstOmts;
					$totalTimeDurationOmtsOn = $totalTimeDurationOmtsOn + $timeDurationOmtsOn;
				
					array_push($omtsAvailabilityEventsArray,$tempOmtsOnArray);		
					unset($tempOmtsOnArray);				
					$tempOmtsOnArray = array();						
					$isOmtsOnTimeRecording = '0';
				}
				
				// Stop OMTS OFF Recording				
				if($isOmtsOffTimeRecording == '1')
				{


				$tempOmtsOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['endTime'] = $doc['timestamp'];
				$tempOmtsOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempOmtsOffArray['computedFailure'])));				
				$tempOmtsOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsOffArrayCoordinates['startLongitude'],$tempOmtsOffArrayCoordinates['startLatitude']),
															array($tempOmtsOffArrayCoordinates['endLongitude'],$tempOmtsOffArrayCoordinates['endLatitude'])
													));			

				$timeFirstOmts  = strtotime($tempOmtsOffArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsOffArray['endTime']);
				$timeDurationOmtsOff = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsOff = $totalTimeDurationOmtsOff + $timeDurationOmtsOff;

				array_push($omtsAvailabilityEventsArray,$tempOmtsOffArray);		
				unset($tempOmtsOffArray);				
				$tempOmtsOffArray = array();						
				$isOmtsOffTimeRecording = '0';
				}

				// Stop OMTS Restricted Recording
				if($isOmtsRestrictTimeRecording == '1')
				{

					$tempOmtsRestrictedArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
					$tempOmtsRestrictedArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
					$tempOmtsRestrictedArray['endTime'] = $doc['timestamp'];		
					$tempOmtsRestrictedArray['location'] =  array(	"type" => "LineString", 
															"coordinates" => array(
																array($tempOmtsRestrictedArrayCoordinates['startLongitude'],$tempOmtsRestrictedArrayCoordinates['startLatitude']),
																array($tempOmtsRestrictedArrayCoordinates['endLongitude'],$tempOmtsRestrictedArrayCoordinates['endLatitude'])
														));		

					$timeFirstOmts  = strtotime($tempOmtsRestrictedArray['startTime']);
					$timeSecondOmts = strtotime($tempOmtsRestrictedArray['endTime']);
					$timeDurationOmtsRestricted = $timeSecondOmts - $timeFirstOmts;
					$totalTimeDurationOmtsRestricted = $totalTimeDurationOmtsRestricted + $timeDurationOmtsRestricted;
					
					array_push($omtsAvailabilityEventsArray,$tempOmtsRestrictedArray);		
					unset($tempOmtsRestrictedArray);				
					$tempOmtsRestrictedArray = array();						
					$isOmtsRestrictTimeRecording = '0';
				}
			
				
				
			// ***************** End of - some Event is In On State Close it ****************** //
			
		  //SB:Iterations for SBB-1
		if($iterationSBB1<4){
			if($doc['PING_STATUS']['SBB-1'] == 'KO'){
				$iterationSBB1 = $iterationSBB1+1;
			}else{
				$iterationSBB1 = 0;
			}
		}
		
		//no active user issue
		if($totalActiveUser == 0){
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') && ($doc['TGS_ONAIR']['applicationAvailable'] == 'true') && ($doc['TGS_ONAIR']['currentActiveUsers'] == 0)){
					$userValue = 1;
			}else{
				$totalActiveUser = $doc['TGS_ONAIR']['currentActiveUsers'];
			}
		}
			
	   //ARP table overflow issue
	   if($iterationARP<4){
		   if(($doc['PING_STATUS']['BTS1'] == 'KO') &&($doc['PING_STATUS']['BTS2'] == 'KO') &&($doc['PING_STATUS']['NCU'] == 'OK') &&($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&($doc['PING_STATUS']['DSU2'] == 'OK') &&($doc['PING_STATUS']['BTS1-CS'] == 'OK') &&($doc['PING_STATUS']['BTS2-CS'] == 'OK') &&($doc['PING_STATUS']['BTS1-ADB'] == 'OK') &&($doc['PING_STATUS']['BTS2-ADB'] == 'OK')){
				$iterationARP = $iterationARP+1;
		   }else{
				$iterationARP = 0;
		   }
	    }
		
		//Flight data stuck issue
		/*BeR:
		Flight data stuck issue updated:
		for some reason, the filter does not go through instances where altitude = -1000 (something to be debugged maybe later)
		in some cases of flight data stuck, ASC shared memory can be empty
		hence the filter I updated is only checking for latitude and longitude stuck. In that way with consecutive check lower to 3 it works ok (considering we are browsing climb-cruise-descent event, it is abnormal to have 3 successive iteration with a same altitude and longitude
		*/
		if($iterationFlight<3){
			if($prevLatitude != 'stuck' && $prevLongitude != 'stuck'){
				if((($doc['THALES_FLIGHT']['asdSbbLatitude'] == $prevLatitude)||($doc['THALES_FLIGHT']['asdSbbLongitude'] == $prevLongitude))){
					$iterationFlight = $iterationFlight+1;
					$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
					$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
				}else{
					$iterationFlight = 0;
					$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
					$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
				}
			}else{
				//check for first iteration
				$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
				$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
			}
		}
			
		
		   //Check for any Failures available
		   
		   //BeR: removing SBB/SDU down one time check. Being replaced by check with several iterations
		   /*
		   if($doc['PING_STATUS']['SBB-1'] == 'KO')
		   {
				failureRecordForWifiEvent("SBB1 Down",$tempWifiOffArray);
				failureRecordForOmtsEvent("SBB1 Down",$tempOmtsOffArray);			
				
		   }
		   */
		   
		   //BeR removing BTS KO filter which is not a root cause. Replaced with ARP overflow issue
		   /*
		   if(	$doc['PING_STATUS']['BTS1'] == 'KO' && 
				$doc['PING_STATUS']['BTS2'] == 'KO')
		   {
				failureRecordForOmtsEvent("BTS 1 & 2 Down", $tempOmtsOffArray);
				
		   }
			*/

			//BeR: removing FapDisable which is not relevant per the log review done and can be triggered for incorrect reason
			/*
		   if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'FapDisabledService')
		   {
				failureRecordForWifiEvent("FapDisabledService", $tempWifiOffArray);
				failureRecordForOmtsEvent("FapDisabledService", $tempOmtsOffArray);	
				
		   }	   
		   */
		   
		   //BeR: removing ground error check. Not relevant for now
		   /*
		   if(	($doc['TGS_ONAIR']['applicationAvailableDetail'] == '14' || 
				$doc['TGS_ONAIR']['applicationAvailableDetail'] == 'groundSystemError'))
		   {
				failureRecordForWifiEvent("Ground Error", $tempWifiOffArray);
				failureRecordForOmtsEvent("Ground Error", $tempOmtsOffArray);	

		   } 
			*/
		//echo "activeuser $totalActiveUser"."<br>";
		//echo "uservalue $userValue"."<br>";
		//echo "SBB1 $iterationSBB1"."<br>";
		//echo "ARP $iterationARP"."<br>";
		$test =$doc['ASC_SHARED']['Flight Phase'];
		//echo "new data$test"."<br>";
		//echo "prevLatitude $prevLatitude"."<br>";
		//echo "prevLongitude $prevLongitude"."<br>";
		//echo "FlightDataStuck $iterationFlight"."<br>";
		
		if($doc['TGS_ONAIR']['serviceAvailable'] == 'false'){
			//WiFi inop with serviceAvailable only stuck on FALSE issue==1
			//BeR: removing single check of WiFi stuck on FALSE. Too many unexpected trigger due to usual hiccup at start of cruise
			/*
			if(($doc['TGS_ONAIR']['applicationAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'noFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 10))			
			{
				failureRecordForWifiEvent("ISE-3984 WiFi inop - WiFi serviceAvailable only stuck on FALSE issue detected", $tempWifiOffArray);
			}
			*/
			
			//Antenna/BSU or both CC de registered or SDU health oid in error issues==8
			/*
			if((($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') ||($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error')) &&
				($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK'))	
			{
				failureRecordForWifiEvent("Antenna issue detected", $tempWifiOffArray);
			}
			*/
			
			//WiFi+GSM inop after SDU short disconnection==14
			//BeR: removing regular WiFi GSM short disconnection check and adding an iteration check instead
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['TGS_ONAIR']['applicationAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error') &&
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='false') &&
				($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication']=='false')) 
			{
				failureRecordForWifiEvent("issue ISE-3839 WiFi+GSM inop after SDU short disconnection detected", $tempWifiOffArray);
			}
			*/
		}
		
		if($doc['TGS_ONAIR']['serviceAvailable'] == 'true'){
			//SDU CC1 de registration issue==3
			//BeR: removing one time check and adding iteration check for SDU CC1 deregistration
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'startup'))			
			{
				failureRecordForWifiEvent("SDU channel card 1 de registration issue detected", $tempWifiOffArray);
			}
			*/
			
			//NCU boot up error issues==4
			//BeR: removing regular NCU boot check and adding a NCU+GisDb iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['btsARFCN'] == '-1'))			
			{
				failureRecordForWifiEvent("potential NCU bootup error issue detected", $tempWifiOffArray);
			}
			*/
			
			//GSM stuck in startup with CC1 ok==7
			//BeR: removing regular GSM startup/CC1 ok check and adding iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0)) 
			{
				failureRecordForWifiEvent("issue ISE-4049 GSM stuck in startup - CC up and logtable ok detected", $tempWifiOffArray);
			}
			*/
			
			//GisDB connection issue==9
			//BeR: removing regular GisDB issue check and adding a NCU+GisDb iteration check instead
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['btsARFCN'] == 580) &&
				($doc['PING_STATUS']['BTS1'] == 'OK') && 
				($doc['PING_STATUS']['BTS2'] == 'OK'))			
			{
				failureRecordForWifiEvent("potential GisDB connection issue detected", $tempWifiOffArray);
			}
			*/
			
			//NCU unreachable due to either DSU1 or DSU2 down issue==10
			//BeR: removing NCU/DSU down to update it to iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['NCU-ADB'] == 'KO') &&
				(($doc['PING_STATUS']['DSU1'] == 'KO') || ($doc['PING_STATUS']['DSU2'] == 'KO')))			
			{
				failureRecordForWifiEvent("NCU unreachable due to either DSU1 or DSU2 down issue detected", $tempWifiOffArray);
			}
			*/
		}		   
	   
			   $tempAltitudeAbove10KArray['altitude10KEndTime'] = $timeStamp;
			   $tempAltitudeAbove10KArray['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
			   $tempAltitudeAbove10KArray['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];
			  
				// Wifi duration and Percentage Calculation
			   $tempAltitudeAbove10KArray['wifiOnDuration'] = $totalTimeDurationOn;
			   $tempAltitudeAbove10KArray['wifiOffDuration'] = $totalTimeDurationOff;
			   $tempAltitudeAbove10KArray['wifiRestrictedAreaDuration'] = $totalTimeDurationWifiRestrictedArea;
			   $totalWifiDuration = $totalTimeDurationOn + $totalTimeDurationOff - $totalTimeDurationWifiRestrictedArea;
			   
			   
			   if($totalTimeDurationOn != 0)
			   {
				   if($totalTimeDurationOff == 0)
				   {
					   $tempAltitudeAbove10KArray['wifiOnPercentage'] = 100.0;
					   $tempAltitudeAbove10KArray['wifiOffPercentage'] = 0.0;			   
				   }
				   else
				   {
					   $tempAltitudeAbove10KArray['wifiOnPercentage'] = ($totalTimeDurationOn/($totalWifiDuration))*100;
					   $tempAltitudeAbove10KArray['wifiOffPercentage'] = 100.0 - $tempAltitudeAbove10KArray['wifiOnPercentage'];
					   
					  // echo $totalTimeDurationOff . ":" . $totalTimeDurationOn . ":" . $totalTimeDurationWifiRestrictedArea . "<br>";
				   }
			   }
			   elseif($totalTimeDurationOff != 0)
			   {
					$tempAltitudeAbove10KArray['wifiOffPercentage'] = ($totalTimeDurationOff/($totalWifiDuration))*100;;
					$tempAltitudeAbove10KArray['wifiOnPercentage'] = 100.0 - $tempAltitudeAbove10KArray['wifiOffPercentage'];

			   }else 
			   {
				   $tempAltitudeAbove10KArray['wifiOnPercentage'] = -1;
				   $tempAltitudeAbove10KArray['wifiOffPercentage'] = -1;
			   }
			   
			   // OMTS duration and Percentage calculation
			   $tempAltitudeAbove10KArray['omtsOnDuration'] = $totalTimeDurationOmtsOn;
			   $tempAltitudeAbove10KArray['omtsOffDuration'] = $totalTimeDurationOmtsOff;
			   $tempAltitudeAbove10KArray['omtsRestrictedDuration'] = $totalTimeDurationOmtsRestricted;
			   $totalOmtsDuration = $totalTimeDurationOmtsOn + $totalTimeDurationOmtsOff - $totalTimeDurationOmtsRestricted;
			   
			   if($totalTimeDurationOmtsOn != 0)
			   {
				   if($totalTimeDurationOmtsOff == 0)
				   {
					   $tempAltitudeAbove10KArray['omtsOnPercentage'] = 100.0;
					   $tempAltitudeAbove10KArray['omtsOffPercentage'] = 100.0 - $tempAltitudeAbove10KArray['omtsOnPercentage'];			   
				   }
				   else
				   {
					   $tempAltitudeAbove10KArray['omtsOnPercentage'] = ($totalTimeDurationOmtsOn/($totalOmtsDuration))*100;
					   $tempAltitudeAbove10KArray['omtsOffPercentage'] = 100.0 - $tempAltitudeAbove10KArray['omtsOnPercentage'];
				   }
			   }
			   elseif($totalTimeDurationOmtsOff != 0)
			   {
					$tempAltitudeAbove10KArray['omtsOffPercentage'] = ($totalTimeDurationOmtsOff/($totalOmtsDuration))*100;
					$tempAltitudeAbove10KArray['omtsOnPercentage'] = 100.0 - $tempAltitudeAbove10KArray['omtsOffPercentage'];
			   }else
			   {
					 $tempAltitudeAbove10KArray['omtsOnPercentage'] = -1;
					 $tempAltitudeAbove10KArray['omtsOffPercentage'] = -1;
			   }
			   
			   
				//BeR: iteration check to display detected faults
				if($iterationSBB1 >= 4){
					//SDU/DSU-C communication issue
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."SDU/SBB1 down";
					}else{
						$flightFailure = "SDU/SBB1 down";
					}
					//echo "$flightFailure";
					//echo "SDU/SBB1 down issue detected"."<br>";
				}
				
				if($iterationARP >= 4){
					//ARP table overflow issue
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."ARP table overflow";
					}else{
						$flightFailure = "ARP table overflow";
					}
					//echo "$flightFailure";
					//echo "ARP table overflow issue detected"."<br>";
				}
				
				if($iterationsSDUCC1 >= 4){
					//SDU channel card 1 deregistration issue
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."SDU channel card 1 deregistration";
					}else{
						$flightFailure = "SDU channel card 1 deregistration";
					}
				}

				if($iterationsNCU_GisDB >= 4){
					//NCU boot and GisDB issues
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."NCU boot error or GisDB connection loss";
					}else{
						$flightFailure = "NCU boot error or GisDB connection loss";
					}
				}				

				if($iterationsGSMstartupCC1 >= 4){
					//GSM in startup and CC1 ok issue
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."GSM in startup with CC1 ok (ISE-4049)";
					}else{
						$flightFailure = "GSM in startup with CC1 ok (ISE-4049)";
					}
				}

				if($iterationsWiFi_GSM_disconnect >= 4){
					//WiFi and/or GSM inop after short satcom disconnection
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."ISE-3839 WiFi and-or GSM inop after short satcom disconnection";
					}else{
						$flightFailure = "ISE-3839 WiFi and-or GSM inop after short satcom disconnection";
					}
				}

				if($iterations_NCU_DSU_down >= 4){
					//NCU down when either DSU1 or DSU2 is down
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."NCU unreachable due to either DSU1 or DSU2 down";
					}else{
						$flightFailure = "NCU unreachable due to either DSU1 or DSU2 down";
					}
				}

				if($iterationsWiFi_FALSE >= 10){
					//WiFi serviceAvailable only stuck on FALSE
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."WiFi serviceAvailable only stuck on FALSE (ISE-3984)";
					}else{
						$flightFailure = "WiFi serviceAvailable only stuck on FALSE (ISE-3984)";
					}
				}				
				
				if($iterationsAntenna >= 10){
					//antenna issue
					/*
					failureRecordForWifiEvent("BeR update Antenna issue detected", $tempWifiOffArray);
					failureRecordForOmtsEvent("BeR update Antenna issue detected", $tempOmtsOffArray);	
					*/
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."Antenna-BSU issue";
					}else{
						$flightFailure = "Antenna-BSU issue";
					}
					//echo "$flightFailure";
					//echo "ARP table overflow issue detected"."<br>";
				}	
				
				if($totalActiveUser == 0 && $userValue ==1){
				//echo var_dump("here");
			
					$t1 = strtotime($tempAltitudeAbove10KArray['altitude10KStartTime']);
					$t2 = strtotime($tempAltitudeAbove10KArray['altitude10KEndTime']);
					$diff = $t2 - $t1;
					$hours = $diff / ( 60 * 60 );
					//echo "flighthour $hours"."<br>";
						if($hours >= 4)
						{
						if($flightFailure!=""){
							$flightFailure = $flightFailure ."/"."no active user";
						}else{
							$flightFailure = "no active user";
						}
						}
				}
				
				//BeR: display result for restrictedAirspace stuck issue
				if($AirspaceCheck == 'restrictedAirspaceRegion' && $RestrictedAirspace == 1){
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."WiFi stuck on RestrictedAirspace";
					}else{
						$flightFailure = "WiFi stuck on RestrictedAirspace";
					}
				}
				
				if($iterationFlight >= 3){
					//Flight data stuck issue
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."Flight data stuck";
					}else{
						$flightFailure = "Flight data stuck";
					}
				}
				
				if($iterationsOMTS_groundTest >= 4){
					//OMTS grount test active
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."OMTS ground test mode active";
					}else{
						$flightFailure = "OMTS ground test mode active";
					}
				}

				if($iterationsSNMPservice_down >= 4){
					//snmp service down on DSU-C
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."snmp service not starting on DSU-C (ISE-4186)";
					}else{
						$flightFailure = "snmp service not starting on DSU-C (ISE-4186)";
					}
				}	

				if($iterations_SystemMode >= 10){
					//system mode not in service
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."system mode not in service (ISE-4213)";
					}else{
						$flightFailure = "system mode not in service (ISE-4213)";
					}
				}	

				if($iterations_SwitchOff >= 4){
					//OMA switch off issue
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."B787 connectivity power switch off (ISE-3903)";
					}else{
						$flightFailure = "B787 connectivity power switch off (ISE-3903)";
					}
				}

				if($iterations_OMTS_DSU3_down >= 4){
					//OMTS KO due to DSU3 down
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."OMTS down due to DSU3 failure";
					}else{
						$flightFailure = "OMTS down due to DSU3 failure";
					}
				}	

				if($iterations_OMTS_ADBG_down >= 4){
					//OMTS KO due to ADBG down
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."OMTS down due to ADBG failure";
					}else{
						$flightFailure = "OMTS down due to ADBG failure";
					}
				}	

				if($iterations_GSM_MIB_down >= 10){
					//GSM MIB unresponsive
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."GSM MIB unresponsive (ISE-4355)";
					}else{
						$flightFailure = "GSM MIB unresponsive (ISE-4355)";
					}
				}				

				if($iterations_WiFi_autoServiceEnableFalse >= 5){
					//WiFi stuck on auto service enable false
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."WiFi stuck on auto service enable false";
					}else{
						$flightFailure = "WiFi stuck on auto service enable false";
					}
				}

				if($iterations_WiFi_FapDisabled >= 5){
					//WiFi stuck on Fap disabled
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."WiFi stuck on Fap disabled";
					}else{
						$flightFailure = "WiFi stuck on Fap disabled";
					}
				}				
				
				if($iterations_NCU_ADBG_down >= 4){
					//NCU unreachable due to ADB KO
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."NCU unreachable due to ADB KO";
					}else{
						$flightFailure = "NCU unreachable due to ADB KO";
					}
				}		

				if($iterations_NCU_KO >= 4){
					//NCU KO
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."NCU KO";
					}else{
						$flightFailure = "NCU KO";
					}
				}

				if($iterations_DSU_P5 >= 4){
					//DSU-C port P5 issues
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."DSU-C port P5 issue";
					}else{
						$flightFailure = "DSU-C port P5 issue";
					}
				}	

				if($iterations_AVCD >= 4){
					//AVCD KO
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."AVCD KO";
					}else{
						$flightFailure = "AVCD KO";
					}
				}	

				if($iterations_NCU_LedOrErrorMsg >= 4){
					//NCU issues related to LED and errorMsg check (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."NCU LED or error message";
					}else{
						$flightFailure = "NCU LED or error message";
					}
				}	

				if($iterations_ProcessSW >= 4){
					//Connectivity SW processes checks (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."one or more DSU-C connectivity SW module not running";
					}else{
						$flightFailure = "one or more DSU-C connectivity SW module not running";
					}
				}	

				if($iterations_SDU_health >= 4){
					//SDU snmp health error (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."SDU snmp health error";
					}else{
						$flightFailure = "SDU snmp health error";
					}
				}				

				if($iterationsSDUCC1_v2 >= 4){
					//SDU CC1 down (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."SDU channel card 1 down (snmp)";
					}else{
						$flightFailure = "SDU channel card 1 down (snmp)";
					}
				}
				
				if($iterationsSDUCC2_v2 >= 4){
					//SDU CC2 down (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."SDU channel card 2 down (snmp)";
					}else{
						$flightFailure = "SDU channel card 2 down (snmp)";
					}
				}
				
				if($iterationsSDU_Ant_bus >= 4){
					//SDU-Antenna bus down (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."SDU-antenna bus down (snmp)";
					}else{
						$flightFailure = "SDU-antenna bus down (snmp)";
					}
				}				

				if($iterations_DLNA >= 4){
					//DLNA down (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."DLNA down (snmp)";
					}else{
						$flightFailure = "DLNA down (snmp)";
					}
				}	
				
				if($iterationsAntenna_v2 >= 4){
					//Antenna down (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."Antenna down (snmp)";
					}else{
						$flightFailure = "Antenna down (snmp)";
					}
				}				

				if($iterations_nslookup >= 4){
					//nslookup unsuccessful (conn log v2)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."nslookup unsuccessful";
					}else{
						$flightFailure = "nslookup unsuccessful";
					}
				}

				if($iterations_groundFailure >= 4){
					//ground failure check
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."ground failure";
					}else{
						$flightFailure = "ground failure";
					}
				}

				if($iterations_WiFi_CommFailure >= 5){
					//WiFi only down - commFailure (ISE-4422)
					if($flightFailure!=""){
						$flightFailure = $flightFailure ."/"."WiFi only down commFailure (ISE-4422)";
					}else{
						$flightFailure = "WiFi only down commFailure (ISE-4422)";
					}
				}
				
			   // *************************  START - Create the data structure need to write in MONGODB ********************************* //
			   
			   $idFlightLeg = readFlightLegForAltitudeEvent($dbConnection, 
															$dbName, 
															$tempAltitudeAbove10KArray['altitude10KStartTime']);
			   
			   $altitudeEventArray = array(	"startTime" 		=> $tempAltitudeAbove10KArray['altitude10KStartTime'],
											"endTime" 			=> $tempAltitudeAbove10KArray['altitude10KEndTime'],
											"location"		=> array(	"type" => "LineString", 
																		"coordinates" => array(
																		array($tempAltitudeAbove10KArray['startLongitude'],$tempAltitudeAbove10KArray['startLatitude']),
																		array($tempAltitudeAbove10KArray['endLongitude'],$tempAltitudeAbove10KArray['endLatitude'])
																		))
											);
											

				$wifiAvailabilityArray = array(	"totalOnDuration" 		=> $tempAltitudeAbove10KArray['wifiOnDuration'],
												"totalOffDuration" 		=> $tempAltitudeAbove10KArray['wifiOffDuration'],
												"totalOnPercentage"		=> $tempAltitudeAbove10KArray['wifiOnPercentage'],
												"totalOffPercentage"	=> $tempAltitudeAbove10KArray['wifiOffPercentage'],
												"manualPercentageOn"	=> "",
												"manualPercentageOff"	=> "");


				$omtsAvailabilityArray = array(	"totalOnDuration" 		=> $tempAltitudeAbove10KArray['omtsOnDuration'],
												"totalOffDuration" 		=> $tempAltitudeAbove10KArray['omtsOffDuration'],
												"totalOnPercentage"		=> $tempAltitudeAbove10KArray['omtsOnPercentage'],
												"totalOffPercentage"	=> $tempAltitudeAbove10KArray['omtsOffPercentage'],
												"manualPercentageOn"	=> "",
												"manualPercentageOff"	=> "");
												
				if($idFlightLeg !=""){
					 $finalFlightElement = array(	"tailSign"  	=> $tempAltitudeAbove10KArray['tailSign'],
											"idFlightLeg"	=> $idFlightLeg,
											"startTime" 	=> $tempAltitudeAbove10KArray['altitude10KStartTime'],
											"endTime" 		=> $tempAltitudeAbove10KArray['altitude10KEndTime'],
											"cityPair"		=> $doc['TGS_FLIGHT']['cityPair'],
											"flightNumber"	=> $doc['TGS_FLIGHT']['flightNumber'],
											/*"tracjectory"	=> array(	"type" => "LineString", 
																		"coordinates" => $trajectoryArray,
																		"properties" => array("tracjectoryTimeStamp" => $trajectoryTimeStampArray)),*/
											"flightFailure" =>$flightFailure,
											"altitudeEvent"	=> $altitudeEventArray,
											"wifiAvailabilityEvents"	=> $wifiAvailabilityEventsArray,
											"wifiAvailability"			=> $wifiAvailabilityArray,
											"omtsAvailabilityEvents"	=> $omtsAvailabilityEventsArray,
											"omtsAvailability"			=> $omtsAvailabilityArray
											);
				}else{
					 $finalFlightElement = array(	"tailSign"  	=> $tempAltitudeAbove10KArray['tailSign'],
											"idFlightLeg"	=> '',
											"startTime" 	=> $tempAltitudeAbove10KArray['altitude10KStartTime'],
											"endTime" 		=> $tempAltitudeAbove10KArray['altitude10KEndTime'],
											"cityPair"		=> $doc['TGS_FLIGHT']['cityPair'],
											"flightNumber"	=> $doc['TGS_FLIGHT']['flightNumber'],
											/*"tracjectory"	=> array(	"type" => "LineString", 
																		"coordinates" => $trajectoryArray,
																		"properties" => array("tracjectoryTimeStamp" => $trajectoryTimeStampArray)),*/
											"flightFailure" =>$flightFailure,
											"altitudeEvent"	=> $altitudeEventArray,
											"wifiAvailabilityEvents"	=> $wifiAvailabilityEventsArray,
											"wifiAvailability"			=> $wifiAvailabilityArray,
											"omtsAvailabilityEvents"	=> $omtsAvailabilityEventsArray,
											"omtsAvailability"			=> $omtsAvailabilityArray
											);
				}
												
			  
			   
			   
			   // ********************** End - of data structure creation ***************************** //
			   
			   
			   
			   
			   array_push($altitudeAbove10KArray,$tempAltitudeAbove10KArray,$wifiOffArray,$wifiOnArray);
			   
			  // if( $idFlightLeg != "") {
					//Write data into mongodb for a Set of data
					/*$res = writeIntoConnectivityEventCollection(	$finalFlightElement,
															$idFlightLeg,
															$collection);	
															*/
					$testTimeStamp = $doc['timestamp'];
					
					//echo var_dump($finalFlightElement).'<br>';
					//echo var_dump($idFlightLeg).'<br>';
					//echo var_dump($testTimeStamp).'<br>';
					//echo var_dump($collection).'<br>';
					
					$res = writeIntoConnectivityEventCollection(	$finalFlightElement, $idFlightLeg, $testTimeStamp, $collection);
			   
					//write connectivity status into mysql for a flightleg
					/*writeConnectivityStatusForFlightLegToMysqlDb(	$dbConnection, 
																	$dbName, 
																	$idFlightLeg,
																	$tempAltitudeAbove10KArray['wifiOnPercentage'],
																	$tempAltitudeAbove10KArray['omtsOnPercentage']);*/
																	
					echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ok.png\" style=\"vertical-align:top\">&nbsp;&nbsp;Data has been stored.<br>";
				/*}
				else
				{
					$testTimeStamp = $doc['timestamp'];
					$res = writeIntoConnectivityEventCollection(	$finalFlightElement, $testTimeStamp, $collection);
					echo "<br><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;Flight Leg is not found. Connectivity Events have not been created.<br>";
				}*/
			   
			   unset($tempAltitudeAbove10KArray);
			   unset($wifiOffArray);
			   unset($wifiOnArray);
			  // unset($trajectoryArray);
			   unset($trajectoryTimeStampArray);
			   unset($altitudeEventArray);
			   unset($wifiAvailabilityEventsArray);
			   unset($wifiAvailabilityArray);
			   unset($omtsAvailabilityEventsArray);
			   unset($altitudeAbove10KArray);
			   $omtsAvailabilityEventsArray = array();
			   $wifiAvailabilityArray = array();
			   $wifiAvailabilityEventsArray = array();
			   $altitudeEventArray = array();
			  // $trajectoryArray = array();
			   $trajectoryTimeStampArray = array();
			   $tempAltitudeAbove10KArray = array();
			   $altitudeAbove10KArray = array();
			   $wifiOnArray = array();
			   $wifiOffArray = array();
			   
			   
				echo "<hr>";
				echo "10K FT Stop Time is $timeStamp and Total Wifi ON Time $totalTimeDurationOn and and Total Wifi OFF Time $totalTimeDurationOff<br><br<br>";
				//BeR: resetting monitoring variables
				$isAltitudeRecordingOn = '0';
				$totalTimeDurationOn = 0;
				$totalTimeDurationOff = 0;
				$totalTimeDurationWifiRestrictedArea = 0;
				$totalTimeDurationOmtsOn = 0;
				$totalTimeDurationOmtsOff = 0;
				$totalTimeDurationOmtsRestricted = 0;
				$recordClimbTimeAsStartTime = '0';
				$recordDecentTimeAsEndTime = '0';
				$iterationSBB1 = 0;
				$iterationARP = 0;
				$totalActiveUser =0;
				$userValue =0;
				$flightFailure ='';
				$iterationFlight = 0;
				$prevLatitude = 'stuck';
				$prevLongitude = 'stuck';
				$iterationsSDUCC1 = 0;
				$iterationsAntenna = 0;
				$iterationsNCU_GisDB = 0;
				$iterationsGSMstartupCC1 = 0;
				$iterationsWiFi_GSM_disconnect = 0;
				$iterations_NCU_DSU_down = 0;
				$iterationsWiFi_FALSE = 0;
				$AirspaceCheck = 'restrictedAirspaceRegion';
				$RestrictedAirspace = 0;
				$iterationsOMTS_groundTest = 0;
				$iterationsSNMPservice_down =0;
				$iterations_SystemMode = 0;
				$iterations_SwitchOff = 0;
				$iterations_OMTS_DSU3_down = 0;
				$iterations_OMTS_ADBG_down = 0;
				$iterations_GSM_MIB_down = 0;
				$iterations_WiFi_autoServiceEnableFalse = 0;
				$iterations_WiFi_FapDisabled = 0;
				$iterations_NCU_ADBG_down = 0;
				$iterations_NCU_KO = 0;
				$iterations_DSU_P5 = 0;
				$iterations_AVCD = 0;
				$iterations_NCU_LedOrErrorMsg = 0;
				$iterations_ProcessSW = 0;
				$iterations_SDU_health = 0;
				$iterationsSDUCC1_v2 = 0;
				$iterationsSDUCC2_v2 = 0;
				$iterationsSDU_Ant_bus = 0;
				$iterations_DLNA = 0;
				$iterationsAntenna_v2 = 0;
				$iterations_nslookup = 0;
				$iterations_groundFailure = 0;
				$iterations_WiFi_CommFailure = 0;

				//echo "Final $iterationSBB1"."<br>";
				//echo "finalARP $iterationARP"."<br>";
		}
	}
	elseif (($altitudeValue >= $altThreshold) && 
			($isAltitudeRecordingOn == '1')) 
	{
		// ***************** MAIN CASE 2: Altitude is above 10K FT and Altitude Recording is in ON start ************** //
		
		if(!is_array($tempOmtsOffArray))
		{
			$tempOmtsOffArray = array();
			$tempOmtsOffArray['computedFailure'] = "Unknown";
		}
		if(!is_array($tempWifiOffArray))
		{
			$tempWifiOffArray = array();
			$tempWifiOffArray['computedFailure'] = "Unknown";
		}
			
		   
		//Recording Wifi Restricted Area between 10K to 10K FT
		if(	($doc['TGS_ONAIR']['serviceAvailableDetail'] == "restrictedAirspaceRegion") && 
			($isWifiRestrictedAreaRecording == '0'))
		{

			wifiRestrictedAreaStartTime($doc['timestamp'],
										$doc['TGS_FLIGHT']['latitude'],
										$doc['TGS_FLIGHT']['longitude'],
										$tempWifiRestrictedAreaArray,
										$tempWifiRestrictedAreaArrayCoordinates);
										
			// ***** Create another event for Wifi Off when detected a Restricted Area ******* //
			//switch off recording of Wifi-Off if already started
			if($isWifiOffTimeRecording == '1'){
				$wifiStatusOffTimeEnd10K = $doc['timestamp'];
				$tempWifiOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];
				$tempWifiOffArray['endTime'] = $doc['timestamp'];
				$tempWifiOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempWifiOffArray['computedFailure'])));
				$tempWifiOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempWifiOffArrayCoordinates['startLongitude'],$tempWifiOffArrayCoordinates['startLatitude']),
															array($tempWifiOffArrayCoordinates['endLongitude'],$tempWifiOffArrayCoordinates['endLatitude'])
													));			
				array_push($wifiAvailabilityEventsArray,$tempWifiOffArray);
				unset($tempWifiOffArray);
				unset($tempWifiOffArrayCoordinates);
				$tempWifiOffArrayCoordinates = array();
				$tempWifiOffArray = array();
				
				$timeFirst  = strtotime($wifiStatusOffTimeStart10K);
				$timeSecond = strtotime($wifiStatusOffTimeEnd10K);
				$timeDurationOff = $timeSecond - $timeFirst;
				$totalTimeDurationOff = $totalTimeDurationOff + $timeDurationOff;		
				
				$isWifiOffTimeRecording = '0';	   
				
				// important debug echo
				// echo "Altitude below 10KFT Wifi is OFF and Its END Time is $wifiStatusOffTimeEnd10K and Duration Off Time $timeDurationOff <br>";
			}	
			// Now Start recording of WIFI-Off as another Event
			if($isWifiOffTimeRecording == '0'){
				$wifiStatusOffTimeStart10K = $doc['timestamp'];
				$tempWifiOffArray['description'] = "WIFI-OFF";
				$tempWifiOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];
				$tempWifiOffArray['startTime'] = $doc['timestamp'];
				$tempWifiOffArray['computedFailure'] = "Restricted Area ";
				$tempWifiOffArray['manualFailureEntry'] = "";			
				$isWifiOffTimeRecording = '1';	 
				// important debug echo
				// echo "<br>Altitude Above 10KFT Wifi is OFF and Its START Time is $wifiStatusOffTimeStart10K<br>";
			}
			// ***** End of Create another event for Wifi Off ******* //
			
			if($isWifiOnTimeRecording == '1'){
				$wifiStatusOnTimeEnd10K = $doc['timestamp'];
				$tempWifiOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	 			
				$tempWifiOnArray['endTime'] = $doc['timestamp'];
				$tempWifiOnArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempWifiOnArrayCoordinates['startLongitude'],$tempWifiOnArrayCoordinates['startLatitude']),
															array($tempWifiOnArrayCoordinates['endLongitude'],$tempWifiOnArrayCoordinates['endLatitude'])
													));			
				array_push(	$wifiAvailabilityEventsArray,
							$tempWifiOnArray);
				unset($tempWifiOnArray);
				unset($tempWifiOnArrayCoordinates);
				$tempWifiOnArrayCoordinates = array();
				$tempWifiOnArray = array();
				
				$timeFirst  = strtotime($wifiStatusOnTimeStart10K);
				$timeSecond = strtotime($wifiStatusOnTimeEnd10K);
				$timeDurationOn = $timeSecond - $timeFirst;
				$totalTimeDurationOn = $totalTimeDurationOn + $timeDurationOn;
				
				$isWifiOnTimeRecording = '0';	  
				// important debug echo
				//echo "Wifi is ON and Its END Time is $wifiStatusOnTimeEnd10K and Duration On Time $timeDurationOn<br>";	   
			}

			
			$isWifiRestrictedAreaRecording = '1';
		}		
		elseif(	($doc['TGS_ONAIR']['serviceAvailableDetail'] != "restrictedAirspaceRegion") && 
				($isWifiRestrictedAreaRecording == '1'))
		{
	   
			wifiRestrictedAreaEndTime(	$doc['timestamp'],
										$doc['TGS_FLIGHT']['latitude'],
										$doc['TGS_FLIGHT']['longitude'],
										$tempWifiRestrictedAreaArray,
										$tempWifiRestrictedAreaArrayCoordinates,
										$totalTimeDurationWifiRestrictedArea);
										
			array_push($wifiAvailabilityEventsArray,$tempWifiRestrictedAreaArray);		
			unset($tempWifiRestrictedAreaArray);				
			$tempWifiRestrictedAreaArray = array();	
			
			// ***** Create another event for Wifi Off ******* //			
			if($isWifiOffTimeRecording == '1'){
				$wifiStatusOffTimeEnd10K = $doc['timestamp'];
				$tempWifiOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];
				$tempWifiOffArray['endTime'] = $doc['timestamp'];
				$tempWifiOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempWifiOffArray['computedFailure'])));
				$tempWifiOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempWifiOffArrayCoordinates['startLongitude'],$tempWifiOffArrayCoordinates['startLatitude']),
															array($tempWifiOffArrayCoordinates['endLongitude'],$tempWifiOffArrayCoordinates['endLatitude'])
													));				
				array_push($wifiAvailabilityEventsArray,$tempWifiOffArray);
				unset($tempWifiOffArray);
				unset($tempWifiOffArrayCoordinates);
				$tempWifiOffArrayCoordinates = array();
				$tempWifiOffArray = array();
				
				$timeFirst  = strtotime($wifiStatusOffTimeStart10K);
				$timeSecond = strtotime($wifiStatusOffTimeEnd10K);
				$timeDurationOff = $timeSecond - $timeFirst;
				$totalTimeDurationOff = $totalTimeDurationOff + $timeDurationOff;		
				
				$isWifiOffTimeRecording = '0';	   
				// important debug echo
				//echo "Altitude below 10KFT Wifi is OFF and Its END Time is $wifiStatusOffTimeEnd10K and Duration Off Time $timeDurationOff <br>";
			}
								
			$isWifiRestrictedAreaRecording = '0';
		}
		else
		{
			//do nothing
		}
		
		
		//Check is it a OMTS Restricted Area	
	   if(	($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == "normal") && 
			($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == "false") && 
			($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication'] == "false") &&
			($isOmtsRestrictTimeRecording == '0'))  // For OMTS Restricted and Off State
	   {

			//echo "OMTS RESTRICTED Area START Time is " . $doc['timestamp'] . "<br>";
			$tempOmtsRestrictedArray['description'] = "OMTS-RESTICTED";
			$tempOmtsRestrictedArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
			$tempOmtsRestrictedArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
			$tempOmtsRestrictedArray['startTime'] = $doc['timestamp'];										
			
			// If OMTS OFF is already in Recording state, Switch OFF the event
			if($isOmtsOffTimeRecording == '1')
			{
				//echo "OMTS OFF END Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['endTime'] = $doc['timestamp'];
				$tempOmtsOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempOmtsOffArray['computedFailure'])));				
				$tempOmtsOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsOffArrayCoordinates['startLongitude'],$tempOmtsOffArrayCoordinates['startLatitude']),
															array($tempOmtsOffArrayCoordinates['endLongitude'],$tempOmtsOffArrayCoordinates['endLatitude'])
													));	

				$timeFirstOmts  = strtotime($tempOmtsOffArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsOffArray['endTime']);
				$timeDurationOmtsOff = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsOff = $totalTimeDurationOmtsOff + $timeDurationOmtsOff;
													
				array_push($omtsAvailabilityEventsArray,$tempOmtsOffArray);		
				unset($tempOmtsOffArray);				
				$tempOmtsOffArray = array();						
				$isOmtsOffTimeRecording = '0';
			}
			//Create new OMTS OFF state event
			if($isOmtsOffTimeRecording == '0')
			{
				//echo "OMTS OFF START Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOffArray['description'] = "OMTS-OFF";
				$tempOmtsOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['startTime'] = $doc['timestamp'];		
				$tempOmtsOffArray['computedFailure'] = "Restricted Area ";
				$tempOmtsOffArray['manualFailureEntry'] = "";				
				$isOmtsOffTimeRecording = '1';
			}
						
			if($isOmtsOnTimeRecording == '1')
			{
				//echo "OMTS ON END Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOnArray['endTime'] = $doc['timestamp'];	
				$tempOmtsOnArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsOnArrayCoordinates['startLongitude'],$tempOmtsOnArrayCoordinates['startLatitude']),
															array($tempOmtsOnArrayCoordinates['endLongitude'],$tempOmtsOnArrayCoordinates['endLatitude'])
													));	

				$timeFirstOmts  = strtotime($tempOmtsOnArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsOnArray['endTime']);
				$timeDurationOmtsOn = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsOn = $totalTimeDurationOmtsOn + $timeDurationOmtsOn;
				
				array_push($omtsAvailabilityEventsArray,$tempOmtsOnArray);		
				unset($tempOmtsOnArray);					
				$tempOmtsOnArray = array();					
				$isOmtsOnTimeRecording = '0';
			}
			
			$isOmtsRestrictTimeRecording = '1';
			
	   }
	   
	   // Check OMTS is ON State
	   if($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == "true") // For OMTS On State
	   {
			if($isOmtsOffTimeRecording == '1')
			{
				//echo "OMTS OFF END Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['endTime'] = $doc['timestamp'];
				$tempOmtsOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempOmtsOffArray['computedFailure'])));				
				$tempOmtsOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsOffArrayCoordinates['startLongitude'],$tempOmtsOffArrayCoordinates['startLatitude']),
															array($tempOmtsOffArrayCoordinates['endLongitude'],$tempOmtsOffArrayCoordinates['endLatitude'])
													));	

				$timeFirstOmts  = strtotime($tempOmtsOffArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsOffArray['endTime']);
				$timeDurationOmtsOff = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsOff = $totalTimeDurationOmtsOff + $timeDurationOmtsOff;
													
				array_push($omtsAvailabilityEventsArray,$tempOmtsOffArray);		
				unset($tempOmtsOffArray);				
				$tempOmtsOffArray = array();						
				$isOmtsOffTimeRecording = '0';
			}
			
			if($isOmtsRestrictTimeRecording == '1')
			{
				//echo "OMTS RESTRICTED Area END Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsRestrictedArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsRestrictedArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsRestrictedArray['endTime'] = $doc['timestamp'];					
				$tempOmtsRestrictedArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsRestrictedArrayCoordinates['startLongitude'],$tempOmtsRestrictedArrayCoordinates['startLatitude']),
															array($tempOmtsRestrictedArrayCoordinates['endLongitude'],$tempOmtsRestrictedArrayCoordinates['endLatitude'])
													));	

				$timeFirstOmts  = strtotime($tempOmtsRestrictedArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsRestrictedArray['endTime']);
				$timeDurationOmtsRestricted = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsRestricted = $totalTimeDurationOmtsRestricted + $timeDurationOmtsRestricted;
				
				array_push($omtsAvailabilityEventsArray,$tempOmtsRestrictedArray);		
				unset($tempOmtsRestrictedArray);				
				$tempOmtsRestrictedArray = array();				
				$isOmtsRestrictTimeRecording = '0';
			}
			
			if($isOmtsOnTimeRecording == '0')
			{
				//echo "OMTS ON START Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOnArray['description'] = "OMTS-ON";
				$tempOmtsOnArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOnArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOnArray['startTime'] = $doc['timestamp'];									
				$isOmtsOnTimeRecording = '1';
			}
			
	   }	   
		//Check for OMTS is OFF State
		if(	($doc['TGS_ONAIR']['gsmConnexTsSystemState'] != 'normal') && 
			($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == 'false')) // For OMTS Off State
	    {			
			if($isOmtsOffTimeRecording == '0')
			{
				//echo "OMTS OFF START Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOffArray['description'] = "OMTS-OFF";
				$tempOmtsOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOffArray['startTime'] = $doc['timestamp'];	
				$tempOmtsOffArray['computedFailure'] = "Unknown";
				$tempOmtsOffArray['manualFailureEntry'] = "";				
				$isOmtsOffTimeRecording = '1';
			}
			
			if($isOmtsRestrictTimeRecording == '1')
			{
				//echo "OMTS RESTRICTED Area END Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsRestrictedArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsRestrictedArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsRestrictedArray['endTime'] = $doc['timestamp'];	
				$tempOmtsRestrictedArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsRestrictedArrayCoordinates['startLongitude'],$tempOmtsRestrictedArrayCoordinates['startLatitude']),
															array($tempOmtsRestrictedArrayCoordinates['endLongitude'],$tempOmtsRestrictedArrayCoordinates['endLatitude'])
													));
													
				$timeFirstOmts  = strtotime($tempOmtsRestrictedArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsRestrictedArray['endTime']);
				$timeDurationOmtsRestricted = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsRestricted = $totalTimeDurationOmtsRestricted + $timeDurationOmtsRestricted;
				
				array_push($omtsAvailabilityEventsArray,$tempOmtsRestrictedArray);		
				unset($tempOmtsRestrictedArray);				
				$tempOmtsRestrictedArray = array();				
				$isOmtsRestrictTimeRecording = '0';
			}
			
			if($isOmtsOnTimeRecording == '1')
			{
				//echo "OMTS ON END Time is " . $doc['timestamp'] . "<br>";
				$tempOmtsOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempOmtsOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	   
				$tempOmtsOnArray['endTime'] = $doc['timestamp'];			
				$tempOmtsOnArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempOmtsOnArrayCoordinates['startLongitude'],$tempOmtsOnArrayCoordinates['startLatitude']),
															array($tempOmtsOnArrayCoordinates['endLongitude'],$tempOmtsOnArrayCoordinates['endLatitude'])
													));	

				$timeFirstOmts  = strtotime($tempOmtsOnArray['startTime']);
				$timeSecondOmts = strtotime($tempOmtsOnArray['endTime']);
				$timeDurationOmtsOn = $timeSecondOmts - $timeFirstOmts;
				$totalTimeDurationOmtsOn = $totalTimeDurationOmtsOn + $timeDurationOmtsOn;
				
				array_push($omtsAvailabilityEventsArray,$tempOmtsOnArray);		
				unset($tempOmtsOnArray);				
				$tempOmtsOnArray = array();
				$isOmtsOnTimeRecording = '0';
			}
			
	    }
	   
	
	
	
		//Check WIFI is OFF State
	   if(	($doc['TGS_ONAIR']['serviceAvailable'] == 'false') && ($doc['TGS_ONAIR']['serviceAvailableDetail'] != "restrictedAirspaceRegion") ) 
	   {
			if($isWifiOnTimeRecording == '1'){
				$wifiStatusOnTimeEnd10K = $doc['timestamp'];
				$tempWifiOnArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOnArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];	 			
				$tempWifiOnArray['endTime'] = $doc['timestamp'];
				$tempWifiOnArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempWifiOnArrayCoordinates['startLongitude'],$tempWifiOnArrayCoordinates['startLatitude']),
															array($tempWifiOnArrayCoordinates['endLongitude'],$tempWifiOnArrayCoordinates['endLatitude'])
													));			
				array_push(	$wifiAvailabilityEventsArray,
							$tempWifiOnArray);
				unset($tempWifiOnArray);
				unset($tempWifiOnArrayCoordinates);
				$tempWifiOnArrayCoordinates = array();
				$tempWifiOnArray = array();
				
				$timeFirst  = strtotime($wifiStatusOnTimeStart10K);
				$timeSecond = strtotime($wifiStatusOnTimeEnd10K);
				$timeDurationOn = $timeSecond - $timeFirst;
				$totalTimeDurationOn = $totalTimeDurationOn + $timeDurationOn;
				
				$isWifiOnTimeRecording = '0';	  
				// important debug echo
				//echo "Wifi is ON and Its END Time is $wifiStatusOnTimeEnd10K and Duration On Time $timeDurationOn<br>";	   
			}
		   if(($isWifiOffTimeRecording == '0'))
		   {
				$wifiStatusOffTimeStart10K = $doc['timestamp'];
				$tempWifiOffArray['description'] = "WIFI-OFF";
				$tempWifiOffArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOffArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];
				$tempWifiOffArray['startTime'] = $doc['timestamp'];
				$tempWifiOffArray['computedFailure'] = "Unknown";				
				$tempWifiOffArray['manualFailureEntry'] = "";					
				$isWifiOffTimeRecording = '1';	
				// important debug echo
				//echo "Wifi is OFF and Its START Time is $wifiStatusOffTimeStart10K<br>";				
		   }
	   
	   }
	   elseif(	($doc['TGS_ONAIR']['serviceAvailable'] == 'true') ) 
				
	   {
			if($isWifiOffTimeRecording == '1'){
				$wifiStatusOffTimeEnd10K = $doc['timestamp'];
				$tempWifiOffArrayCoordinates['endLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOffArrayCoordinates['endLongitude'] = $doc['TGS_FLIGHT']['longitude'];			
				$tempWifiOffArray['endTime'] = $doc['timestamp'];
				$tempWifiOffArray['computedFailure'] = implode(' ',array_unique(explode(':', $tempWifiOffArray['computedFailure'])));
				$tempWifiOffArray['location'] =  array(	"type" => "LineString", 
														"coordinates" => array(
															array($tempWifiOffArrayCoordinates['startLongitude'],$tempWifiOffArrayCoordinates['startLatitude']),
															array($tempWifiOffArrayCoordinates['endLongitude'],$tempWifiOffArrayCoordinates['endLatitude'])
													));	
				
				array_push($wifiAvailabilityEventsArray,$tempWifiOffArray);
				unset($tempWifiOffArray);
				unset($tempWifiOffArrayCoordinates);
				$tempWifiOffArray = array();
				$tempWifiOffArrayCoordinates = array();
				$timeFirst  = strtotime($wifiStatusOffTimeStart10K);
				$timeSecond = strtotime($wifiStatusOffTimeEnd10K);
				$timeDurationOff = $timeSecond - $timeFirst;
				
				$totalTimeDurationOff = $totalTimeDurationOff + $timeDurationOff;
				
				$isWifiOffTimeRecording = '0';	
				// important debug echo
				//echo "Wifi is OFF and Its END Time is $wifiStatusOffTimeEnd10K and Duration Off Time $timeDurationOff<br>";	   
			}
			
		   if(($isWifiOnTimeRecording == '0'))
		   {
				$wifiStatusOnTimeStart10K = $doc['timestamp'];
				$tempWifiOnArray['description'] = "WIFI-ON";
				$tempWifiOnArrayCoordinates['startLatitude'] = $doc['TGS_FLIGHT']['latitude'];
				$tempWifiOnArrayCoordinates['startLongitude'] = $doc['TGS_FLIGHT']['longitude'];
				$tempWifiOnArray['startTime'] = $doc['timestamp'];
				$isWifiOnTimeRecording = '1';
				// important debug echo
				//echo "Wifi is ON and Its START Time is $wifiStatusOnTimeStart10K<br>";				
		   }	   
	   }
	   else
	   {
	   //do nothing
	   }


		//SB:Iterations for SBB-1
		if($iterationSBB1<4){
			if($doc['PING_STATUS']['SBB-1'] == 'KO'){
				$iterationSBB1 = $iterationSBB1+1;
			}else{
				$iterationSBB1 = 0;
			}
		}

	   //ARP table overflow issue
	   if($iterationARP<4){
		   if(($doc['PING_STATUS']['BTS1'] == 'KO') &&($doc['PING_STATUS']['BTS2'] == 'KO') &&($doc['PING_STATUS']['NCU'] == 'OK') &&($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&($doc['PING_STATUS']['DSU2'] == 'OK') &&($doc['PING_STATUS']['BTS1-CS'] == 'OK') &&($doc['PING_STATUS']['BTS2-CS'] == 'OK') &&($doc['PING_STATUS']['BTS1-ADB'] == 'OK') &&($doc['PING_STATUS']['BTS2-ADB'] == 'OK')){
				$iterationARP = $iterationARP+1;
		   }else{
				$iterationARP = 0;
		   }
	    }

		
		//BeR: Antenna/BSU issue for conn tool log v1
		//checking condition from conn tool log v2 and discard this test if v2 (additional accurate filter with v2 for antenna and sdu and channel cards failures)
		if (!isset($doc['P5_PORT_STATUS']['speed'])){
			if($iterationsAntenna<10){
				if((($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') ||($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error')) &&
						($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
						($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
						($doc['PING_STATUS']['SBB-1'] == 'OK') &&
						($doc['TGS_ONAIR']['serviceAvailableDetail']!='restrictedAirspaceRegion') &&
						($doc['TGS_ONAIR']['serviceAvailable'] == 'false')){
							$iterationsAntenna = $iterationsAntenna+1;
				}else{
					$iterationsAntenna = 0;
					}	
			}
		}		
		
		
		//no active user issue
		if($totalActiveUser == 0){
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') && ($doc['TGS_ONAIR']['applicationAvailable'] == 'true') && ($doc['TGS_ONAIR']['currentActiveUsers'] == 0)){
					$userValue = 1;
			}else{
				$totalActiveUser = $doc['TGS_ONAIR']['currentActiveUsers'];
			}
		}
		
		//BeR: stuck on restrictedAirspace issue. Failure is considered only if all CRUISE iteration are in restrictedAirspaceRegion
		if($AirspaceCheck == 'restrictedAirspaceRegion'){
			if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'restrictedAirspaceRegion'){
					$RestrictedAirspace = 1;
			}else{
				$AirspaceCheck = $doc['TGS_ONAIR']['serviceAvailableDetail'];
			}
		}		
		
		//Flight data stuck issue
		/*BeR:
		Flight data stuck issue updated:
		for some reason, the filter does not go through instances where altitude = -1000 (something to be debugged maybe later)
		in some cases of flight data stuck, ASC shared memory can be empty
		hence the filter I updated is only checking for latitude and longitude stuck. In that way with consecutive check lower to 3 it works ok (considering we are browsing climb-cruise-descent event, it is abnormal to have 3 successive iteration with a same altitude and longitude
		*/
		if($iterationFlight<3){
			if($prevLatitude != 'stuck' && $prevLongitude != 'stuck'){
				if((($doc['THALES_FLIGHT']['asdSbbLatitude'] == $prevLatitude)||($doc['THALES_FLIGHT']['asdSbbLongitude'] == $prevLongitude))){
					$iterationFlight = $iterationFlight+1;
					$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
					$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
				}else{
					$iterationFlight = 0;
					$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
					$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
				}
			}else{
				//check for first iteration
				$prevLatitude = $doc['THALES_FLIGHT']['asdSbbLatitude'];
				$prevLongitude = $doc['THALES_FLIGHT']['asdSbbLongitude'];
			}
		}
		
		
		//BeR: SDU channel card 1 deregistration for conn tool log v1
		//checking condition from conn tool log v2 and discard this test if v2 (new CC test available via snmp variable in v2 is more reliable)
		if (!isset($doc['SDU_STATUS']['asuSduInfoCC1Status'])){
			if ($iterationsSDUCC1<4){
				if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&
					($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
					($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
					($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'startup')){
						$iterationsSDUCC1 = $iterationsSDUCC1+1;
				}else{
					$iterationsSDUCC1 = 0;
					}
			}
		}
		
		
		//BeR: NCU boot and GisDB issues
		if ($iterationsNCU_GisDB<4){
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'OK') &&
				($doc['PING_STATUS']['BTS1'] == 'OK') && 
				($doc['PING_STATUS']['BTS2'] == 'OK')){
					$iterationsNCU_GisDB = $iterationsNCU_GisDB+1;
			}else{
				$iterationsNCU_GisDB = 0;
			}			
		}
		
		//BeR: GSM stuck in startup and CC1 OK
		if ($iterationsGSMstartupCC1<4){
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0)){
					$iterationsGSMstartupCC1 = $iterationsGSMstartupCC1+1;
			}else{
				$iterationsGSMstartupCC1 = 0;
			}			
		}
		
		//BeR: WiFi and/or GSM inop after short satcom short disconnection
		//14Sep17: added BTS1 and BTS2 check to avoid conflict with ARP overflow issue
		//22Sep17: removing the filter. Despite adjustements, it is often triggered for incorrect reason.
		/*
		if ($iterationsWiFi_GSM_disconnect<10){
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'OK') &&
				($doc['PING_STATUS']['CWLU1'] == 'OK') &&
				($doc['PING_STATUS']['BTS1'] == 'OK') &&
				($doc['PING_STATUS']['BTS2'] == 'OK') &&
				((($doc['TGS_ONAIR']['applicationAvailable'] == 'false') && ($doc['TGS_ONAIR']['serviceAvailable'] == 'false') && ($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') && ($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable')) || ((($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error') || ($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup')) && ($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='false')))){
					$iterationsWiFi_GSM_disconnect = $iterationsWiFi_GSM_disconnect+1;
				}else{
					$iterationsWiFi_GSM_disconnect = 0;
				}	
		}
		*/
		
		//BeR: NCU unreachable if DSU1 or DSU2 is down
		if ($iterations_NCU_DSU_down<4){			
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['NCU-ADB'] == 'KO') &&
				($doc['TGS_ONAIR']['serviceAvailable'] == 'true') &&	
				(($doc['PING_STATUS']['DSU1'] == 'KO') || ($doc['PING_STATUS']['DSU2'] == 'KO'))){
					$iterations_NCU_DSU_down = $iterations_NCU_DSU_down+1;
				}else{
					$iterations_NCU_DSU_down = 0;					
				}					
		}
		
		//BeR: WiFi serviceAvailable only stuck on FALSE
		if ($iterationsWiFi_FALSE<10){	
			if(($doc['TGS_ONAIR']['serviceAvailable'] == 'false') &&		
				($doc['TGS_ONAIR']['applicationAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'noFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 10) &&
				($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0)){
					$iterationsWiFi_FALSE = $iterationsWiFi_FALSE+1;
				}else{
					$iterationsWiFi_FALSE = 0;					
				}	
		}

		//BeR: OMTS ground test mode active
		if ($iterationsOMTS_groundTest<4){
			if($doc['TGS_ONAIR']['inputBtsOverruleAllowIsOn'] == 'true'){
					$iterationsOMTS_groundTest = $iterationsOMTS_groundTest+1;
			}else{
				$iterationsOMTS_groundTest = 0;
			}			
		}
		
		//BeR: snmp service not starting on DSU-C
		//putting less parameter check than on the script. This should be sufficient to detect the issue.
		if ($iterationsSNMPservice_down<4){
			if(($doc['TGS_ONAIR']['applicationAvailableDetail'] == '') &&
				($doc['TGS_FLIGHT']['flightPhase'] == '') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == '')){
					$iterationsSNMPservice_down = $iterationsSNMPservice_down+1;
			}else{
				$iterationsSNMPservice_down = 0;
				}
		}		

		//BeR: system mode not in service
		if ($iterations_SystemMode<10){
			if($doc['THALES_FLIGHT']['asdSysIfeSystemMode'] != 'service'){
					$iterations_SystemMode = $iterations_SystemMode+1;
			}else{
				$iterations_SystemMode = 0;
				}
		}		

		//BeR: OMA switch off issue
		if ($iterations_SwitchOff<4){
			if(($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['BTS1'] == 'KO') &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['CWLU1'] == 'KO') &&
				($doc['PING_STATUS']['CWLU2'] == 'KO') &&
				($doc['PING_STATUS']['CWLU3'] == 'KO') &&
				($doc['PING_STATUS']['BTS2'] == 'KO')){
					$iterations_SwitchOff = $iterations_SwitchOff+1;
			}else{
				$iterations_SwitchOff = 0;
			}			
		}	
		
		//BeR: OMTS KO due to DSU3 down issue
		if ($iterations_OMTS_DSU3_down<4){
			if(($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['CWLU1'] == 'KO') &&
				($doc['PING_STATUS']['CWLU2'] == 'OK') &&
				($doc['PING_STATUS']['CWLU3'] == 'OK')){
					$iterations_OMTS_DSU3_down = $iterations_OMTS_DSU3_down+1;
			}else{
				$iterations_OMTS_DSU3_down = 0;
			}			
		}		

		//BeR: OMTS KO due to ADBG down issue
		if ($iterations_OMTS_ADBG_down<4){
			if(($doc['PING_STATUS']['DSU1'] == 'OK') &&
				($doc['PING_STATUS']['DSU2'] == 'OK') &&
				($doc['PING_STATUS']['NCU'] == 'OK') &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['PING_STATUS']['BTS1'] == 'KO') &&
				($doc['PING_STATUS']['BTS2'] == 'KO') &&
				($doc['PING_STATUS']['BTS1-CS'] == 'OK') &&
				($doc['PING_STATUS']['BTS2-CS'] == 'OK') &&
				($doc['PING_STATUS']['BTS1-ADB'] == 'KO') &&
				($doc['PING_STATUS']['BTS2-ADB'] == 'KO')){
					$iterations_OMTS_ADBG_down = $iterations_OMTS_ADBG_down+1;
			}else{
				$iterations_OMTS_ADBG_down = 0;
			}			
		}

		//BeR: GSM MIB unresponsive
		if ($iterations_GSM_MIB_down<10){
			if(($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication'] == 'No') &&
				($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication'] == 'No') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'No')){
					$iterations_GSM_MIB_down = $iterations_GSM_MIB_down+1;
			}else{
				$iterations_GSM_MIB_down = 0;
			}			
		}

		//BeR: WiFi stuck on auto service enable false
		if ($iterations_WiFi_autoServiceEnableFalse<5){
			if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'autoServiceEnableFalse'){
					$iterations_WiFi_autoServiceEnableFalse = $iterations_WiFi_autoServiceEnableFalse+1;
			}else{
				$iterations_WiFi_autoServiceEnableFalse = 0;
			}			
		}

		//BeR: WiFi stuck on Fap disabled
		if ($iterations_WiFi_FapDisabled<5){
			if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'FapDisabledService'){
					$iterations_WiFi_FapDisabled = $iterations_WiFi_FapDisabled+1;
			}else{
				$iterations_WiFi_FapDisabled = 0;
			}			
		}

		//BeR: NCU unreachable due to ADB KO
		if ($iterations_NCU_ADBG_down<4){
			if(($doc['PING_STATUS']['NCU'] == 'KO') && ($doc['PING_STATUS']['NCU-ADB'] == 'KO') && ($doc['PING_STATUS']['DSU1'] == 'OK') && ($doc['PING_STATUS']['DSU2'] == 'OK')){
					$iterations_NCU_ADBG_down = $iterations_NCU_ADBG_down+1;
			}else{
				$iterations_NCU_ADBG_down = 0;
			}			
		}

		//BeR: NCU KO
		if ($iterations_NCU_KO<4){
			if(($doc['PING_STATUS']['NCU'] == 'KO') && ($doc['PING_STATUS']['NCU-ADB'] == 'OK') && ($doc['PING_STATUS']['DSU1'] == 'OK') && ($doc['PING_STATUS']['DSU2'] == 'OK')){
					$iterations_NCU_KO = $iterations_NCU_KO+1;
			}else{
				$iterations_NCU_KO = 0;
			}			
		}	

		//BeR: DSU-C port P5 check (from conn tool v2)
		//testing if P5.speed exist to confirm conn tool v2
		if (isset($doc['P5_PORT_STATUS']['speed'])){
			if ($iterations_DSU_P5<4){
				if(($doc['P5_PORT_STATUS']['speed'] != '100Mbps') || ($doc['P5_PORT_STATUS']['status'] != 'Pass')){
						$iterations_DSU_P5 = $iterations_DSU_P5+1;
				}else{
					$iterations_DSU_P5 = 0;
				}			
			}					
		}		

		//BeR: AVCD KO
		if ($iterations_AVCD<4){
			if(($doc['PING_STATUS']['AVCD1'] == 'KO')){
					$iterations_AVCD = $iterations_AVCD+1;
			}else{
				$iterations_AVCD = 0;
			}			
		}		

		//BeR: NCU LED or error message checks (from conn tool v2)
		//testing if TGS_ONAIR.ncuErrorMsg exist to confirm conn tool v2
		if (isset($doc['TGS_ONAIR']['ncuErrorMsg'])){
			if ($iterations_NCU_LedOrErrorMsg<4){
				if(($doc['TGS_ONAIR']['ncuErrorMsg'] != 'FAIL') || ($doc['TGS_ONAIR']['ncuLedPwr'] != 'on(1)') || ($doc['TGS_ONAIR']['ncuLedSysOk'] != 'on(1)') || ($doc['TGS_ONAIR']['ncuLedBootRdy'] != 'on')){
						$iterations_NCU_LedOrErrorMsg = $iterations_NCU_LedOrErrorMsg+1;
				}else{
					$iterations_NCU_LedOrErrorMsg = 0;
				}			
			}					
		}

		//BeR: Connectivity SW processes checks (from conn tool v2)
		//testing if SERVICE_STATUS.gsm status exists to confirm conn tool v2
		if (isset($doc['SERVICE_STATUS']['gsm status'])){
			if ($iterations_ProcessSW<4){
				if(($doc['SERVICE_STATUS']['gsm status'] != 'running...') || ($doc['SERVICE_STATUS']['vocem status'] != 'running...') || ($doc['SERVICE_STATUS']['agp status'] != 'running...') || ($doc['SERVICE_STATUS']['agprs status'] != 'running...') || ($doc['SERVICE_STATUS']['stu status'] != 'running...') || ($doc['SERVICE_STATUS']['nXt_Agent status'] != 'running') || ($doc['SERVICE_STATUS']['linkcontroller status'] != 'running...') || ($doc['SERVICE_STATUS']['named status'] != 'running...') || ($doc['SERVICE_STATUS']['snpd status'] != 'running...')){
						$iterations_ProcessSW = $iterations_ProcessSW+1;
				}else{
					$iterations_ProcessSW = 0;
				}			
			}					
		}

		//BeR: SDU health check (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterations_SDU_health<4){
				if($doc['SDU_STATUS']['asuSduInfoOverallStatus'] != 'pass(1)'){
						$iterations_SDU_health = $iterations_SDU_health+1;
				}else{
					$iterations_SDU_health = 0;
				}			
			}					
		}
		
		//BeR: SDU CC1 down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsSDUCC1_v2<4){
				if($doc['SDU_STATUS']['asuSduInfoCC1Status'] != 'pass(1)'){
						$iterationsSDUCC1_v2 = $iterationsSDUCC1_v2+1;
				}else{
					$iterationsSDUCC1_v2 = 0;
				}			
			}					
		}		

		//BeR: SDU CC2 down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsSDUCC2_v2<4){
				if($doc['SDU_STATUS']['asuSduInfoCC2Status'] != 'pass(1)'){
						$iterationsSDUCC2_v2 = $iterationsSDUCC2_v2+1;
				}else{
					$iterationsSDUCC2_v2 = 0;
				}			
			}					
		}

		//BeR: SDU-Antenna bus down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsSDU_Ant_bus<4){
				if($doc['SDU_STATUS']['asuSduInfoAntennaBus'] != 'pass(1)'){
						$iterationsSDU_Ant_bus = $iterationsSDU_Ant_bus+1;
				}else{
					$iterationsSDU_Ant_bus = 0;
				}			
			}					
		}

		//BeR: DLNA down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterations_DLNA<4){
				if($doc['SDU_STATUS']['asuDlnaInfoOverallStatus'] != 'pass(1)'){
						$iterations_DLNA = $iterations_DLNA+1;
				}else{
					$iterations_DLNA = 0;
				}			
			}					
		}
		
		//BeR: Antenna down (from conn tool v2)
		//testing if SDU_STATUS.asuSduInfoOverallStatus exists to confirm conn tool v2
		if (isset($doc['SDU_STATUS']['asuSduInfoOverallStatus'])){
			if ($iterationsAntenna_v2<4){
				if (($doc['SDU_STATUS']['asuAntInfoOverallStatus'] != 'pass(1)') && ($doc['SDU_STATUS']['asuSduInfoAntennaBus'] == 'pass(1)')){
						$iterationsAntenna_v2 = $iterationsAntenna_v2+1;
				}else{
					$iterationsAntenna_v2 = 0;
				}			
			}					
		}		

		//BeR: nslookup unsuccessful (from conn tool v2)
		//testing if SYS_DATA.nslookup exists to confirm conn tool v2
		if (isset($doc['SYS_DATA']['nslookup'])){
			if ($iterations_nslookup<4){
				if($doc['SYS_DATA']['nslookup'] == '0'){
						$iterations_nslookup = $iterations_nslookup+1;
				}else{
					$iterations_nslookup = 0;
				}			
			}					
		}

	   //BeR: ground failure check
	    if($iterations_groundFailure<4){
		   if(($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'groundSystemError') || ($doc['TGS_ONAIR']['applicationAvailableDetail'] == '14')){
				$iterations_groundFailure = $iterations_groundFailure+1;
		   }else{
				$iterations_groundFailure = 0;
		   }
		}

	   //BeR: WiFi only down - commFailure (ISE-4422)
	    if($iterations_WiFi_CommFailure<5){
		   if( ($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['TGS_ONAIR']['applicationAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['serviceAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='normal') &&
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='true') ) {
					$iterations_WiFi_CommFailure = $iterations_WiFi_CommFailure+1;
			}else{
				$iterations_WiFi_CommFailure = 0;
		   }
		}
		
	   //Check for any Failures available
	   
	   //BeR: removing SBB/SDU down one time check. Being replaced by check with several iterations
	   /*
	   if($doc['PING_STATUS']['SBB-1'] == 'KO')
	   {
			failureRecordForWifiEvent("SBB1 Down", $tempWifiOffArray);
			failureRecordForOmtsEvent("SBB1 Down", $tempOmtsOffArray);
		
	   }
	   */
	   
	   //BeR removing BTS KO filter which is not a root cause. Replaced with ARP overflow issue.
	   /*
	   if(	$doc['PING_STATUS']['BTS1'] == 'KO' && 
			$doc['PING_STATUS']['BTS2'] == 'KO')
	   {
			failureRecordForOmtsEvent("BTS 1 & 2 Down", $tempOmtsOffArray);

	   }
		*/

		//BeR: removing FapDisable which is not relevant per the log review done and can be triggered for incorrect reason
		/*
   	   if($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'FapDisabledService')
	   {
			failureRecordForWifiEvent("FapDisabledService", $tempWifiOffArray);
			failureRecordForOmtsEvent("FapDisabledService", $tempOmtsOffArray);	

	   }	
	   */
	   
	   //BeR: removing ground error check. Not relevant for now
	   /*
   	   if(	($doc['TGS_ONAIR']['applicationAvailableDetail'] == '14' || 
			$doc['TGS_ONAIR']['applicationAvailableDetail'] == 'groundSystemError'))
	   {
			failureRecordForWifiEvent("Ground Error", $tempWifiOffArray);
			failureRecordForOmtsEvent("Ground Error", $tempOmtsOffArray);	

	   }	
		*/

		 //SB:Filter for more Check
		if($doc['TGS_ONAIR']['serviceAvailable'] == 'false'){
			//WiFi inop with serviceAvailable only stuck on FALSE issue==1
			//BeR: removing single check of WiFi stuck on FALSE. Too many unexpected trigger due to usual hiccup at start of cruise
			/*
			if(($doc['TGS_ONAIR']['applicationAvailable'] == 'true') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'noFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 10))			
			{
				failureRecordForWifiEvent("ISE-3984 WiFi inop - WiFi serviceAvailable only stuck on FALSE issue detected", $tempWifiOffArray);
			}
			*/
			
			//Antenna/BSU or both CC de registered or SDU health oid in error issues==8
			/*
			if((($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') ||($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error')) &&
				($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK'))	
			{
				failureRecordForWifiEvent("Antenna issue detected", $tempWifiOffArray);
			}
			*/
			
			//WiFi+GSM inop after SDU short disconnection==14
			//BeR: removing regular WiFi GSM short disconnection check and adding an iteration check instead
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0) &&
				($doc['PING_STATUS']['SBB-1'] == 'OK') &&
				($doc['TGS_ONAIR']['applicationAvailable'] == 'false') &&
				($doc['TGS_ONAIR']['applicationAvailableDetail'] == 'commFailure') &&
				($doc['TGS_ONAIR']['serviceAvailableDetail'] == 'applicationNotAvailable') &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='error') &&
				($doc['TGS_ONAIR']['gsmConnexServiceAllowedIndication']=='false') &&
				($doc['TGS_ONAIR']['gsmConnexMobilesAllowedIndication']=='false')) 
			{
				failureRecordForWifiEvent("issue ISE-3839 WiFi+GSM inop after SDU short disconnection detected", $tempWifiOffArray);
			}
			*/
		}
		
		if($doc['TGS_ONAIR']['serviceAvailable'] == 'true'){
			//SDU CC1 de registration issue==3
			//BeR: removing one time check and adding iteration check for SDU CC1 deregistration
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] == 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] == 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'startup'))			
			{
				failureRecordForWifiEvent("SDU channel card 1 de registration issue detected", $tempWifiOffArray);
			}
			*/
			
			//NCU boot up error issues==4
			//BeR: removing regular NCU boot check and adding a NCU+GisDb iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['btsARFCN'] == '-1'))			
			{
				failureRecordForWifiEvent("potential NCU bootup error issue detected", $tempWifiOffArray);
			}
			*/
			
			//GSM stuck in startup with CC1 ok==7
			//BeR: removing regular GSM startup/CC1 ok check and adding iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] >0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState']=='startup') &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] >0)) 
			{
				failureRecordForWifiEvent("issue ISE-4049 GSM stuck in startup - CC up and logtable ok detected", $tempWifiOffArray);
			}
			*/
			
			//GisDB connection issue==9
			//BeR: removing regular GisDB issue check and adding a NCU+GisDb iteration check instead
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['TGS_ONAIR']['btsARFCN'] == 580) &&
				($doc['PING_STATUS']['BTS1'] == 'OK') && 
				($doc['PING_STATUS']['BTS2'] == 'OK'))			
			{
				failureRecordForWifiEvent("potential GisDB connection issue detected", $tempWifiOffArray);
			}
			*/
			
			//NCU unreachable due to either DSU1 or DSU2 down issue==10
			//BeR: removing NCU/DSU down to update it to iteration check
			/*
			if(($doc['TGS_ONAIR']['backhaulLinkAvailableBitrate'] > 0) &&
				($doc['TGS_ONAIR']['backhaulLinkNumberActiveContexts'] > 0) &&
				($doc['TGS_ONAIR']['gsmConnexTsSystemState'] == 'error') &&
				($doc['PING_STATUS']['NCU'] == 'KO') &&
				($doc['PING_STATUS']['NCU-ADB'] == 'KO') &&
				(($doc['PING_STATUS']['DSU1'] == 'KO') || ($doc['PING_STATUS']['DSU2'] == 'KO')))			
			{
				failureRecordForWifiEvent("NCU unreachable due to either DSU1 or DSU2 down issue detected", $tempWifiOffArray);
			}
			*/
		}
	   
	}	
	else
	{
		//do nothing
	}

}

//write connectivity status for a aircraft into the mysql database
/*writeConnectivityStatusForAircraftOnToMysqlDb(	$dbConnection, 
												$dbName, 
												$bAnalyticsDB, 
												$aircraftTailsign);*/
																								

?>