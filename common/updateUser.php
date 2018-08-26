<?php
	include("getAircraftCodes.php");
	require_once("validateUser.php");
	
	$approvedRoles = [$roles["admin"]];
	$checkPermissionBool = $auth->checkPermissionBool($hash, $approvedRoles);
	
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$uid = $request->uid;
	$email = $request->email;
	$airline = $request->airline;
	$role = $request->role;
	$isactive = $request->isactive;
	
	if ($role == "Admin") {
		$airline = [0];
	}
	$airlineCount = getNumberOfAirlines();
	if (count($airline) == $airlineCount) {
		$airline = '-1';
	} else {
		$airline = implode($airline, ", ");
	}
	$update = $auth->updateUser($uid, $email, $airline, $role, $isactive);
	$response = ["success"=>false, "message"=>""];
	if (!$checkPermissionBool) {
		$response["success"] = false;
		$response['message'] = "Do not have permission for that action.";
	} else if (!$update['error']) {
		$response["success"] = true;
		$response["acronyms"] = codeToAcronymString($airline);
		$response['message'] = $update['message'];
		$response['oldEmail'] = $update['oldEmail'];
	} else {
		$response["success"] = false;
		$response['message'] = $update['message'];
	}
	echo $json_response = json_encode($response);
?>
