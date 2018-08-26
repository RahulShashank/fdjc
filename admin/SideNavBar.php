<?php
session_start();
require_once ("../common/validateUser.php");
$approvedRoles = [$roles["admin"]
];
$auth->checkPermission($hash, $approvedRoles);

require_once ("../common/getAircraftCodes.php");
// $airlinesCodesArray = aircraftCodesArray();
$airlinesCodesArray = getAirlineCodes();

$uid = $auth->getSessionUID($hash);
$user = $auth->getUser($uid);
$userArray = $user['email'];
$userArray = explode("@", $userArray);
$usr = explode(".", $userArray[0]);
$str = str_replace(".", " ", $userArray[0]);
$str = ucwords($str);
?>
<script type="text/javascript" src="../js/alertify/alertify.js"></script>
<script type="text/javascript" src="../js/alertify/alertify.min.js"></script>
<link rel="stylesheet" href="../css/alertify/alertify.min.css" />
<!-- Default theme -->
<link rel="stylesheet" href="../css/alertify/default.min.css" />
<!-- Semantic UI theme -->
<link rel="stylesheet" href="../css/alertify/semantic.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<script src="../controllers/sessionExpires.js"></script>
<script type="text/javascript"
	src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>


<!-- START PAGE SIDEBAR -->
<div id="sideNavBar" class="page-sidebar page-sidebar-fixed scroll">
	<!-- START X-NAVIGATION -->
	<ul class="x-navigation">
		<li class="xn-logo"><a href="#"
			style="font-size: 18px; padding-top: 3px;"><img
				src="../img/thales.png"
				style="height: 24px; padding-bottom: 5px; width: 120px;" /><br />BITE
				Analytics</a> <a href="#" class="x-navigation-control"></a></li>
		<li class="xn-profile"><a href="#" class="profile-mini"><img
				src="../img/globe-icon.png" alt="BITE Analytics" /> </a>
			<div class="profile">
				<div class="profile-image">
					<div class="icon-preview" style="height: 60px; line-height: 1px;">
						<i class="fa fa-user"></i>
					</div>
				</div>
				<div class="profile-data">
					<div class="profile-data-name">
					<?php echo $str;?>
					</div>

					<div class="dropdown">
						<a class="dropdown-toggle" href="#" data-toggle="dropdown"
							id="profileDropdown" style="text-decoration: none;"><div
								style="text-align: center">
								<?php echo $user['role'];?>
								<b class="caret"> </b>
							</div> </a>
						<ul class="dropdown-menu animated flipInX m-t-xs" id="profilemenu"
							style="display: none;">
							<li><a href="profile.php"><span class="fa fa-user"></span>Profile</a></li>
							<li><a href="ChangeUserPassword.php"><span class="fa fa-cog"></span>Change
									Password</a></li>
							<li><a href="#" class="mb-control" data-box="#mb-signout"><span
									class="fa fa-sign-out"></span>Logout</a></li>
						</ul>
					</div>
				</div>
			</div></li>
		<!-- <li class="xn-title">Navigation</li> -->
		<li <?php if($menu =='USERS'){echo 'class="active"';}?>><a
			href="Users.php"><span class="fa fa-users"></span><span
				class="xn-text">Users</span></a></li>
		<li <?php if($menu =='AIRLINES'){echo 'class="active"';}?>><a
			href="Airlines.php"><span class="fa fa-plane"></span><span
				class="xn-text">Airlines</span></a></li>
		<li <?php if($menu =='AIRCRAFTS'){echo 'class="active"';}?>><a
			href="Aircrafts.php"><span class="fa fa-fighter-jet"></span><span
				class="xn-text">Aircrafts</span></a></li>
		<li <?php if($menu =='UPLOAD_WIRING_DATA'){echo 'class="active"';}?>>
			<a href="UploadWiringData.php"><span
					class="fa fa-upload"></span><span class="xn-text">Upload Wiring
						Data</span></a></li>
		<li <?php if($menu =='LRU_COUNT'){echo 'class="active"';}?>><a
			href="LRUCount.php"><span class="fa fa-desktop"></span><span
				class="xn-text">LRU Count</span></a></li>
		<li></li>
	</ul>
	<!-- END X-NAVIGATION -->
</div>
<!-- END PAGE SIDEBAR -->

<script>
$("#profileDropdown").on("click", function() {
	var el = document.getElementById('profilemenu');
	if(el.style.display=='block'){
		el.style.display = 'none';
	}else{
		el.style.display = 'block';
	}
});
$(document).click(function(){                    		  
	  var el = document.getElementById('profilemenu');
		if(el.style.display=='block'){
			el.style.display = 'none';
    	}
	});

</script>
