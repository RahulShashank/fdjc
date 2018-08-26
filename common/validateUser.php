<?php
	require_once "../database/connecti_database.php";
	
	include("languages/en.php");
	include("config.class.php");
	include("auth.class.php");
	
	$dbh = new PDO("mysql:host=$hostname;dbname=$mainDB", "$username", "$password");
	$config = new Config($dbh);
	$auth = new Auth($dbh, $config, $lang);
	$hash = $_COOKIE[$config->cookie_name];
	$roles = array("admin"=>"Admin",
				   "manager"=>"Manager", 
				   "engineer"=>"Engineer", 
				   "customer"=>"Customer", 
				   "all"=>"all"
				   );
	$_SESSION['airlineIds'] = $auth->getAirlinesIDs($hash);
?>