<?php
ini_set ( 'memory_limit', '-1' );
ini_set ( 'max_execution_time', 300 );
date_default_timezone_set ( "GMT" );
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once("validateUser.php");
// $approvedRoles = [$roles["engineer"]];
// $auth->checkPermission($hash, $approvedRoles);

//$myfile = fopen("reportDebug.txt", "w");
$cache_life = 60; // caching time, in seconds
$download = false;
if (! isset ( $_REQUEST ['url'] )) {
	exit ();
}
$url = $_REQUEST ['url'];
// echo "url--$url";
// Get database information
// Get database information
// $mainDB = $database;
$db = $mainDB;
$aircraftId = $_REQUEST ['aircraftId'];
$sqlDump = $_REQUEST ['db']; // sqlDump.
$tailSign = $_REQUEST ['tailSign']; // For sqlDump report.
                                  // echo ">>>>>>>>$aircraftId>>>>>>>";;
                                  
error_log('Aircraft Id : '.$aircraftId)  ;
if ($aircraftId != '') {
	$selected = mysqli_select_db ( $GLOBALS ['dbConnection'], $mainDB ) or die ( "Could not select " . $mainDB );
} else {
	$selected = mysqli_select_db ( $GLOBALS ['dbConnection'], $sqlDump ) or // Modified $db to $sqlDump
die ( "Could not select " . $sqlDump );
}

// Get information to display in header
if ($aircraftId != '') {
	$query = "SELECT a.tailsign, a.databaseName, b.id, b.name FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
	// echo ">>>>$query>>>";
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	
	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_array ( $result );
		// $airlineId = $row['id'];
		// $airline = $row['name'];
		$aircraft = $row ['tailsign'];
		$dbName = $row ['databaseName'];
		// echo "$aircraft------$dbName";
	} else {
		echo "error: " . mysqli_error ( $GLOBALS ['dbConnection'], $db );
	}
} else if ($sqlDump != '') {
	$dbName = $sqlDump;
	$query = "SELECT a.tailsign, b.id, b.name, b.acronym FROM $db.aircrafts a, $db.airlines b WHERE a.tailsign = '$tailSign' AND a.airlineId= b.id";
	// echo ">>>>$query>>>";
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_array ( $result );
		// $airlineId = $row['id'];
		// $airline = $row['name'];
		$aircraft = $row ['tailsign'];
		$airlinesName = $row ['name'];
		$airlinesAcronym = $row ['acronym'];
		// echo "$aircraft------$dbName";
	} else {
		echo "error: " . mysqli_error ( $GLOBALS ['dbConnection'], $db );
	}
} else {
}
// echo "<<<<<$isTimeLine<<<<<";
$url = trim ( urldecode ( $url ) );
if ($url == '') {
	exit ();
}

// CURRENTLY I am using local Machine so I used Localhost as a host Name.
// changed hardcoded environment directory to curent environment in $url 
if (! stristr ( $url, 'http://' ) and ! stristr ( $url, 'https://' )) {
   // echo "Test:";
	$url = 'http://localhost/'.basename(dirname(__DIR__)).'/common/' . $url;
	// $url = 'http://10.76.108.177:8080/bite_analytics_test/' . $url;
}

error_log("----URL----$url");
$url_segs = parse_url ( $url );
if (! isset ( $url_segs ['host'] )) {
	exit ();
}

$here = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR;
$bin_files = $here . 'bin' . DIRECTORY_SEPARATOR;
$jobs = $here . 'jobs' . DIRECTORY_SEPARATOR;
$cache = $here . 'cache' . DIRECTORY_SEPARATOR;
// echo ">>>>>$cache>>>>";

if (! is_dir ( $jobs )) {
	mkdir ( $jobs );
	file_put_contents ( $jobs . 'index_1.php', '<?php exit(); ?>' );
}
if (! is_dir ( $cache )) {
	mkdir ( $cache );
	file_put_contents ( $cache . 'index_1.php', '<?php exit(); ?>' );
}

$w = 900;
$h = 350;

if (isset ( $_REQUEST ['w'] )) {
	$w = intval ( $_REQUEST ['w'] );
}

if (isset ( $_REQUEST ['h'] )) {
	$h = intval ( $_REQUEST ['h'] );
}

if (isset ( $_REQUEST ['clipw'] )) {
	$clipw = intval ( $_REQUEST ['clipw'] );
}

if (isset ( $_REQUEST ['cliph'] )) {
	$cliph = intval ( $_REQUEST ['cliph'] );
	// echo "->>>>$cliph---";
}

if (isset ( $_REQUEST ['download'] )) {
	$download = $_REQUEST ['download'];
}

$url = strip_tags ( $url );
$url = str_replace ( ';', '', $url );
$url = str_replace ( '"', '', $url );
$url = str_replace ( '\'', '/', $url );
$url = str_replace ( '<?', '', $url );
$url = str_replace ( '<?', '', $url );
$url = str_replace ( '\077', ' ', $url );

// $cache1 = "/opt/lampp/htdocs/bite_analytics/reports/img/";
/* Create the file name */
// $screen_file = $url_segs['host'] . crc32($url) . '_' . $w . '_' . $h . '.jpg';

/*
 * if ($isTimeLine) {
 * $screen_file ='FlightTimeLine'.'.jpg';
 *
 * }else {
 * $screen_file ='SeatReset'.'.jpg';
 * }
 */
// $screen_file =$dbName.'.jpg';

if ($aircraftId != '') {
	$screen_file = $dbName . '.jpg';
	$cache_job = $here . "../reports/img/" . $screen_file;
} else {
	$screen_file = $airlinesAcronym . '_' . str_replace ( "-", "_", $tailSign ) . '.jpg';
	$cache_job = $here . "../reports/imgForSql/" . $screen_file;
}

$refresh = false;
if (is_file ( $cache_job )) {
	$filemtime = @filemtime ( $cache_job ); // returns FALSE if file does not exist	
	$timediff = time() - $filemtime;
	if (! $filemtime or (time () - $filemtime >= $cache_life)) {
		$refresh = true;
	}
}

// NOT COMPATIBLE WITH WINDOWS / OK WITH LINUS
//$url = escapeshellcmd ( $url );
$is_f = is_file($cache_job);
if (! is_file ( $cache_job ) or $refresh == true) {
	echo "create\n";
	$src = "

    var page = require('webpage').create();

    page.viewportSize = { width: {$w}, height: {$h} };

    ";
	
	if (isset ( $clipw ) && isset ( $cliph )) {
		$src .= "page.clipRect = { top: 50, left: 0, width: {$clipw}, height: {$cliph} };";
	}
	//echo "bad $url\n";
	$src .= "

    page.open('{$url}', function () {
        page.render('{$screen_file}');
        phantom.exit();
    });


    ";
	
	$job_file = $jobs . $url_segs ['host'] . crc32 ( $src ) . '.js';
	// echo "$job_file\n";
	$result = file_put_contents ( $job_file, $src );
	// echo "$result\n";
	
	$exec = $bin_files . 'phantomjs ' . $job_file;
	 //echo "$exec\n";
	
	$escaped_command = escapeshellcmd ( $exec );
	
	echo "command to execute: $escaped_command";
	$execResult = exec ( $escaped_command );
	echo "exec result => $execResult\n";
	echo "$here" . "$screen_file === $cache_job\n\n";
	if (is_file ( $here . $screen_file )) {
		rename ( $here . $screen_file, $cache_job );
	}
}
if (is_file ( $cache_job )) {
	if ($download != false) {
		$file = $cache_job;
		$file_name = basename ( $file );
		$type = 'image/jpeg';
		header ( "Content-disposition: attachment; filename={$file_name}" );
		header ( "Content-type: {$type}" );
		readfile ( $file );
	} else {
		$file = $cache_job;
		$type = 'image/jpeg';
		header ( 'Content-Type:' . $type );
		header ( 'Content-Length: ' . filesize ( $file ) );
		readfile ( $file );
	}
}
//fclose($myfile);




 
