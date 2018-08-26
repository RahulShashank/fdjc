<?php
	
	session_start();
	
	require_once "../database/connecti_database.php";

	include("languages/en.php");
	include("config.class.php");
	include("auth.class.php");

	$dbh = new PDO("mysql:host=$hostname;dbname=$mainDB", "$username", "$password");

	$config = new Config($dbh);
	$auth = new Auth($dbh, $config, $lang);
	
	$hash = $_COOKIE[$config->cookie_name];
	
	if ($auth->logout($hash))
	{
		$_SESSION['message'] = "Successfully logged out!";
	} else {
		echo "Oh my god";
	}
	
	header("Location: ../index.php");
?>