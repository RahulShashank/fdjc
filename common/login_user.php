<?php
	
	require_once "../database/connecti_database.php";
	
	include("languages/en.php");
	include("config.class.php");
	include("auth.class.php");

	$dbh = new PDO("mysql:host=$hostname;dbname=$mainDB", "$username", "$password");
	
	$config = new Config($dbh);
	$auth = new Auth($dbh, $config, $lang);
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$email = $request->email;
	$password = $request->password;
	$remember = $request->remember;
	$login = $auth->login($email, $password, $remember);
	$response = ["success"=>false, "message"=>"", "homepage"=>"/"];
	if (!$login['error']) {
		setcookie($config->cookie_name, $login['hash'], $login['expire'], $config->cookie_path);
		$response["success"] = true;
		$response["homepage"] = $auth->getHomepage($login['hash']);
	} else {
		$response["success"] = false;
		$response['message'] = $login['message'];
	}
	echo $json_response = json_encode($response);
?>