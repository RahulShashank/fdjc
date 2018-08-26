<?php
	session_start();
	require_once "database/connecti_database.php";

	include("common/languages/en.php");
	include("common/config.class.php");
	include("common/auth.class.php");

	$dbh = new PDO("mysql:host=$hostname;dbname=$mainDB", "$username", "$password");

	$config = new Config($dbh);
	$auth = new Auth($dbh, $config, $lang);
	if (isset($_COOKIE[$config->cookie_name])) {
		$hash = $_COOKIE[$config->cookie_name];
	}
	if(isset($hash) && $auth->checkSession($hash)) {
		$homepage = $auth->getHomepage($hash);
		header("Location: " . $homepage);
	}
	if(isset($hash) && $auth->checkSession($hash)) {
		$homepage = $auth->getHomepage($hash);
		header("Location: " . $homepage);
	}
?>
<html lang="en" class="body-full-height">
    <head>        
        <!-- META SECTION -->
		<link rel="shortcut icon" href="img/globe-icon.ico">
        <title>BITE Analytics</title>            
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        
        <!--  <link rel="icon" href="globe-icon" type="image/x-icon" />
        <!-- END META SECTION -->
        
        <!-- CSS INCLUDE -->        
        <link rel="stylesheet" type="text/css" id="theme" href="css/theme-default.css"/>
		<script src="js/angular.min.js"></script>
        <!-- EOF CSS INCLUDE -->                                     
    </head>
    <body ng-app="myApp">
        
        <div id="containerBox" class="login-container" ng-controller="loginCtrl">
        
            <div class="login-box animated fadeInDown">
                <div style="text-align: center;">
					<img src="img/Thales_Logo.png" style="width: 400px;">
				</div>
				<br/>
                <div id="loginBox" class="login-body loginpage" style="height:300px;">
                    <div class="login-title"><strong>Log In</strong> to your account</div>
                    <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-md-12">
                            <input type="text" id="email"  class="form-control" placeholder="E-mail"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <input type="password" id="password" class="form-control" placeholder="Password"/>
                        </div>
                    </div>
					<div class="form-group">
                        <div class="col-md-12">                            
							<label class="check" style="color: #fff;"><input type="checkbox" id="checkmark"  class="icheckbox" checked="checked" /> Remember me on this computer</label>
                        </div>
                    </div>
                    <div class="form-group">
                        
                        <div class="col-md-12">
                            <button id="submit" class="btn btn-info btn-block" style="background-color: #428bca; border-color: #428bca;">Log In</button>
                        </div><br/><br/>
                        
                        <div class="col-md-12">
                            <a href="mailto:Stephane.TOUSSAINT@us.thalesgroup.com;smitanjali.behera@us.thalesgroup.com?Subject=[Bite%20Tool]%20Forgot%20Password" target="_top" class="btn btn-link btn-block">Ask for an account / Forgot password?</a>
                        </div>
                    </div>
                  
                     <br/>
                    </form>
                </div>
               
            </div>
            
        </div>
	<script src="js/plugins/jquery/jquery.min.js"></script>
    <script src="js/plugins/bootstrap/bootstrap.min.js"></script>        
    </body>
	<script>
		var app = angular.module('myApp', []);
	</script>
	<script src="controllers/loginCtrl.js"></script>
</html>






