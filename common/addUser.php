<?php
	
	include("getAircraftCodes.php");
	require_once("validateUser.php");

	$approvedRoles = [$roles["admin"]];
	$checkPermissionBool = $auth->checkPermissionBool($hash, $approvedRoles);
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$email = $request->email;
	$password = $request->password;
	$confirmpassword = $request->confirmpassword;
	$airline = $request->airline;
	$role = $request->role;
	$airlineCount = getNumberOfAirlines();
	
	if ($role == "Admin") {
		$airline = [0];
	}
	
	if (count($airline) == $airlineCount) {
		$airline = "-1";
	} else {
		$airline = implode($airline, ", ");
	}

	$register = $auth->register($email, $password, $confirmpassword, $airline, $role);
	$response = ["success"=>false, "message"=>"", "uid"=>-1];
	if (!$checkPermissionBool) {
		$response["success"] = false;
		$response['message'] = "Do not have permission for that action.";
	} else if (!$register['error']) {
		$uid = $auth->getUID($email);
		$auth->activate($uid);
		$response["success"] = true;
		$response["uid"] = $uid;
		$response["acronyms"] = codeToAcronymString($airline);
		$response["message"] = $register['message'];
		//	var_dump($response);
	} else {
		$response["success"] = false;
		$response['message'] = $register['message'];
	}
	echo $json_response = json_encode($response);
	
?>
