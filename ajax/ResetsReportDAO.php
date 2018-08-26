<?php

require_once "../database/connecti_database.php";
require_once "../common/seatAnalyticsData.php";

require_once("../engineering/checkEngineeringPermission.php");

	$acConfiguration = "A350, A380";

	$airlineId 			= $_REQUEST['airlineId'];
	$startDateTime		= $_REQUEST['startDateTime'];
	$endDateTime 		= $_REQUEST['endDateTime'];
	$reportType		 	= $_REQUEST['reportType'];
	//$lruType			= $_REQUEST['lruType'];
	$period 			= $_REQUEST ['period'];
	$platform 			= $_REQUEST ['platform'];
	$config 			= $_REQUEST ['config'];
	$software 			= $_REQUEST ['software'];
	
	$newstartDateTime = $startDateTime;
	$newendDateTime = $endDateTime;
	
	// Create header data
	$periods = [];
	if($period == "daily") {				
		$begin = new DateTime($startDateTime);
		$end = new DateTime($endDateTime);
		$end->add(new DateInterval('P1D'));

		$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

		foreach($daterange as $date){
			$periods[] = $date->format("Y-m-d");
		}
	} else if($period == "weekly") {
		$p = new DatePeriod(
			new DateTime($newstartDateTime), 
			new DateInterval('P1W'), 
			new DateTime($newendDateTime)
		);
		
		foreach ($p as $w) {
			$periods[] = $w->format('Y') . "-W" . $w->format('W'); 
		}
	} else if($period == "monthly") {
		$start    = (new DateTime($startDateTime))->modify('first day of this month');
		$end      = (new DateTime($endDateTime))->modify('first day of next month');
		$interval = DateInterval::createFromDateString('1 month');
		$datePeriod   = new DatePeriod($start, $interval, $end);
		
		$count = iterator_count($datePeriod);
		$i = 1;

		foreach ($datePeriod as $dt) {
			// As the algorithm is ending to the next month after the one we want, we need to make sure to not take it into consideration
			if($i == $count) {
				break;
			}			
			
			$timestamp    = strtotime($dt->format("Y-m"));
			$firstDay = date('m-01-Y', $timestamp);
			$lastDay  = date('m-t-Y', $timestamp);
			$periods[] = $dt->format("Y-m");
			
			$i++;
		}
	} else {
		echo "period not recognized";
	}

	$resets = [];
	
	if($reportType == "aircraft") {
		//$query="SELECT id,tailsign,databaseName FROM aircrafts WHERE airlineId=$airlineId AND Ac_Configuration IN('A350', 'A380') AND Ac_Configuration<>'' ORDER BY tailsign ASC";
		$query="SELECT id,tailsign,databaseName FROM aircrafts WHERE airlineId=$airlineId ";
		if($platform!=''){
		    $query.= " AND platform in ($platform)";
		}
		if($config!=''){
		    $query.= " AND Ac_Configuration in ($config)";
		}
		if($software!=''){
		    $query.= " AND software in ($software)";
		}
		$query.= " ORDER BY tailsign ASC";
		
		//select id,tailsign,databaseName FROM banalytics.aircrafts where airlineId=1 and platform in ('i5000') and Ac_Configuration IN ('A320') and software in ('v2110');
		$result = mysqli_query($dbConnection,$query);

		if($result && mysqli_num_rows($result) > 0 ) {
			while( $row = mysqli_fetch_array($result)){
				$acid = $row['id'];
				$tailsign = $row['tailsign'];	
				//$dbName = $row['databaseName'];
				
				$tailsignMetrics = calculateResetsMetrics($dbConnection, $startDateTime, $endDateTime, $acid, $period);
				$resets[] = array(
					"tailsign" => $tailsign,
					"data" => $tailsignMetrics
				); 
			}
		} // end if query result tailsign
	} else if($reportType == "actype") {
		$query="SELECT DISTINCT (Ac_Configuration) FROM aircrafts WHERE airlineId=$airlineId ";
		if($platform!=''){
		    $query.= " AND platform in ($platform)";
		}
		if($config!=''){
		    $query.= " AND Ac_Configuration in ($config)";
		}
		if($software!=''){
		    $query.= " AND software in ($software)";
		}
		$query.= " AND Ac_Configuration<>'' ORDER BY Ac_Configuration ASC";
		$result = mysqli_query($dbConnection,$query);

		if($result && mysqli_num_rows($result) > 0 ) {
			while( $row = mysqli_fetch_array($result)){
				$configuration = $row['Ac_Configuration'];
				//echo "<strong>configuration:</strong> $configuration<br>";
				$query2="SELECT id FROM aircrafts WHERE airlineId=$airlineId AND Ac_Configuration='$configuration' ORDER BY tailsign ASC";
				$result2 = mysqli_query($dbConnection,$query2);
				$acid = "";

				if($result2 && mysqli_num_rows($result2) > 0 ) {
					while( $row2 = mysqli_fetch_array($result2)){
						if($acid !== "") {
							$acid .= ",";
						}
						$acid .= $row2['id'];
					}
				} // end if query result tailsign
				
				$tailsignMetrics = calculateResetsMetrics($dbConnection, $startDateTime, $endDateTime, $acid, $period);
				
				$resets[] = array(
					"tailsign" => $configuration,
					"data" => $tailsignMetrics
				); 
			}
		} // end if query result for ac_configuration
	} else if($reportType == "platform") {
		$query="SELECT DISTINCT (platform) FROM aircrafts WHERE airlineId=$airlineId ";
		if($platform!=''){
		    $query.= " AND platform in ($platform)";
		}
		if($config!=''){
		    $query.= " AND Ac_Configuration in ($config)";
		}
		if($software!=''){
		    $query.= " AND software in ($software)";
		}
		$query.= " ORDER BY platform ASC";
		$result = mysqli_query($dbConnection,$query);

		if($result && mysqli_num_rows($result) > 0 ) {
			while( $row = mysqli_fetch_array($result)){
				$platform = $row['platform'];
				//echo "<strong>platform:</strong> $platform<br>";
				$query2="SELECT id FROM aircrafts WHERE airlineId=$airlineId AND platform='$platform' ORDER BY tailsign ASC";
				$result2 = mysqli_query($dbConnection,$query2);
				$acid = "";

				if($result2 && mysqli_num_rows($result2) > 0 ) {
					while( $row2 = mysqli_fetch_array($result2)){
						if($acid !== "") {
							$acid .= ",";
						}
						$acid .= $row2['id'];
					}
				} // end if query result tailsign
				
				$tailsignMetrics = calculateResetsMetrics($dbConnection, $startDateTime, $endDateTime, $acid, $period);
				
				$resets[] = array(
					"tailsign" => $platform,
					"data" => $tailsignMetrics
				); 
			}
		} // end if query result for ac_configuration
		
		
	} else {
		echo "report type not supported";
	}
	
	$data = array(
		"periods" => $periods,
		"items" => $resets,
	);
	echo json_encode($data);
	
	function calculateResetsMetrics($dbConnection, $startDateTime, $endDateTime, $acid, $period) {
	    error_log(basename(__FILE__) . "-> calculateResetsMetrics() entered");
		$tailsignMetrics = [];
		
		if($period == "daily") {				
			$tailsignMetrics = calculateResetsMetricsForDays($dbConnection, $mainDB, $startDateTime, $endDateTime, $acid);
		} else if($period == "weekly") {
			$tailsignMetrics = calculateResetsMetricsForWeeks($dbConnection, $mainDB, $startDateTime, $endDateTime, $acid);
		} else if($period == "monthly") {
			$tailsignMetrics = calculateResetsMetricsForMonths($dbConnection, $mainDB, $startDateTime, $endDateTime, $acid);
		} else {
			echo "period *$period* not supported<br>";
		}

		/*
		$resets[] = array(
			"tailsign" => $tailsign,
			"data" => $tailsignMetrics
		);
		*/
		
		return $tailsignMetrics;
	}

	// Function to calculate total commanded resets and commanded resets per flight hour for the given dates
	function calculateResetsMetricsForDays($dbConnection, $dbName, $start, $end, $acid) {
	    error_log(basename(__FILE__) . "-> calculateResetsMetricsForDays() entered");
	    $metrics = array();
		$output = array();
		
		$query = "select DATE(flightDate) as flightDate, COALESCE(SUM(totalCommandedResets),0) as totalCommandedResets, COALESCE(SUM(totalUncommandedResets),0) as totalUncommandedResets, COALESCE(SUM(systemResetsCount),0) as systemResetsCount, COALESCE(SUM(totalCruise),0) as totalCruise FROM banalytics.resets_report where acid IN ($acid) AND flightDate BETWEEN '$start 00:00:00' AND '$end 23:59:59'  group by DATE(flightDate)";
		$result = mysqli_query($dbConnection,$query);
		
		if($result && mysqli_num_rows($result) > 0 ) {
		    while($row = mysqli_fetch_assoc($result)) {
		        $metrics[] = array(
		            "date" => $row['flightDate'],
		            "totalCommandedResets" => $row['totalCommandedResets'],
		            "totalUncommandedResets" => $row['totalUncommandedResets'],
		            "systemResetsCount" => $row['systemResetsCount'],
		            "totalCruise" => $row['totalCruise']
		        );
		    }
		}
		
		$begin = new DateTime($start);
		$end = new DateTime($end);
		$end->add(new DateInterval('P1D'));
		
		$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

		if(sizeof($metrics)>0){
	        foreach($daterange as $date){
	            $exists = false;
	            foreach($metrics as $d){
	                $queryDate =$date->format("Y-m-d");
	                
    		        if($d['date']==$queryDate){	
    		            $exists = true;
    		            break;
    		        }
    		    }
    		    
    		    if(!$exists) {
    		        $output[] = array(
    		            "date" => $queryDate,
    		            "totalCommandedResets" => '',
    		            "totalUncommandedResets" => '',
    		            "systemResetsCount" => '',
    		            "totalCruise" => ''
    		        );
    		    } else {
    		        $output[] = $d;
    		    }
    		}
		}else{
		    foreach($daterange as $date){
		        $queryDate = $date->format("Y-m-d");
		        $output[] = array(
		            "date" => $queryDate,
		            "totalCommandedResets" => '',
		            "totalUncommandedResets" => '',
		            "systemResetsCount" => '',
		            "totalCruise" => ''
		        );
		    }		    
		}
		
		return $output;
	}
	
	function calculateResetsMetricsForWeeks($dbConnection, $dbName, $newstartDateTime, $newendDateTime, $acid) {
		$p = new DatePeriod(
			new DateTime($newstartDateTime), 
			new DateInterval('P1W'), 
			new DateTime($newendDateTime)
		);
		
		$metrics = [];
		
		foreach ($p as $w) {
			$week = $w->format('W'); 
			$year = $w->format('Y'); 
			$weekDates = getWeekStartAndEndDate($week, $year);
			
			$query = "SELECT
						IFNULL(SUM(totalCommandedResets),'') as totalCommandedResets,	
						IFNULL(SUM(totalUncommandedResets),'') as totalUncommandedResets,
						IFNULL(SUM(systemResetsCount),'') as systemResetsCount,
						IFNULL(SUM(totalCruise),'') as totalCruise	
				FROM $dbName.resets_report
				WHERE acid IN ($acid)
				AND flightDate BETWEEN '" . $weekDates['week_start'] . "  00:00:00' AND '" . $weekDates['week_end'] . "  23:59:59'";
			//echo $query . "<br>"; exit;
			$result = mysqli_query($dbConnection,$query);

			if($result && mysqli_num_rows($result) > 0 ) {
			    while ($row = mysqli_fetch_array($result)) {
			        $metrics[] = array(
			            "date" => "$year-W" . $week,
			            "totalCommandedResets" => $row['totalCommandedResets'],
			            "totalUncommandedResets" => $row['totalUncommandedResets'],
			            "systemResetsCount" => $row['systemResetsCount'],
			            "totalCruise" => $row['totalCruise']
			        );
			    }
			}
		}
		
		return $metrics;
	}
	
	// Function to get start and end dates for a given week and year
	function getWeekStartAndEndDate($week, $year) {
		$dto = new DateTime();
		$dto->setISODate($year, $week);
		$ret['week_start'] = $dto->format('Y-m-d');
		$dto->modify('+6 days');
		$ret['week_end'] = $dto->format('Y-m-d');
		return $ret;
	}
	
	function calculateResetsMetricsForMonths($dbConnection, $dbName, $startDateTime, $endDateTime, $acid) {
		$metrics = [];
		
		$start    = (new DateTime($startDateTime))->modify('first day of this month');
		$end      = (new DateTime($endDateTime))->modify('first day of next month');
		$interval = DateInterval::createFromDateString('1 month');
		$period   = new DatePeriod($start, $interval, $end);

		$count = iterator_count($period);
		$i = 1;
		
		foreach ($period as $dt) {
			// As the algorithm is ending to the next month after the one we want, we need to make sure to not take it into consideration
			if($i == $count) {
				break;
			}
			
			$timestamp    = strtotime($dt->format("Y-m"));
			$firstDay = date('Y-m-01', $timestamp);
			$lastDay  = date('Y-m-t', $timestamp);
			
			//$monthMetrics = calculateResetsMetricsForDays($dbConnection, $dbName, $firstDay, $lastDay, true);
			$query = "SELECT
						IFNULL(SUM(totalCommandedResets),'') as totalCommandedResets,
						IFNULL(SUM(totalUncommandedResets),'') as totalUncommandedResets,				
						IFNULL(SUM(systemResetsCount),'') as systemResetsCount,
						IFNULL(SUM(totalCruise),'') as totalCruise	
				FROM $dbName.resets_report
				WHERE acid IN ($acid)
				AND flightDate BETWEEN '$firstDay 00:00:00' AND '$lastDay 23:59:59'";
			//echo $query . "<br>"; exit;
			$result = mysqli_query($dbConnection,$query);

			if($result && mysqli_num_rows($result) > 0 ) {
			    while($row = mysqli_fetch_array($result)) {
			        $metrics[] = array(
			            "date" => $dt->format("Y-m"),
			            "totalCommandedResets" => $row['totalCommandedResets'],
			            "totalUncommandedResets" => $row['totalUncommandedResets'],
			            "systemResetsCount" => $row['systemResetsCount'],
			            "totalCruise" => $row['totalCruise']
			        );
			    }
			}
			
			$i++;
		}
		
		return $metrics;
	}
?>