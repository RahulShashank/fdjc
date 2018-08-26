<?php
	require_once("validateUser.php");
	include("getAircraftCodes.php");

	$approvedRoles = [$roles["admin"], $roles["engineer"]];
	$checkPermissionBool = $auth->checkPermissionBool($hash, $approvedRoles);
	
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$uid = $request->uid;
	$currentPassword = $request->currentPassword;
	$password = $request->password;
	$confirmPassword = $request->confirmPassword;
	$isAdmin = $request->isAdmin;
	$changePassword = $auth->changePassword($uid, $currentPassword, $password, $confirmPassword, $isAdmin);
	$response = ["success"=>false, "message"=>""];
	if (!$checkPermissionBool) {
		$response["success"] = false;
		$response['message'] = "Do not have permission for that action.";
	} else if (!$changePassword['error']) {
		$response["success"] = true;
		$response['message'] = $changePassword['message'];
	} else {
		$response["success"] = false;
		$response['message'] = $changePassword['message'];
	}error_log("Response: " . print_r($response, TRUE));
	echo $json_response = json_encode($response);
	
?>
