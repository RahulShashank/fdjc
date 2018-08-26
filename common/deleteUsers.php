<?php
	require_once("validateUser.php");
	$approvedRoles = [$roles["admin"]];
	$checkPermissionBool = $auth->checkPermissionBool($hash, $approvedRoles);
	
    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$selectedUsers = $request->selectedUsers;
	$response = ["success"=>false, "message"=>"", "uid"=>-1];
	if ($checkPermissionBool) {
		if ($selectedUsers) {
			foreach ($selectedUsers as $uid) {
				//echo "UID: " . $uid[id];
				$auth->adminDeleteUser($uid);
			}	
			$_SESSION['message'] = "User(s) successfully deleted";
		}
		$response["success"] = true;
		$response['message'] = "User(s) successfully deleted";
	} else {
		$response["success"] = false;
		$response['message'] = "Do not have permission for that action.";
	}
	
	echo $json_response = json_encode($response);
?>
