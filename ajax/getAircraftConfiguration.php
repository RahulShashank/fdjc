<?php

	
	require_once "../database/connecti_database.php";
	//require_once('../admin/checkAdminPermission.php');

	$postdata = file_get_contents ( "php://input" );
	$request = json_decode ( $postdata, true );

	$airline = $request ['airline'];
	
	$configuration = [];
	$query="SELECT DISTINCT `Ac_Configuration` FROM `aircrafts` WHERE `airlineId` ='$airline' AND `Ac_Configuration`<>'' ORDER BY Ac_Configuration ASC";
	
	
	$result = mysqli_query ($dbConnection, $query);
		if($result){
			while($row = mysqli_fetch_array($result)) {
				array_push($configuration, $row['Ac_Configuration']);
			}
		}
	echo $json_response = json_encode($configuration);
	

	
?>