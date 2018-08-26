<?php
	session_start();

	require_once "../database/connecti_database.php";
	require_once('../admin/checkAdminPermission.php');

	include "getAircraftCodes.php";

	$query = "SELECT users.id, users.email, users.role, users.isactive, users.approvedAirlines, users.lastActive FROM `users`";
	$result = mysqli_query($dbConnection, $query);

	$users = array();
	while ($row = mysqli_fetch_array($result)) {
			$users[] = $row;
	}

	$query = "SELECT uid FROM sessions WHERE sessions.expiredate > NOW()";
	$result = mysqli_query($dbConnection, $query);

	$onlineUsers = array();
	while ($row = mysqli_fetch_array($result)) {
			$onlineUsers[] = $row[0];
	}
	$usersCount = count($users);
	for ($i = 0; $i < $usersCount; $i++) {
		$tempIndex = array_search($users[$i]['id'], $onlineUsers);
		if ($users[$i]['id'] == $onlineUsers[$tempIndex]) {
			$users[$i]['status'] = 'Online';
		} else {
			$users[$i]['status'] = 'Offline';
		}
		if ($users[$i]['isactive']) {
			$users[$i]['isactive'] = 'Active';
		} else {
			$users[$i]['isactive'] = 'Inactive';
		}
		$users[$i]['approvedAirlines'] = codeToAcronymString($users[$i]['approvedAirlines']);
		//$users[$i]['approvedAirlines'] = getAirlineAcronyms($users[$i]['approvedAirlines']);
		//echo $users[$i];
	}
		
	# JSON-encode the response
	echo $json_response = json_encode($users);
?>
