<?php


	function getstatus($status) {
	$aircraftStatus = '';	

		if($status >=2) {
			$aircraftStatus = 'statusDanger';
		}
		else if($status ==0) {
			$aircraftStatus = 'statusGreen';
		}
		else if($status ==1) {
			$aircraftStatus = 'statusWar';
		}
		else {
			$aircraftStatus = "StatusGrey";
		}

		return $aircraftStatus;
	}
	
	function getSwstatus($Swstatus) {
	$aircraftSwStatus = '';	

		if($Swstatus==1) {
			$aircraftSwStatus = 'statusGreen';
		}
		else {
			$aircraftSwStatus = "statusWar";
		}

		return $aircraftSwStatus;
	}




?>