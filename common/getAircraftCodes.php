<?php
	//include("languages/en.php");
	//include("config.class.php");
	//include("auth.class.php");
	/*
	$dbh = new PDO("mysql:host=localhost;dbname=banalytics", "root", "");
	$config = new Config($dbh);
	$auth = new Auth($dbh, $config, $lang);
	*/
	
	//echo json_encode($airlineCodes);

	require_once "../database/connecti_database.php";
// 	require_once("validateUser.php");
// 	$approvedRoles = [$roles["admin"], $roles["engineer"]];
// 	$auth->checkPermission($hash, $approvedRoles);
	
	function connectToDB() {
		// $username = "root";
		// $password = "";//"Stef279*";
		// $hostname = "localhost"; 
		// $mainDB = "banalytics"; 
	
		// $dbConnection = mysqli_connect($hostname, $username, $password, $mainDB)
		// or die("Unable to connect to MySQL");

		// // Check connection
		// if (mysqli_connect_errno())
		// {
		// 	echo "Failed to connect to MySQL: " . mysqli_connect_error();
		// }
		// // Set autocommit to off
		// mysqli_autocommit($dbConnection,FALSE);
		
		return $GLOBALS['dbConnection'];
	}
	
	function getNumberOfAirlines() {
		$dbConnection  = connectToDB();
		$query = "SELECT COUNT(*)  FROM `airlines`";
		$result = mysqli_query ($dbConnection, $query);
		$count = 0;
		while($row = mysqli_fetch_array($result)) {
			$count = $row[0];
		}
		
		return $count;
		
		
	}
	
	function aircraftCodesArray() {
	    
	    $dbConnection  = connectToDB();
	    
	    $airlineCodes = [];
	    $query = "SELECT * FROM `airlines` ORDER BY `name`";
	    $result = mysqli_query ($dbConnection, $query);
	    while($row = mysqli_fetch_array($result)) {
	        $airlineCodes[$row['id']] = $row['name'];
	    }
	    return $airlineCodes;
	}
	
	function getAirlineCodes() {
	    
	    $dbConnection  = connectToDB();
	    
	    $airlineCodes = array();
	    
	    $query = "SELECT * FROM `airlines` ORDER BY `name`";
	    $result = mysqli_query ($dbConnection, $query);
	    while($row = mysqli_fetch_assoc($result)) {
	        $airlineCodes[] = $row;
	    }
	    return $airlineCodes;
	}
	
	function codeToAcronymString($airlineIds) {		
		$dbConnection  = connectToDB();		
		$airlineCodes = [];
		if($airlineIds==-1){
			$query = "SELECT *  FROM `airlines` ORDER BY `name` ";
		}else{
			$query = "SELECT *  FROM `airlines` where id in ($airlineIds) ORDER BY `name`";
		}
		$result = mysqli_query ($dbConnection, $query);
		while($row = mysqli_fetch_array($result)) {
			$airlineCodes[$row['id']] = $row['name'];
		}
		
		$airlineCodes[-1] = '<i>All airlines</i>';
		$airlineCodes[0] = 'N/A';
		$acronymString = "";
		$airlineIds = str_replace(' ', '', $airlineIds);
		$airlineIds = explode(",", $airlineIds);
		//var_dump($airlineIds);
		//var_dump($airlineCodes);
		
// 		foreach ($airlineCodes as $airlineId) {			
// 			if ($airlineId == '') break;
// 			$acronymString.= $airlineId . ", ";
// 		}
// 		return substr($acronymString, 0, -2);

		foreach ($airlineIds as $airlineId) {
		    if ($airlineId == '') break;
		    $acronymString = $acronymString . $airlineCodes[intval($airlineId)] . ", ";
		}
		return substr($acronymString, 0, -2);
		
	}
	
?>
