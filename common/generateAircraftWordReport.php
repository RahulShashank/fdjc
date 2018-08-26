<?php
ini_set ( 'max_execution_time', 300 );
date_default_timezone_set ( "GMT" );

include ("../database/connecti_database.php");
include ("../common/functions.php");

require_once('../engineering/checkEngineeringPermission.php');

require_once "../common/flightSeatsDetails.php";
// require_once "../common/computeFlightStatus.php";
// include ($_SERVER["DOCUMENT_ROOT"] . "/common/computeFlightStatus.php");
// include(dirname(__FILE__) . '/common/computeFlightStatus.php');
// include(realpath(dirname(__FILE__).'/../common/computeFlightStatus.php'));

include __DIR__ . '/../common/computeFlightStatus.php';
//include __DIR__ . '/../common/computeFleetStatusData.php';

static $globalCounter = 0;

$db = $mainDB;
// echo "DBNAME:- $db";
$dbName = '';
$aircraftId = $_GET ['aircraftId'];
$startDateTime = $_GET ['startDateTime'];
$endDateTime = $_GET ['endDateTime'];
$sqlDump = $_GET ['db'];
$tailSign = $_GET ['tailSign']; // For
                                // echo "startDateTime>>>>>>>".$startDateTime. "---endDateTime>>>>>>".$endDateTime .">>>>>";
                                // echo "---->>>$tailSign ----->>>>";
                                // $selected = mysqli_select_db ($dbConnection, $mainDB) or die ( "Could not select " . $mainDB );
                                // echo "TimeLine";
if ($aircraftId != '') {
	$selected = mysqli_select_db ( $dbConnection, $mainDB ) or die ( "Could not select " . $mainDB );
} else { // Modified code for $sqlDump
	$selected = mysqli_select_db ( $dbConnection, $sqlDump ) or die ( "Could not select " . $sqlDump );
}
// Get aircraft information
if ($aircraftId != '') {
	//$query = "SELECT a.tailsign, a.platform, a.software, a.msn, a.type, b.name, b.acronym, a.databaseName FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId = b.id";
	$query = "SELECT a.tailsign, a.platform, a.software, a.msn, a.type, b.name, b.acronym, a.databaseName, c.firstClassSeats, c.businessClassSeats, c.totalEconomyClassSeats FROM aircrafts a INNER JOIN airlines b ON (a.airlineId = b.id) LEFT JOIN aircraft_seatinfo c ON ( a.aircraftConfigId = c.id ) WHERE a.id = $aircraftId ";
} else {
	/**
	 * It's for sqlDump report.
	 */
	$dbName = $sqlDump;
	$tailSign = str_replace ( " ", "", $tailSign ); // Remove space
	$query = "SELECT a.tailsign, a.platform, a.software, a.msn, a.type, b.name, b.acronym, a.databaseName FROM $db.aircrafts a, $db.airlines b WHERE a.tailsign = '$tailSign' AND a.airlineId = b.id";
	// echo "--->>>> $query ---->>>>";
}

$result = mysqli_query ( $dbConnection, $query );

if ($result && mysqli_num_rows ( $result ) > 0) {
	$row = mysqli_fetch_array ( $result );
	$aircraft = $row ['tailsign'];
	$pageTitle = "Generate report for $aircraft";
	$pageIcon = "document.png";
	
	$airline = $row ['name'];
	$acronym = $row ['acronym'];
	$platform = $row ['platform'];
	$software = $row ['software'];
	$tailsign = $aircraft;
	$msn = $row ['msn'];
	$actype = $row ['type'];
	$fClassSeats = $row ['firstClassSeats'];
	$bClassSeats = $row ['businessClassSeats'];
	$totalEconomyClassSeats = $row ['totalEconomyClassSeats'];  
	// $eClassSeats = $row['economyClassSeats'];
	// echo ">>>>>First:-$eClassSeats>>>>>";
	if ($aircraftId != '') {
		$dbName = $row ['databaseName'];
	}
} else {
	echo "error: " . mysqli_error ( $dbConnection ) . " / query: $query";
}

/*
 // Retriving Aircraft seats information 
if($actype != '') {
	$queryForSeatDetails = "SELECT a.firstClassSeats,a.businessClassSeats,a.totalEconomyClassSeats FROM $db.aircraft_seatinfo a WHERE a.aircraftType = '$actype' ";
	//echo "--->>>$queryForSeatDetails-->>>";
	
	$resultForSeatDetails_Query = mysqli_query ( $dbConnection, $queryForSeatDetails );
	
	if ($resultForSeatDetails_Query) {
		if(mysqli_num_rows ( $resultForSeatDetails_Query ) > 0){
			$row = mysqli_fetch_array ( $resultForSeatDetails_Query );
		
			$fClassSeats = $row ['firstClassSeats'];
			$bClassSeats = $row ['businessClassSeats'];
			$totalEconomyClassSeats = $row ['totalEconomyClassSeats'];
		}
	}else {
		echo "error: " . mysqli_error ( $dbConnection ) . " / query: $queryForSeatDetails";
	}
}
 
*/


// NEED TO HANDLE CASES WHERE TIMES ARE EMPTY!!!
if ($startDateTime == '') {
	// $query = "SELECT createDate FROM sys_flight ORDER BY createDate ASC LIMIT 1";
	// $result = mysql_query($query);
	// $row = mysql_fetch_array($result);
	// $start = $row['createDate'];
} else {
	$d = strtotime ( $startDateTime );
	$start = date ( "Y-m-d H:i:s", $d );
}
if ($endDateTime == '') {
	// $query = "SELECT createDate FROM sys_flight ORDER BY createDate DESC LIMIT 1";
	// $result = mysql_query($query);
	// $row = mysql_fetch_array($result);
	// $end = $row['createDate'];
} else {
	$d = strtotime ( $endDateTime );
	$end = date ( "Y-m-d H:i:s", $d );
}
// echo "mysqlDailyStart>>>>>>>".$start. "---mysqlDailyEnd>>>>>>".$end .">>>>>";
// Select aircraft database
$selected = mysqli_select_db ( $dbConnection, $dbName ) or die ( "Could not select " . $dbName );

$dailyEnd = strtotime ( $end );
$dailyStart = strtotime ( '-2 day', $dailyEnd );
$mysqlDailyEnd = date ( "Y-m-d H:i:s", $dailyEnd );
$mysqlDailyStart = date ( "Y-m-d H:i:s", $dailyStart );
// echo "mysqlDailyStart>>>>>>>".$mysqlDailyStart. "---mysqlDailyEnd>>>>>>".$mysqlDailyEnd .">>>>>";

// echo $dbName;
// Create Word document
require_once '../src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register ();

// Create font styles
$sectionFontStyle = array (
		'name' => 'Calibri',
		'size' => 11,
		'bold' => true,
		'underline' => 'single' 
);
$titleFontStyle = array (
		'name' => 'Calibri',
		'size' => 11,
		'bold' => true 
);
$flightstatusFontStyle = array (
        'name' => 'Calibri',
		'size' => 12,
		'color' => 'FF0000'
);
$titleFontStyleForSeats = array (
		'name' => 'Calibri',
		'size' => 12,
		'bold' => true 
		
);
$textFontStyle = array (
		'name' => 'Calibri',
		'size' => 11 
);
$italicFontStyle = array (
		'name' => 'Calibri',
		'size' => 11 
);
$caFontStyle = array (
		'name' => 'Calibri',
		'size' => 11,
		'underline' => 'single' 
);
$tableStyle = array (
		'borderColor' => '000000',
		'borderSize' => 6,
		'cellMargin' => 50 
);
$sectionStyle = array (
		'orientation' => 'landscape',
		'marginTop' => 600,
		'colsNum' => 2 
);
// Creating the new document...
$phpWord = new \PhpOffice\PhpWord\PhpWord ();

// Add style for table
$firstRowStyle = array (
		'bgColor' => 'FFFFFF' 
);
$phpWord->addTableStyle ( 'Details Table', $tableStyle, $firstRowStyle );

// $section = $phpWord->addSection(array('orientation'=>'landscape'));
$section = $phpWord->addSection ();
// header for 1st page
$header = $section->addHeader ();
$header->firstPage ();
$table = $header->addTable ();
$table->addRow ();
$cell = $table->addCell ( 6000 )->addImage ( '../img/thales.png', array (
		'width' => 173,
		'height' => 29,
		'valign' => 'center',
		'align' => 'left' 
) );
$cell2 = $table->addCell ( 4500, array (
		'valign' => 'center',
		'align' => 'right' 
) );
$cell2->addText ( htmlspecialchars ( 'BITE OFFLOAD ANALYSIS' ), array (
		'bold' => true,
		'align' => 'right',
		'color' => '001897',
		'name' => 'Arial',
		'size' => 14 
) );
// header for other pages
$header = $section->addHeader ();
$table = $header->addTable ();
$table->addRow ();
$cell = $table->addCell ( 6000 )->addImage ( '../img/thales.png', array (
		'width' => 173,
		'height' => 29,
		'valign' => 'center',
		'align' => 'left' 
) );
$cell2 = $table->addCell ( 4500, array (
		'valign' => 'center',
		'align' => 'right' 
) );
$cell2->addText ( htmlspecialchars ( 'BITE OFFLOAD ANALYSIS' ), array (
		'bold' => true,
		'align' => 'right',
		'color' => '001897',
		'name' => 'Arial',
		'size' => 14 
) );

// footer for 1st page
$footer = $section->addFooter ();
$footer->firstPage ();
$footer->addPreserveText ( htmlspecialchars ( 'Page {PAGE} of {NUMPAGES}.' ) );

// footer for other pages
$footer = $section->addFooter ();
$footer->addPreserveText ( htmlspecialchars ( 'Page {PAGE} of {NUMPAGES}.' ) );

// start main page
$section->addTextBreak ( 2, $textFontStyle );
// Customer Information
$section->addText ( htmlspecialchars ( 'CUSTOMER INFORMATION:' ), $sectionFontStyle );

// $section_1->addTextBreak ( 1, $textFontStyle );

$table = $section->addTable ();

$table->addRow ();
$cell = $table->addCell ( 2500 );
$cell->addText ( htmlspecialchars ( 'Airline: ' ), $titleFontStyle );
$cell = $table->addCell ( 4000 );
$cell->addText ( htmlspecialchars ( $airline ), $textFontStyle );
$cell = $table->addCell ( 1250 );
$cell->addText ( htmlspecialchars ( 'IFE System: ' ), $titleFontStyle );
$cell = $table->addCell ( 4000 );
$cell->addText ( htmlspecialchars ( $platform ), $textFontStyle );

$table->addRow ();
$cell = $table->addCell ( 2500 );
$cell->addText ( htmlspecialchars ( 'Tail Number / MSN: ' ), $titleFontStyle );
$cell = $table->addCell ( 4000 );
$cell->addText ( htmlspecialchars ( $tailsign . " / " . $msn ), $textFontStyle );
$cell = $table->addCell ( 1250 );
$cell->addText ( htmlspecialchars ( 'Software: ' ), $titleFontStyle );
$cell = $table->addCell ( 4000 );
$cell->addText ( htmlspecialchars ( $software ), $textFontStyle );

$table->addRow ();
$cell = $table->addCell ( 2500 );
$cell->addText ( htmlspecialchars ( 'Aircraft type: ' ), $titleFontStyle );
$cell = $table->addCell ( 4000 );
$cell->addText ( htmlspecialchars ( $actype ), $textFontStyle );

// Daily report section
// calculate dates


// echo ">>>>>>>".$mysqlDailyStart. ">>>>>>".$mysqlDailyEnd .">>>>>";
$section->addTextBreak ( 3, $textFontStyle );
// $section_1->addPageBreak ( 1, $textFontStyle );
$myTextElement = $section->addText ( htmlspecialchars ( 'Daily report for ' . date ( "Y-m-d", $dailyStart ) . ' and ' . date ( "Y-m-d", $dailyEnd ) ), $sectionFontStyle );
$section->addTextBreak ( 1, $textFontStyle );

/**
 * Displaying picture of timeLine
 * $cell = $table->addCell ( 900 );
 */
$imageStyle = array (
		'width' => 650,
		'height' => 350 
);

/*
 * $section_1->addImage(
 * 'img/FlightTimeLine.jpg',$imageStyle);
 */
/**
 * Add Image in the word report.
 */
if ($aircraftId != '') {
	$image = '../reports/img/' . $dbName . '.jpg';
} else {
	$imageName = $acronym . '_' . str_replace ( "-", "_", $tailSign );
	$image = '../reports/imgForSql/' . $imageName . '.jpg';
}

error_log("Image: " . $image);
$section->addImage ( $image, $imageStyle );

// $section_1->addTextBreak ( 1, $textFontStyle );

// Daily report section
// calculate dates
/*
 * $dailyEnd = strtotime ( $end );
 * $dailyStart = strtotime ( '-1 day', $dailyEnd );
 * $mysqlDailyEnd = date ( "Y-m-d H:i:s", $dailyEnd );
 * $mysqlDailyStart = date ( "Y-m-d H:i:s", $dailyStart );
 */

// Faults in cruise mode Section
/**
 * Displaying all faults 400 occurring in cruise mode
 */
$section_1 = $phpWord->addSection ( array (
		'orientation' => 'landscape' 
) );

/**
 * Displaying all faults 400, 404, 406, 420231, 440211 and 42007003 occurring in cruise mode
 * ******** I didn't find fault Description for faults "404" and "406" in the DATABASE( sys_faultinfo Table)  ********* So now those two faults contains default Description 
 */
$faultCodesWithDescForDailyReport = array (
		"400" => array (
				' Lost communications to LRU Host error still active or greater than 15 minutes (in cruise mode)',
				' Lost communications to LRU Host error with more than 5 fault less than 15 minutes (in cruise mode)' 
		),
		"404" => array (
				' still active or greater than 15 minutes (in cruise mode)',
				' with more than 5 fault less than 15 minutes (in cruise mode)' 
		),
		"406" => array (
				' still active or greater than 15 minutes (in cruise mode)',
				' with more than 5 fault less than 15 minutes (in cruise mode)' 
		),
		"420231" => array (
				' SVDU-G4 failed to mount the SDXC error still active or greater than 15 minutes (in cruise mode)',
				' SDXC error with more than 5 fault less than 15 minutes (in cruise mode)' 
		),
		"440211" => array (
				' Ethernet link error still active or greater than 15 minutes (in cruise mode)',
				' Ethernet link error with more than 5 fault less than 15 minutes (in cruise mode)' 
		),
		"42007003" => array (
				' Communication error between the Server and Client still active or greater than 15 minutes (in cruise mode)',
				' Communication error between the Server and Client with more than 5 fault less than 15 minutes (in cruise mode)' 
		)
);

// Only consider 400, 440211 for i5000 and i8000
if($platform=='i5000' or $platform=='i8000'){
	unset($faultCodesWithDescForDailyReport['404']);
	unset($faultCodesWithDescForDailyReport['406']);
	unset($faultCodesWithDescForDailyReport['420231']);
}


generateParagraphInDailySectionForFaultsInCruise ( $faultCodesWithDescForDailyReport );
$section_1->addTextBreak ( 2, $textFontStyle );

$section = $phpWord->addSection ();

/**
 * Start main page
 */
$section->addTextBreak ( 2, $textFontStyle );
/**
 * As per requirement I remeoved Failure recorded in cruise Mode.
 */

/*
 * $section_1->addPageBreak ( 1, $textFontStyle );
 * generateParagraphForDailyReport ( $section_1, $titleFontStyle, $textFontStyle, $mysqlDailyStart, $mysqlDailyEnd );
 */

/**
 *
 * @var CUSTOMER REPORTED PROBLEM
 */

$myTextElement = $section->addText ( htmlspecialchars ( 'CUSTOMER REPORTED PROBLEM' ), $sectionFontStyle );
$section->addTextBreak ( 1, $textFontStyle );

if ($startDateTime == '') {
	$query = "SELECT createDate FROM SYS_flight ORDER BY createDate ASC LIMIT 1";
	$result = mysqli_query ( $dbConnection, $query );
	$row = mysqli_fetch_array ( $result );
	$start = $row ['createDate'];
} else {
	$d = strtotime ( $startDateTime );
	$start = date ( "Y-m-d H:i:s", $d );
}
if ($endDateTime == '') {
	$query = "SELECT createDate FROM SYS_flight ORDER BY createDate DESC LIMIT 1";
	$result = mysqli_query ( $dbConnection, $query );
	$row = mysqli_fetch_array ( $result );
	$end = $row ['createDate'];
} else {
	$d = strtotime ( $endDateTime );
	$end = date ( "Y-m-d H:i:s", $d );
}

$section->addText ( htmlspecialchars ( 'BITE data review for the period from ' . $start . ' to ' . $end . '.' ), $textFontStyle );

$section->addTextBreak ( 2, $textFontStyle );
$myTextElement = $section->addText ( htmlspecialchars ( 'Observations and recommendations:' ), $sectionFontStyle );
$section->addTextBreak ( 1, $textFontStyle );

/**
 * AVANT TEMPLATE
 */

if ($platform == 'AVANT') {
	$lrusList = array ();
	
	/**
	 * SVDU performance
	 */
	
	$text = "Check performance of below SVDUs due to high number of reported communication failures";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10042400001, 20 );
	$lrusList = array_merge ( $lrusList, $lrus );
	
	/**
	 * SVDU POST Failures
	 */
	
	$text = "Check performance of below SVDUs due to high number of reported POST failures";
	$lrus = generateParagraphForPostFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10042000101, 2 );
	// getFaultsforFailure($dbhandle, $section, $textFontStyle, $lrus, 10042000101);
	// getFaultsforPostFailure($dbhandle, $section, $textFontStyle, $lrus, 10042000101);
	// echo "<br>--test";
	$lrusList = array_merge ( $lrusList, $lrus );
	
	/**
	 * SVDU performance, config check, dispfac
	 * As per requirement I have commented it.
	 */
	
	/*
	 * $text = "Verify performance, config check and/or dispfac of below SVDUs to confirm if it properly reports due to high number of reported H/W failures";
	 * $lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', '10042048001, 10042049001', 20 );
	 * $lrusList = array_merge ( $lrusList, $lrus );
	 */
	
	/**
	 * SVDU performance, wiring
	 */
	
	/*
	 * $text = "Verify performance and wiring of below SVDUs due to high number of reported link failures";
	 * $lrus = generateParagraphForFailure($section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10042209001, 30);
	 * $lrusList = array_merge($lrusList,$lrus);
	 */
	
	/**
	 * SVDU SDXC Card not present on SVDU-G4 error
	 * Dispaly SVDU with active failure 10042228001=1.
	 */
	
	$text = "Check performance of below SVDUs due to SDXC Card not present on SVDU-G4 error report";
	$lrus = generateActiveFailureParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10042228001, 1 ); // As per requirement Modified Failure count check 5 to 1.
	$lrusList = array_merge ( $lrusList, $lrus );
	
	/**
	 * SVDU performance, config check, dispfac #2
	 */
	
	$text = "Verify performance, config check and/or dispfac of below SVDUs to confirm if it properly reports due to high number of reported S/W P/N failures";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10042047001, 100 ); // Modified 20 to 100.
	$lrusList = array_merge ( $lrusList, $lrus );
	
	/**
	 * TPMU performance
	 * Dispaly TPMU with active failure 10041400001>20.
	 */
	
	$text = "Check performance of below TMPUs due to high number of reported communication failures";
	generateActiveFailureParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'TPMU', 10041400001, 20 );
	
	/**
	 * TPMU performance, config check, dispfac HW
	 * As per requirement I have commented it.
	 */
	
	/*
	 * $text = "Verify performance, config check and/or dispfac of below TPMUs to confirm if it properly reports due to high number of reported H/W failures";
	 * generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'TPMU', 10041049001, 20 );
	 */
	
	/**
	 * TPMU performance, config check, dispfac SW PN
	 * Dispaly TPMU with active failures 10041047001 and/or 1004105001 and/or 10041046001=5.
	 */
	
	$text = "Verify performance, config check and/or dispfac of below TPMUs to confirm if it properly reports due to high number of reported S/W P/N failures";
	generateActiveFailureParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'TPMU', '10041047001, 1004105001, 10041046001', 5 ); // As per requirement Modified Failure count check 20 to 5.
	
	/**
	 * SPB performance, stability
	 * Dispaly SPB with active failure 10423040001>20.
	 */
	
	$text = "Check performance/stability and associated RPO functioning of below SPB due to high number of reported status error";
	generateActiveFailureParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SPB', 10423040001, 20 );
	
	/**
	 * Power Module
	 */
	
	$text = "Check status and stability of below RPO due to high number of reported loss of communication failures by the associated Power Module";
	generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, '', '10423090001, 10422240001', 20 );
	
	/**
	 * Camera
	 */
	
	$text = "Check Camera due to high number of reported communication failures";
	generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'CAMERA', '10045302001, 10422240001', 15 );
	
	/**
	 * Printer
	 */
	
	$text = "Check Printer due to high number of reported communication failures";
	generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'PRINTER', 10044305001, 100 ); // Modified 20 to 100.
	
	//Newly Added..[Added New Fault Codes].
	/**
	 * SVDU-G4 failed to mount the SDXC error.
	 */
	$text = "Check performance of below SVDU due to SVDU-G4 failed to mount the SDXC errors report";
	generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 420231 , 1,'3' );
	
	/**
	 * SVDU link status error reported
	*/
	$text = "Check status of below SVDU due to high number of link status error reported";
	generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 420209 , 30,'3' );
	
	/**
	 * SVDU communication ERROR reported between the Server  and Client
	*/
	$text = "Check performance of below SVDU due to high number of communication ERROR reported between the Server and Client";
	generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 42007003 , 1,'3' );
	
	
	$section->addPageBreak ( 1, $textFontStyle );
	$myTextElement = $section->addText ( htmlspecialchars ( 'Seat Reset Comparison' ), $sectionFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	/**
	 * Above svdus seat reset stats
	 * make sure we have unique keys before creating that paragraph
	 */
	$lrusList = array_unique ( $lrusList );
	$text = "Above reported SVDUs reset count for that period";
	generateParagraphForUncommandedCommandedResets ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, $lrusList );
	
	/**
	 * High seat resets
	 */
	$text = "High count of seat resets";
	generateParagraphForCommandedResets ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 5 );
	
	/**
	 * additional observation section
	 */
	$section->addPageBreak ( 1, $textFontStyle );
	$myTextElement = $section->addText ( htmlspecialchars ( 'Additional observations' ), $sectionFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	/**
	 * SVDU failover faults
	 */
	$text = "List of SVDU communication fault event";
	generateParagraphForExtAppEvent ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 42007003, 0 );
	
	// SVDU failover
	// $text = "List of SVDU failover events";
	// generateParagraphForExtAppEvent($section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 42007008, 10);
	
	// SVDU buffer faults
	// $text = "List of SVDU buffer events";
	// generateParagraphForExtAppEvent($section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 42007005, 50);
	
	/**
	 * DSU offline
	 */
	$text = "List of offline DSU occurrences";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'DSU', 10044400001, 0 );
	if (count ( $lrus ) > 0) {
		generateParagraphForFailureDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, 'DSU', 10044400001 );
	}
	
	/**
	 * DSU process crash
	 */
	$text = "List of offline DSU with process crash";
	generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'DSU', 440304, 0,'1,3' );
	
	/**
	 * ADBG stability.
	 */
	$text = "Verify below ADBGs stability due to high number of reported loss of communication failures";
	generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'ADBG', 10025400001, 25 );
	
	/**
	 * LAIC offline
	 */
	$text = "List of LAIC offline occurrences";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'LAIC', 10045400001, 0 );
	if (count ( $lrus ) > 0) {
		generateParagraphForFailureDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, 'LAIC', 10045400001 );
	}
	
	/**
	 * Newly Added in the Report
	 * // CIDS Failure
	 */
	$text = "List of CIDS Failure occurrences";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'CIDS', 10044306002, 0 );
	if (count ( $lrus ) > 0) {
		generateParagraphForFailureDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, 'CIDS', 10044306002 );
	}
	
	/**
	 * ICMT Process restart.
	 */
	$text = "List of ICMT Process restart";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'ICMT', 10043308001, 0 );
	if (count ( $lrus ) > 0) {
		generateParagraphForFailureDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, 'ICMT', 10043308001 );
	}
	
	/**
	 * ICMT Offline.
	 */
	$text = "List of ICMT Offline occurrences";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'ICMT', 10043400001, 0 );
	if (count ( $lrus ) > 0) {
		generateParagraphForFailureDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, 'ICMT', 10043400001 );
	}
	
	
	
	
} // End of Avant.

/**
 * ********************** I5000 TEMPLATE **************
 * Platform I5000. 
 * Because I5000 Template is diffrent from AVANT,Current i5000 Template doesn't contains Dialy Section.*******
 */
 // Added OR condition for I8000
if ($platform == 'i5000' || $platform == 'i8000') {
	
	$lrusList = array ();
	
	/**
	 * TPMU performance
	 * Dispaly TPMU with active failure 10041400001>=10.
	 */
	
	$text = "Check below TPMU due to active loss of communication failures reported";
	generateActiveFailureParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'TPMU', 10041400001, 10 );
	
	/**
	 * TPMU faults
	 * Display any TPMU with faults 410101,410102, 410100, 410103, 410104, 410105, 410106, 410107, 410108, 410109, 410215 reported.
	 */
	$text = "Check below TPMU due to LCD and touchscreen faults reported";
	generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'TPMU', '410101, 410102, 410100, 410103, 410104, 410105, 410106, 410107, 410108, 410109, 410215', 0, '1,3' );
	
	/**
	 * SVDU performance
	 * Display SVDU with failure 10028400001 >= 5
	 */
	
	$text = "Check performance of below SVDUs due to high number of communication failure reported";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10028400001, 5 );
	// $lrusList = array_merge ( $lrusList, $lrus );
	
	/**
	 * SVDU performance
	 * Display QSEB with failure 10026400001 >= 2
	 */
	
	$text = "Check performance of below QSEB due to communication failure reported";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SVDU', 10026400001, 2 );
	// $lrusList = array_merge ( $lrusList, $lrus );
	
	/**
	 * DSU offline
	 * Display list of all failure 10030400001 occurrences
	 */
	$text = "DSU loss of communication failure";
	$lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'DSU', 10030400001, 0 );
	if (count ( $lrus ) > 0) {
		generateParagraphForFailureDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, 'DSU', 10030400001 );
	}
	
	/**
	 * DSU additional faults reported
	 * Display list of all faults(300103,300201,300301,300302,42,44,100,300202,300203,300303,300204,300205,300304,300305,200,300206 )
	 */
	$text = "DSU additional faults reported";
	// $lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'DSU', 10030400001, 0 );
	// if (count ( $lrus ) > 0) {
	$isSingleFault = false;
	//generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'DSU', '300103, 300201, 300301, 300302, 42, 44, 100, 300202, 300203, 300303, 300204, 300205, 300304, 300305, 200, 300206' );
	generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'DSU', '300103, 300201, 300302, 42, 44, 100, 300202, 300203, 300303, 300204, 300205, 300304, 300305, 200, 300206' );
	// }
	
	/**
	 * AVCD loss of communication failure
	 * Display list of all fault 400 occurrences
	 */
	$text = "AVCD loss of communication fault";
	$lrus = generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'AVCD', 400, 0,'1,3' );
	if (count ( $lrus ) > 0) {
		$isSingleFault = true;
		generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'AVCD', 400 );
	}
	
	/**
	 * AVCD additional faults reported
	 * Display list of all faults(240110,240201,240301,240301,240302,240303,240305,240306,240309,240310,240206,42,44,100,200,240203,240202,240315,240315,240316,240318,240204,240205)
	 */
	$text = "AVCD additional faults reported";
	// $lrus = generateParagraphForFailure ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'DSU', 10030400001, 0 );
	// if (count ( $lrus ) > 0) {
	$isSingleFault = false;
	generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'AVCD', '240110, 240201, 240301, 240301, 240302, 240303, 240305, 240306, 240309, 240310, 240206, 42, 44, 100, 200, 240203, 240202, 240315, 240315, 240316, 240318, 240204, 240205' );
	// }
	
	/**
	 * AVCD loss of communication failure
	 * Display list of all fault 400 occurrences
	 */
	$text = "ADBG loss of communication fault";
	$lrus = generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'ADBG', 400, 0,'1,3' );
	if (count ( $lrus ) > 0) {
		$isSingleFault = true;
		generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'ADBG', 400 );
	}

	/**
	 * ICMT loss of communication failure
	 * Display list of all fault 400 occurrences
	 */
	$text = "ICMT loss of communication fault";
	$lrus = generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'ICMT', 400, 0,'1,3' );
	if (count ( $lrus ) > 0) {
		$isSingleFault = true;
		generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'ICMT', 400 );
	}

	/**
	 * SDB loss of communication failure
	 * Display list of all fault 400 occurrences
	 */
	$text = "SDB loss of communication fault";
	$lrus = generateParagraphForFault ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, 'SDB', 400, 0,'1,3' );
	if (count ( $lrus ) > 0) {
		$isSingleFault = true;
		generateParagraphForFaultsDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, 'SDB', 400 );
	}
} // END I5000
  
// Saving the document
$reportName = $acronym . "_" . $tailsign . "_BITE_Report_" . strtok ( $start, " " ) . "_" . strtok ( $end, " " ) . ".docx";
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter ( $phpWord, 'Word2007' );
$objWriter->save ( "../reports/$reportName");
//$objWriter->save ('test.docx');

// Return name of the report as part of the ajax call so we can create the link for download
echo $reportName;

// Utility functions
//Added serialNumber from serialNumber column of respective table
function getQueryForFailure($start, $end, $unitType, $failureCodes, $count, $monitorState) {
	$query = "SELECT a.accusedHostName, COUNT('idFailure') AS 'failures', a.serialNumber 
                FROM BIT_failure a
                WHERE accusedHostName LIKE '$unitType%' 
                    AND correlationDate >= '$start'
                    AND correlationDate <= '$end'
                    AND failureCode IN ($failureCodes) 
                    AND monitorState IN ($monitorState) 
                GROUP BY accusedHostName , a.serialNumber 
                HAVING COUNT('idFailure') >= $count 
                ORDER BY failures DESC";
	
	// echo "<br><br>Query For Failure:---$query---</br></br>";
	return $query;
}
function getQueryForFailureDetails($start, $end, $unitType, $failureCodes) {
	$query = "SELECT accusedHostName, idFlightLeg, monitorState, correlationDate, lastUpdate 
              FROM BIT_failure 
              WHERE accusedHostName LIKE '$unitType%' 
              AND correlationDate >= '$start'
              AND correlationDate <= '$end'
              AND failureCode IN ($failureCodes)";
	
	return $query;
}
/**
 * Newly added for i5000
 *
 * @param unknown $start        	
 * @param unknown $end        	
 * @param unknown $unitType        	
 * @param unknown $faultCodes-DSU
 *        	Fault code like(300103,300201,300301,300302,42,44,100 etc..)
 * @return string
 */
function getQueryForDSUFaultDetails($start, $end, $unitType, $faultCodes) {
	$query = "SELECT hostName,faultCode,idFlightLeg, monitorState, detectionTime, lastUpdate
	          FROM BIT_fault
	          WHERE hostName LIKE '$unitType%'
	          AND detectionTime >= '$start'
			  AND detectionTime <= '$end'
	          AND faultCode IN ($faultCodes)";
	
	// echo "DSUFaultDetails-->>>>>$query--->>>>>>";
	
	return $query;
}
//Added serialNumber from serialNumber column of respective table
function getQueryForFault($start, $end, $unitType, $faultCodes, $count, $monitorState) {
	$query = "SELECT t.hostName,t.faultCode, COUNT('idFault') AS 'failures', t.serialNumber 
                  FROM BIT_fault  t
                  WHERE t.hostName LIKE '$unitType%' 
                  AND detectionTime >= '$start'
		  AND detectionTime <= '$end'
                  AND t.faultCode IN ($faultCodes)
                  AND t.monitorState IN ($monitorState)
                  GROUP BY t.hostName ,t.serialNumber
                  HAVING COUNT('idFault') >= $count 
                  ORDER BY failures DESC";
	// echo "<br><br>---$query---</br></br>";
	
	return $query;
}
// Newly Added
function getQueryForFaultDetails($start, $end, $unitType, $faultCodes, $count) {
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$query = "SELECT hostName, COUNT(bit_t.faultCode) AS 'faults' , faultDesc,bit_t.param1,bit_t.param2,bit_t.param3, bit_t.monitorState,bit_t.detectionTime, bit_t.clearingTime, bit_t.lastUpdate 
                    FROM (
                        SELECT B.hostName, B.faultCode, B.param1,B.param2,B.param3, B.monitorState, B.detectionTime, B.clearingTime, B.lastUpdate
                        FROM BIT_fault B
                        INNER JOIN SYS_flightPhase P
                        ON B.idFlightLeg = b.idFlightLeg
                                          
                        AND P.idFlightPhase = 5 
                        AND B.faultCode = 400
                        AND B.monitorState IN (3,1)
                        AND B.lastUpdate >= P.startTime 
                        AND B.lastUpdate <= P.endTime
                        
                    ) AS bit_t, $commonDbName.sys_faultinfo E
                    WHERE bit_t.faultCode = E.faultCode";
	
	return $query;
}
function getQueryForExtAppEvent($start, $end, $unitType, $faultCodes, $count) {
	$query = "SELECT t.hostName, COUNT('idFault') AS 'failures', t.serialNumber 
                    FROM BIT_extAppEvent t
                    WHERE t.hostName LIKE '$unitType%'
                        AND detectionTime >= '$start'
                        AND detectionTime <= '$end' 
                        AND t.faultCode IN ($faultCodes)                        
                    GROUP BY t.hostName ,t.serialNumber
                    HAVING COUNT('idFault') >= $count 
                    ORDER BY failures DESC";
	
	return $query;
}
function getQueryForUncommandedCommandedResets($start, $end, $unit) {
	$query = "SELECT t.eventData, t.eventName, 
                        SUM(case when eventName in ('UncommandedReboot') then 1 else 0 end) uncommandedCount, 
                        SUM(case when eventName in ('CommandedReboot') then 1 else 0 end) commandedCount, 
                        COUNT(*) AS totalCount 
                    FROM BIT_events t
                    WHERE t.eventData = '$unit' 
                        AND lastUpdate >= '$start'
                        AND lastUpdate <= '$end'
                    GROUP BY t.eventData 
                    ORDER BY totalCount DESC";
	
	return $query;
}
function getQueryForCommandedResets($start, $end, $unitType, $count) {
	$query = "SELECT t.eventData, COUNT('idEvent') AS 'count' 
                    FROM BIT_events t
                    WHERE t.eventData LIKE '$unitType%' 
                        AND eventName = 'CommandedReboot'
                        AND lastUpdate >= '$start'
                        AND lastUpdate <= '$end' 
                    GROUP BY t.eventData 
                    HAVING COUNT('idEvent') >= $count 
                    ORDER BY count DESC";
	
	return $query;
}
/**
 * Newly Added
 * For SDXC AND TPMU S/W P/N Failure Captur at word report only if it's in Active state.
 */
function generateActiveFailureParagraph($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $unitType, $failureCodes, $count, $monitorState = '3') {
	$query = getQueryForFailure ( $start, $end, $unitType, $failureCodes, $count, $monitorState );
	// echo "DBNAME:- ".$GLOBALS['db'];
	// // For debug
	
	return generateParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $query, "accusedHostName", $failureCodes );
}
function generateParagraphForFailure($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $unitType, $failureCodes, $count, $monitorState = '1,3') {
	$query = getQueryForFailure ( $start, $end, $unitType, $failureCodes, $count, $monitorState );
	// echo "DBNAME:- ".$GLOBALS['db'];
	// // For debug
	
	return generateParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $query, "accusedHostName", $failureCodes );
}
/**
 * Newly Added
 * Post Failure.
 */
function generateParagraphForPostFailure($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $unitType, $failureCodes, $count, $monitorState = '1,3') {
	$query = getQueryForFailure ( $start, $end, $unitType, $failureCodes, $count, $monitorState );
	// echo "DBNAME:- ".$GLOBALS['db'];
	// // For debug
	// $section->addText(htmlspecialchars('query: '.$query), $titleFontStyle);
	// $section->addTextBreak(1, $textFontStyle);
	return generateForPostFailureParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $start, $end, "accusedHostName", $failureCodes );
}
function generateParagraphForFailureDetails($dbhandle, $section, $titleFontStyle, $textFontStyle, $start, $end, $unitType, $failureCodes) {
	$query = getQueryForFailureDetails ( $start, $end, $unitType, $failureCodes );
	// // For debug
	
	return generateParagraphForDetails ( $dbhandle, $section, $titleFontStyle, $textFontStyle, $query, "accusedHostName", $failureCodes );
}
/**
 * Newly Added For i5000 report.
 * DSU additional faults reported
 */
function generateParagraphForFaultsDetails($dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $start, $end, $isSingleFault, $unitType, $faultCodes) {
	$query = getQueryForDSUFaultDetails ( $start, $end, $unitType, $faultCodes );
	// // For debug
	// $section->addText(htmlspecialchars('query: '.$query), $titleFontStyle);
	// $section->addTextBreak(1, $textFontStyle);
	return generateFaultParagraphForDetails ( $dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $query, $isSingleFault, "hostName", $faultCodes );
}
function generateParagraphForFault($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $unitType, $faultCodes, $count, $monitorState) {
	$query = getQueryForFault ( $start, $end, $unitType, $faultCodes, $count, $monitorState );
	return generateParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $query, "hostName" );
}
function generateParagraphForExtAppEvent($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $unitType, $faultCodes, $count) {
	$query = getQueryForExtAppEvent ( $start, $end, $unitType, $faultCodes, $count );
	return generateParagraph ( $section, $titleFontStyle, $textFontStyle, $text, $query, "hostName" );
}
function generateParagraphForUncommandedCommandedResets($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $units) {
	static $rawCounter = 1;
	$svdusList = array ();
	foreach ( $units as $unit ) {
		if (strpos ( $unit, 'SVDU' ) === 0) {
			$svdusList [] = $unit;
		}
	}
	
	// echo $query = getQueryForUncommandedCommandedResets($unitsList)."\n";
	// echo var_dump($svdusList)."\n";
	
	$section->addText ( htmlspecialchars ( '- ' . $text . ':' ), $titleFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	if (count ( $svdusList ) > 0) {
		
		$table = $section->addTable ( 'Details Table' );
		$table->addRow ();
		$cell = $table->addCell ( 3000 );
		$cell->addText ( htmlspecialchars ( 'SVDU Name' ), $titleFontStyle );
		$cell = $table->addCell ( 2500 );
		$cell->addText ( htmlspecialchars ( 'Uncommanded resets' ), $titleFontStyle );
		$cell = $table->addCell ( 2500 );
		$cell->addText ( htmlspecialchars ( 'Commanded resets' ), $titleFontStyle );
		$cell = $table->addCell ( 2500 );
		$cell->addText ( htmlspecialchars ( 'Total resets' ), $titleFontStyle );
		
		foreach ( $svdusList as $svdu ) {
			$rawCounter ++;
			$query = getQueryForUncommandedCommandedResets ( $start, $end, $svdu );
			$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
			$rawCountValue = $rawCounter;
			if ($result) {
				$count = mysqli_num_rows ( $result );
				if ($count > 0) {
					
					while ( $row = mysqli_fetch_array ( $result ) ) {
						$unCommandedResets = $row ['uncommandedCount'];
						$commandedResets = $row ['commandedCount'];
						$totalResetsCount = $row ['totalCount'];
						$table->addRow ();
						if ($rawCountValue % 2 == 0) {
							$cell = $table->addCell ( 3000, array (
									'bgColor' => 'C0C0C0' 
							) );
							$cell->addText ( htmlspecialchars ( $svdu ) );
							$cell = $table->addCell ( 2500, array (
									'bgColor' => 'C0C0C0' 
							) );
							$cell->addText ( htmlspecialchars ( $unCommandedResets ) );
							$cell = $table->addCell ( 2500, array (
									'bgColor' => 'C0C0C0' 
							) );
							$cell->addText ( htmlspecialchars ( $commandedResets ) );
							$cell = $table->addCell ( 2500, array (
									'bgColor' => 'C0C0C0' 
							) );
							$cell->addText ( htmlspecialchars ( $totalResetsCount ) );
						} else {
							
							$cell = $table->addCell ( 3000 );
							$cell->addText ( htmlspecialchars ( $svdu ) );
							$cell = $table->addCell ( 2500 );
							$cell->addText ( htmlspecialchars ( $unCommandedResets ) );
							$cell = $table->addCell ( 2500 );
							$cell->addText ( htmlspecialchars ( $commandedResets ) );
							$cell = $table->addCell ( 2500 );
							$cell->addText ( htmlspecialchars ( $totalResetsCount ) );
						}
					}
				} else {
					$table->addRow ();
					if ($rawCountValue % 2 == 0) {
						$cell = $table->addCell ( 3000, array (
								'bgColor' => 'C0C0C0' 
						) );
						$cell->addText ( htmlspecialchars ( $svdu ) );
						$cell = $table->addCell ( 2000, array (
								'bgColor' => 'C0C0C0' 
						) );
						$cell->addText ( htmlspecialchars ( '0' ) );
						$cell = $table->addCell ( 2000, array (
								'bgColor' => 'C0C0C0' 
						) );
						$cell->addText ( htmlspecialchars ( '0' ) );
						$cell = $table->addCell ( 2000, array (
								'bgColor' => 'C0C0C0' 
						) );
						$cell->addText ( htmlspecialchars ( '0' ) );
					} else {
						$cell = $table->addCell ( 3000 );
						$cell->addText ( htmlspecialchars ( $svdu ) );
						$cell = $table->addCell ( 2000 );
						$cell->addText ( htmlspecialchars ( '0' ) );
						$cell = $table->addCell ( 2000 );
						$cell->addText ( htmlspecialchars ( '0' ) );
						$cell = $table->addCell ( 2000 );
						$cell->addText ( htmlspecialchars ( '0' ) );
					}
				}
			} else {
				$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
				$section->addTextBreak ( 1, $textFontStyle );
			}
		}
	} else {
		$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
	}
	
	$section->addTextBreak ( 1, $textFontStyle );
}
function generateParagraphForCommandedResets($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $unitType, $count) {
	$query = getQueryForCommandedResets ( $start, $end, $unitType, $count );
	static $counter = 1;
	$section->addText ( htmlspecialchars ( '- ' . $text . ':' ), $titleFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	if ($result != false) {
		$count = mysqli_num_rows ( $result );
		if ($count > 0) {
			
			$table = $section->addTable ( 'Details Table' );
			$table->addRow ();
			$cell = $table->addCell ( 3000 );
			$cell->addText ( htmlspecialchars ( 'SVDU Name' ), $titleFontStyle );
			$cell = $table->addCell ( 2000 );
			$cell->addText ( htmlspecialchars ( 'Number of resets' ), $titleFontStyle );
			
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$counter ++;
				$lru = $row ['eventData'];
				$rowCount = $counter;
				$table->addRow ();
				if ($rowCount % 2 == 0) {
					$cell = $table->addCell ( 3000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $lru ) );
					$cell = $table->addCell ( 2000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $row ['count'] ) );
				} else {
					$cell = $table->addCell ( 3000 );
					$cell->addText ( htmlspecialchars ( $lru ) );
					$cell = $table->addCell ( 2000 );
					$cell->addText ( htmlspecialchars ( $row ['count'] ) );
				}
			}
		} else {
			$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
}
function generateParagraph($section, $titleFontStyle, $textFontStyle, $text, $query, $hostnameCol, $failureCodes = '') {
	// // For debug
	// $section->addText(htmlspecialchars('query: '.$query), $titleFontStyle);
	// $section->addTextBreak(1, $textFontStyle);
	$lrus = array ();
	$i = 0;
	$counter = 0;
	$section->addText ( htmlspecialchars ( '- ' . $text . ':' ), $titleFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	if ($result != false) {
		$count = mysqli_num_rows ( $result );
		if ($count > 0) {
			
			$table = $section->addTable ();
			$table->addRow ();
			$cell = $table->addCell ( 3000 );
			$cell->addText ( htmlspecialchars ( 'LRU Name - S/N' ), $titleFontStyle );
			$cell = $table->addCell ( 2000 );
			$cell->addText ( htmlspecialchars ( 'Number of faults' ), $titleFontStyle );
			
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$counter ++;
				$lrus [$i] = $row [$hostnameCol];
				
				$table->addRow ();
				$cell = $table->addCell ( 3000 );
				$cell->addText ( htmlspecialchars ( $lrus [$i] . " - " . $row ['serialNumber'] ) );
				$cell = $table->addCell ( 2000 );
				$cell->addText ( htmlspecialchars ( $row ['failures'] ) );
				
				$i ++;
			}
		} else {
			$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
	
	return $lrus;
}

/**
 * Newly Added
 * Generate Post Failure report as a Paragraph.
 */
function generateForPostFailureParagraph($section, $titleFontStyle, $textFontStyle, $text, $start, $end, $hostnameCol, $failureCodes = '') {
	// // For debug
	// $section->addText(htmlspecialchars('query: '.$query), $titleFontStyle);
	// $section->addTextBreak(1, $textFontStyle);
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$db_Name = $GLOBALS ['dbName'];
	$lrus = array ();
	$i = 0;
	static $counter = 0;
	 $query = "SELECT DISTINCT t.hostName, t.faultCode, t.reportingHostName, b.faultDesc, COUNT( t.faultCode ) AS numberOfFaults
	              FROM $db_Name.BIT_fault t, $commonDbName.sys_faultinfo b
	              WHERE t.faultCode = b.faultCode
	              AND t.detectionTime BETWEEN '$start' AND '$end'
	              AND t.faultCode IN ( 42001006, 42001009, 42001000, 42001004 ) 
	              GROUP BY t.hostName,t.faultCode ORDER BY numberOfFaults DESC "; 
	 //echo "--->>>$query--->>>>>";
	
	$section->addText ( htmlspecialchars ( '- ' . $text . ':' ), $titleFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	
	if ($result != false) {
		$count = mysqli_num_rows ( $result );
		// echo "--RowCount:--$count ";
		if ($count > 0) {
			$table = $section->addTable ();
			$table->addRow ();
			$cell = $table->addCell ( 3000 );
			$cell->addText ( htmlspecialchars ( 'LRU Name - S/N' ), $titleFontStyle );
			$cell = $table->addCell ( 2000 );
			$cell->addText ( htmlspecialchars ( 'Number of faults' ), $titleFontStyle );
			$cell = $table->addCell ( 5000 );
			$cell->addText ( htmlspecialchars ( 'Description of faults' ), $titleFontStyle );
			
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$counter ++;
				// echo "----$counter---";
				$host_Name = '';
				if ($row ['hostName'] != '') {
					$host_Name = $row ['hostName'];
					$numberOfFault = $row ['numberOfFaults'];
					$faultDesc = $row ['faultDesc'];
					// echo "->>HOST_NAME->>$host_Name";
					$query_1 = "SELECT t.serialNumber FROM BIT_lru t WHERE t.hostName='$host_Name'";
					$result_1 = mysqli_query ( $GLOBALS ['dbConnection'], $query_1 );
					// $row1 = mysql_fetch_array ( $result );
					while ( $row1 = mysqli_fetch_array ( $result_1 ) ) {
						if ($row1 ['serialNumber'] != '') {
							$serial_Number = $row1 ['serialNumber'];
						}
					}
					$table->addRow ();
					$cell = $table->addCell ( 3000 );
					$cell->addText ( htmlspecialchars ( $host_Name . " - " . $serial_Number ) );
					$cell = $table->addCell ( 2000 );
					$cell->addText ( htmlspecialchars ( $numberOfFault ) );
					$cell = $table->addCell ( 5000 );
					$cell->addText ( htmlspecialchars ( $faultDesc ) );
				}
			} // End of while Loop
		} else {
			$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
	
	return $lrus;
}
function generateParagraphForDetails($dbhandle, $section, $titleFontStyle, $textFontStyle, $query, $hostnameCol, $failureCodes = '') {
	// // For debug
	// $section->addText(htmlspecialchars('query: '.$query), $titleFontStyle);
	// $section->addTextBreak(1, $textFontStyle);
	// $dbName = $GLOBALS ['db'];
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$lrus = array ();
	$i = 0;
	$rowCounterForDetails = 1;
	$failureQuery = "SELECT failureDesc 
                            FROM $commonDbName.sys_failureinfo 
                            WHERE failureCode = $failureCodes";
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $failureQuery );
	$row = mysqli_fetch_array ( $result );
	
	$text = "Details for failure code " . $failureCodes . " [" . $row ['failureDesc'] . "]";
	$section->addText ( htmlspecialchars ( $text ), $titleFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	if ($result != false) {
		$count = mysqli_num_rows ( $result );
		if ($count > 0) {
			
			$table = $section->addTable ( 'Details Table' );
			$table->addRow ();
			$cell = $table->addCell ( 1000 );
			$cell->addText ( htmlspecialchars ( 'LRU' ), $titleFontStyle );
			$cell = $table->addCell ( 1000 );
			$cell->addText ( htmlspecialchars ( 'Flight Leg' ), $titleFontStyle );
			$cell = $table->addCell ( 1500 );
			$cell->addText ( htmlspecialchars ( 'Route' ), $titleFontStyle );
			$cell = $table->addCell ( 1500 );
			$cell->addText ( htmlspecialchars ( 'Flight Phase' ), $titleFontStyle );
			$cell = $table->addCell ( 1000 );
			$cell->addText ( htmlspecialchars ( 'State' ), $titleFontStyle );
			$cell = $table->addCell ( 2000 );
			$cell->addText ( htmlspecialchars ( 'Duration' ), $titleFontStyle );
			
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$rowCounterForDetails ++;
				$lrus [$i] = $row [$hostnameCol];
				$flightLegId = $row ['idFlightLeg'];
				$state = getMonitorStateDesc ( $row ['monitorState'] );
				$start = $row ['correlationDate'];
				$end = $row ['lastUpdate'];
				$duration = dateDifference ( $start, $end, '%h Hours %i Minutes' );
				$route = getFlightLegRoute ( $flightLegId );
				$flightPhase = getFlightPhase ( $start );
				
				$totalRowCount = $rowCounterForDetails;
				$table->addRow ();
				if ($totalRowCount % 2 == 0) {
					
					$cell = $table->addCell ( 1000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $lrus [$i] ) );
					$cell = $table->addCell ( 1000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $flightLegId ) );
					$cell = $table->addCell ( 1250, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $route ) );
					$cell = $table->addCell ( 1250, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $flightPhase ) );
					$cell = $table->addCell ( 1000, array (
							'bgColor' => 'C0C0C0' 
					) );
					if ($state == 'Active') {
						$cell->addText ( htmlspecialchars ( $state ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $state ) );
					}
					$cell = $table->addCell ( 2000, array (
							'bgColor' => 'C0C0C0' 
					) );
					if ($state == 'Inactive') {
						$cell->addText ( htmlspecialchars ( $duration ) );
					}
				} else {
					$cell = $table->addCell ( 1000 );
					$cell->addText ( htmlspecialchars ( $lrus [$i] ) );
					$cell = $table->addCell ( 1000 );
					$cell->addText ( htmlspecialchars ( $flightLegId ) );
					$cell = $table->addCell ( 1250 );
					$cell->addText ( htmlspecialchars ( $route ) );
					$cell = $table->addCell ( 1250 );
					$cell->addText ( htmlspecialchars ( $flightPhase ) );
					$cell = $table->addCell ( 1000 );
					if ($state == 'Active') {
						$cell->addText ( htmlspecialchars ( $state ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $state ) );
					}
					$cell = $table->addCell ( 2000 );
					
					if ($state == 'Inactive') {
						$cell->addText ( htmlspecialchars ( $duration ) );
					}
				}
				$i ++;
			}
			$section->addTextBreak ( 1, $textFontStyle );
		} else {
			$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
	
	return $lrus;
}

/**
 * New Added for i5000
 * DSU faults record details
 */
function generateFaultParagraphForDetails($dbhandle, $section, $text, $titleFontStyle, $textFontStyle, $query, $isSingleFault, $hostnameCol, $faultCodes = '') {
	// // For debug
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$lrus = array ();
	$i = 0;
	$rowCounterForDetails = 1;
	
	if ($isSingleFault) {
		
		$faultQuery = "SELECT faultDesc FROM $commonDbName.sys_faultinfo
				      WHERE faultCode IN ($faultCodes)";
		$result1 = mysqli_query ( $GLOBALS ['dbConnection'], $faultQuery );
		$row1 = mysqli_fetch_array ( $result1 );
		$faultDesc = $row1 ['faultDesc'];
		$text = "Details for fault code " . $faultCodes . " [" . $faultDesc . "]";
	}
	$section->addText ( htmlspecialchars ( '- ' . $text . ':' ), $titleFontStyle );
	// $text = "Details for DSU fault codes " . $faultCodes ;
	// $section->addText ( htmlspecialchars ( $text ), $titleFontStyle );
	$section->addTextBreak ( 1, $textFontStyle );
	
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	if ($result != false) {
		$count = mysqli_num_rows ( $result );
		if ($count > 0) {
			
			$table = $section->addTable ( 'Details Table' );
			$table->addRow ();
			$cell = $table->addCell ( 950 );
			$cell->addText ( htmlspecialchars ( 'LRU' ), $titleFontStyle );
			$cell = $table->addCell ( 950 );
			$cell->addText ( htmlspecialchars ( 'Fault' ), $titleFontStyle );
			$cell = $table->addCell ( 2000 );
			$cell->addText ( htmlspecialchars ( 'Description' ), $titleFontStyle );
			$cell = $table->addCell ( 950 );
			$cell->addText ( htmlspecialchars ( 'Flight Leg' ), $titleFontStyle );
			$cell = $table->addCell ( 1500 );
			$cell->addText ( htmlspecialchars ( 'Route' ), $titleFontStyle );
			$cell = $table->addCell ( 1300 );
			$cell->addText ( htmlspecialchars ( 'Flight Phase' ), $titleFontStyle );
			$cell = $table->addCell ( 1000 );
			$cell->addText ( htmlspecialchars ( 'State' ), $titleFontStyle );
			$cell = $table->addCell ( 1550 );
			$cell->addText ( htmlspecialchars ( 'Duration' ), $titleFontStyle );
			
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$rowCounterForDetails ++;
				$faultCode = $row ['faultCode'];
				// Retriving Fault Description from sys_faultinfo.
				if (! $isSingleFault) {
					$faultDescQuery = "SELECT faultDesc FROM $commonDbName.sys_faultinfo
				                   WHERE faultCode IN ($faultCode)";
					$result_forFaultDesc = mysqli_query ( $GLOBALS ['dbConnection'], $faultDescQuery );
					
					while ( $row_forFaultDesc = mysqli_fetch_array ( $result_forFaultDesc ) ) {
						$faultDesc = $row_forFaultDesc ['faultDesc'];
					}
				} // End of
				$lrus [$i] = $row [$hostnameCol];
				$flightLegId = $row ['idFlightLeg'];
				$state = getMonitorStateDesc ( $row ['monitorState'] );
				$start = $row ['detectionTime'];
				$end = $row ['lastUpdate'];
				$duration = dateDifference ( $start, $end, '%h Hours %i Minutes' );
				$route = getFlightLegRoute ( $flightLegId );
				$flightPhase = getFlightPhase ( $start );
				
				$totalRowCount = $rowCounterForDetails;
				$table->addRow ();
				if ($totalRowCount % 2 == 0) {
					
					$cell = $table->addCell ( 950, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $lrus [$i] ) );
					$cell = $table->addCell ( 950, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $faultCode ) );
					$cell = $table->addCell ( 2000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $faultDesc ) );
					$cell = $table->addCell ( 950, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $flightLegId ) );
					$cell = $table->addCell ( 1500, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $route ) );
					$cell = $table->addCell ( 1300, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $flightPhase ) );
					$cell = $table->addCell ( 1000, array (
							'bgColor' => 'C0C0C0' 
					) );
					if ($state == 'Active') {
						$cell->addText ( htmlspecialchars ( $state ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $state ) );
					}
					$cell = $table->addCell ( 1550, array (
							'bgColor' => 'C0C0C0' 
					) );
					if ($state == 'Inactive') {
						$cell->addText ( htmlspecialchars ( $duration ) );
					}
				} else {
					$cell = $table->addCell ( 950 );
					$cell->addText ( htmlspecialchars ( $lrus [$i] ) );
					$cell = $table->addCell ( 950 );
					$cell->addText ( htmlspecialchars ( $faultCode ) );
					$cell = $table->addCell ( 2000 );
					$cell->addText ( htmlspecialchars ( $faultDesc ) );
					$cell = $table->addCell ( 950 );
					$cell->addText ( htmlspecialchars ( $flightLegId ) );
					$cell = $table->addCell ( 1500 );
					$cell->addText ( htmlspecialchars ( $route ) );
					$cell = $table->addCell ( 1300 );
					$cell->addText ( htmlspecialchars ( $flightPhase ) );
					$cell = $table->addCell ( 1000 );
					if ($state == 'Active') {
						$cell->addText ( htmlspecialchars ( $state ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $state ) );
					}
					$cell = $table->addCell ( 1550 );
					
					if ($state == 'Inactive') {
						$cell->addText ( htmlspecialchars ( $duration ) );
					}
				}
				$i ++;
			}
			$section->addTextBreak ( 1, $textFontStyle );
		} else {
			$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
	
	return $lrus;
}

/**
 * It's a Common Query For Faults 400 and 404.and it's for daily report section.
 * 
 * @param unknown $commonDbName
 *        	- BAnalytics Database
 * @param unknown $db_Name-
 *        	it's Bite dataBase or SqlDatabase.
 * @param unknown $faultCode-
 *        	It's now 400 and 404
 * @param unknown $idFlightLeg
 *        	- It's a Dynamic value
 * @return string - result of Query.
 */
function getQueryForDailyReportFaults($commonDbName, $db_Name, $faultCode, $idFlightLeg, $platform) {
	$query = "SELECT hostName, bit_t.faultCode ,bit_t.reportingHostName, faultDesc , bit_t.idFlightLeg , bit_t.param1 , bit_t.param2 , bit_t.param3, bit_t.monitorState, bit_t.detectionTime, bit_t.clearingTime, bit_t.lastUpdate, flightNumber, departureAirportCode, arrivalAirportCode, createDate, endFlightLeg
	FROM (
		SELECT DISTINCT B.hostName, B.faultCode, B.reportingHostName, B.idFlightLeg, B.param1, B.param2, B.param3, B.monitorState, B.detectionTime, B.clearingTime, B.lastUpdate, C.flightNumber, C.departureAirportCode, C.arrivalAirportCode, C.createDate, C.lastUpdate  AS 'endFlightLeg'
		,IFNULL((TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime)),0) As 'Timediff'
		FROM 		$db_Name.BIT_fault B
		INNER JOIN 	$db_Name.SYS_flightPhase P
		ON 			B.idFlightLeg = P.idFlightLeg
		INNER JOIN 	$db_Name.SYS_flight C
		ON 			B.idFlightLeg = C.idFlightLeg";
	if($platform == "i5000"){
		$query .= " AND P.idFlightPhase IN (4,5)";
	}
	else{
		$query .= " AND P.idFlightPhase = 5";
	}
	$query .= " AND B.faultCode IN ($faultCode)
		AND	B.idFlightLeg = '$idFlightLeg'
		AND (B.detectionTime BETWEEN P.startTime AND P.endTime
		OR 			
		(B.detectionTime < P.startTime and B.clearingTime BETWEEN P.startTime AND P.endTime)
		OR			
		(B.detectionTime < P.startTime and B.clearingTime>P.endTime)
		OR			
		(B.detectionTime < P.startTime and B.clearingTime='0000-00-00 00:00:00'))
	) AS bit_t, $commonDbName.sys_faultinfo E
	WHERE bit_t.faultCode = E.faultCode
	AND (bit_t.monitorState=3 OR bit_t.Timediff>=15)
	GROUP BY `reportingHostName`
	ORDER BY `detectionTime`";
	
	// echo "<br><br>Query_1 InCruise:---$query---</br></br>";
	return $query;
}

/**
 * It's Part of the Frequency of the fault And now it's for 400.
 * 
 * @param unknown $db_Name
 *        	- Bite Database or SqlDatabase.
 * @param unknown $faultCode
 *        	- it's now 400.
 * @param unknown $idFlightLeg
 *        	- It's a dynamic value.
 */
function getQueryForFaultFrequency($db_Name, $faultCode, $idFlightLeg, $platform) {
		
	$query = "SELECT t.hostName, t.reportingHostname, t.faultCode, COUNT(*) AS count, t.idFlightLeg
	FROM(
		SELECT DISTINCT B.idFault, B.hostName, B.reportingHostname, B.faultCode, B.idFlightLeg
		FROM $db_Name.BIT_fault B
		INNER JOIN $db_Name.SYS_flightPhase P
		ON B.idFlightLeg = P.idFlightLeg";
	if($platform == "i5000"){
		$query .= " AND P.idFlightPhase IN (4,5)";
	}
	else{
		$query .= " AND P.idFlightPhase = 5";
	}
	$query .= " AND B.faultCode IN ($faultCode)
		AND B.idFlightLeg = $idFlightLeg
		AND (
			TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) < 15
		)
		AND
		(
		(B.detectionTime BETWEEN P.startTime AND P.endTime) 
		OR 
		(B.clearingTime BETWEEN P.startTime AND P.endTime) 
		OR 
		(B.detectionTime <= P.startTime AND B.clearingTime >= P.endTime)
		)
	) AS t
	GROUP BY t.`hostName`, t.reportingHostname
	HAVING (count >= 5 )
	ORDER BY `count` DESC, LENGTH(t.hostName), t.hostName ";
	
	return $query;
		
}

/**
 *
 * @return multitype:unknown-It will return FlightLegId with FlightNumbe DepartureAirportCode, ArrivalAirportCOde, Created Date And LastUpdate
 */
function getFlightLegsDetailsForOneDayDuration($platform) {
	// $result_array = array ();
	// static $rowCounter = 0;
	// $flight_array = array ();
	$mysqlDailyStart = $GLOBALS ['mysqlDailyStart'];
	$mysqlDailyEnd = $GLOBALS ['mysqlDailyEnd'];
	$flightLegDetail = array ();
	$query = "SELECT DISTINCT a.idFlightLeg,a.flightNumber, a.departureAirportCode, a.arrivalAirportCode, a.createDate, a.lastUpdate  AS 'endFlightLeg'
	FROM SYS_flight a
	INNER JOIN SYS_flightPhase b
	ON a.idFlightLeg = b.idFlightLeg";
	if($platform=="i5000"){
		$query .= " AND b.idFlightPhase IN (4,5)";
	}
	else{
		$query .= " AND b.idFlightPhase = 5";
	}
	$query .= " AND (
	( '$mysqlDailyStart' <= a.createDate AND '$mysqlDailyEnd' >= a.lastUpdate)
	OR
	( '$mysqlDailyStart' <= a.lastUpdate AND '$mysqlDailyEnd' >= a.createDate)
	)
	ORDER BY a.createDate";
	// GROUP BY a.idFlightLeg
	
	// echo "<br><br>Query InCruise:---$query---</br></br>";
	$result_set = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	
	if ($result_set) { // Not all the dumps have all this table
		$count1 = mysqli_num_rows ( $result_set );
		if ($count1 > 0) {
			while ( $row_set = mysqli_fetch_array ( $result_set ) ) {
				$flightLegDetail [] = $row_set;
			}
		}
	}
	
	return $flightLegDetail;
}
/**
 *
 * Displaying all faults 400 and 404 occurring in cruise mode
 * It's Main method for Daily Section.
 * It will Create Paragarph in Daily Section For In Cruise Mood.
 *
 * @param unknown $faultCodes        	
 */
function generateParagraphInDailySectionForFaultsInCruise($faultCodes) {
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$db_Name = $GLOBALS ['dbName'];
	$section = $GLOBALS ['section_1'];
	$actype = $GLOBALS ['actype'];
	$platform = $GLOBALS ['platform'];
	// $dbConnection = $GLOBALS ['dbConnection'];
	$italicFontStyle = $GLOBALS ['italicFontStyle'];
	$titleFontStyle = $GLOBALS ['titleFontStyle'];
	$textFontStyle = $GLOBALS ['textFontStyle'];
	$titleFontStyleForSeats = $GLOBALS ['titleFontStyleForSeats'];
	$flightstatusFontStyle = $GLOBALS ['flightstatusFontStyle'];
	/*
	 * $seatsWithSvdu = $GLOBALS ['seatsWithSvdu'];
	 * $secondClassseatsWithSvdu = $GLOBALS ['secondClassseatsWithSvdu'];
	 */
	$fClassSeats = $GLOBALS ['fClassSeats'];
	$bClassSeats = $GLOBALS ['bClassSeats'];
	$totalEconomyClassCount = $GLOBALS ['totalEconomyClassSeats'];
	// echo "--->>>>>$totalEconomyClassCount----->>>>>";
	/**
	 * It will return 24hours duration Flight Legs.
	 */
	$flightLegDetails = getFlightLegsDetailsForOneDayDuration ($platform);
	//echo "FlightLegs --$flightLegDetails";
	if(!empty($flightLegDetails)) {
		
	foreach ( $flightLegDetails as $flightLegs ) {
		//echo "Test";
		$idFlightLeg = $flightLegs ['idFlightLeg'];
		$flightNumber = $flightLegs ['flightNumber'];
		$departureAirportCode = $flightLegs ['departureAirportCode'];
		$arrivalAirportCode = $flightLegs ['arrivalAirportCode'];
		$createDate = $flightLegs ['createDate'];
		$endFlightLeg = $flightLegs ['endFlightLeg'];
		
		$section->addTextBreak ( 1, $textFontStyle );
		// It's common for fault 400 and 404.
		$section->addText ( htmlspecialchars ( 'Flight Leg id #' . $idFlightLeg . ' / ' . $flightNumber . ' / ' . $departureAirportCode . ' - ' . $arrivalAirportCode . ' / ' . $createDate . ' => ' . $endFlightLeg ), $titleFontStyle );
		// echo ">>>--$idFlightLeg-->>-$flightNumber->>>$departureAirportCode->>>$arrivalAirportCode->>>$createDate-->>>$endFlightLeg";
		
		
		//if ($fClassSeats != '' or $bClassSeats != '') {
			// $estimateStatusResult = calculateFlightStatusEstimation ($actype, $seatsWithSvdu, $secondClassseatsWithSvdu, $idFlightLeg, $fClassSeats, $bClassSeats, $titleFontStyle );
			/**
			 * Getting First, Business and Economy Class  & HeadEnd LRUS information  status .
			 * It will return -1,0 and 2 as a Status of the Flight Estimation.
			 * Status = -1 means it's not computed,
			 * Status = 0, means Flight is OK, it's a good flight.
			 * Status = 2, it's KO means it's a bad Flight.
			 */
			$firstClassSeatsStatusResult = getFlightFirstClassStatus ( $db_Name,$platform, $idFlightLeg, $fClassSeats );
			$businessClassSeatsStatusResult = getFlightBusinessClassStatus ( $db_Name,$platform, $idFlightLeg, $bClassSeats );
			$economyClassSeatsStatusResult = getFlightEconomyClassStatus ( $db_Name,$platform, $idFlightLeg, $fClassSeats, $bClassSeats, $totalEconomyClassCount );
			$headEndLrusStatusEstimationResult =  getHeadEndLrusStatus($db_Name,$platform,$idFlightLeg);
			$headEndLrusFrequencyStatusEstimationResult =  getHeadEndLrusFrequencyStatus($db_Name,$platform,$idFlightLeg);
			
			// MBS: Adding Below block to include SystemReset also for FlightStatus Estimation
			$systemResetStatus = computeSystemResetStatus($db_Name, $idFlightLeg);
			
			$tableForFlightEstimate = $section->addTable ();
			$tableForFlightEstimate->addRow ();
			$cellForFlightEstimate = $tableForFlightEstimate->addCell ( 4000 );
			$textrunForFlightEstimate = $cellForFlightEstimate->createTextRun ();
			$textrunForFlightEstimate->addText ( htmlspecialchars ( 'Flight status estimation: ' ), $titleFontStyleForSeats );
			
			// if ($estimateStatusResult [1] === false or $estimateStatusResult [2] === false or $estimateStatusResult [3] === false) {
			if ($firstClassSeatsStatusResult === 2 or $businessClassSeatsStatusResult === 2 or $economyClassSeatsStatusResult === 2 or $headEndLrusStatusEstimationResult === 2 or $headEndLrusFrequencyStatusEstimationResult  === 2 or $systemResetStatus === 2) {
				// $section->addImage('../img/seatStatusOk.png',array('width'=>30));
				$textrunForFlightEstimate->addImage ( '../img/seatStatusKo.png', array (
						'width' => 30,
						'marginTop' => - 1,
						'marginLeft' => - 1 
				) );
			} else {
				// $section->addImage('../img/seatStatusKo.png',array('width'=>30));
				$textrunForFlightEstimate->addImage ( '../img/seatStatusOk.png', array (
						'width' => 30,
						'marginTop' => - 1,
						'marginLeft' => - 1 
				) );
			}
			
			if ($firstClassSeatsStatusResult === 2) {
				$addTableForFirstClass = $section->addTable ();
				$addTableForFirstClass->addRow ();
				$cellForFirstClass = $addTableForFirstClass->addCell ( 10500 );
				$textrunForFirstClass = $cellForFirstClass->createTextRun ();
				/*
				 * $textrunForFirstClass->addImage ( '../img/seatStatusKo.png', array (
				 * 'width' => 20,
				 * 'marginTop' => - 1,
				 * 'marginLeft' => - 1
				 * ) );
				 */
				$textrunForFirstClass->addText ( htmlspecialchars ( '-  Because 1 F/C Seat or more Seats has service interruption in cruise for more than 30mns' ), $flightstatusFontStyle );
			} else {
				// $textrunForFirstClass->addText ( htmlspecialchars ( '1 F/C Seat or more Seats has service interruption in cruise for more than 30mns') , $titleFontStyle);
				// $textrunForFirstClass->addImage('../img/seatStatusOk.png', array('width'=>20,'marginTop' => -1,'marginLeft' => -1));
			}
			
			if ($businessClassSeatsStatusResult === 2) {
				$addTableForBusinessClass = $section->addTable ();
				$addTableForBusinessClass->addRow ();
				$cellForBusinessClass = $addTableForBusinessClass->addCell ( 10500 );
				$textrunForBusinessClass = $cellForBusinessClass->createTextRun ();
				/*
				 * $textrunForBusinessClass->addImage ( '../img/seatStatusKo.png', array (
				 * 'width' => 20,
				 * 'marginTop' => - 1,
				 * 'marginLeft' => - 1
				 * ) );
				 */
				$textrunForBusinessClass->addText ( htmlspecialchars ( '-  Because 3 or more B/C Seats has service interruption in cruise for more than 30mns' ), $flightstatusFontStyle );
			} else {
				// $textrunForBusinessClass->addText ( htmlspecialchars ( '3 or more B/C Seats has service interruption in cruise for more than 30mns'), $titleFontStyle );
				// $textrunForBusinessClass->addImage('../img/seatStatusOk.png', array('width'=>20,'marginTop' => -1,'marginLeft' => -1));
			}
			
			if ($economyClassSeatsStatusResult === 2) {
				$addTableForBusinessClass = $section->addTable ();
				$addTableForBusinessClass->addRow ();
				$cellForBusinessClass = $addTableForBusinessClass->addCell ( 10500 );
				$textrunForBusinessClass = $cellForBusinessClass->createTextRun ();
				/*
				 * $textrunForBusinessClass->addImage ( '../img/seatStatusKo.png', array (
				 * 'width' => 20,
				 * 'marginTop' => - 1,
				 * 'marginLeft' => - 1
				 * ) );
				 */
				$textrunForBusinessClass->addText ( htmlspecialchars ( '-  Because 10% or more E/C Seats has service interruption in cruise for more than 30mns' ), $flightstatusFontStyle );
			} else {
				// $textrunForBusinessClass->addText ( htmlspecialchars ( '3 or more B/C Seats has service interruption in cruise for more than 30mns'), $titleFontStyle );
				// $textrunForBusinessClass->addImage('../img/seatStatusOk.png', array('width'=>20,'marginTop' => -1,'marginLeft' => -1));
			}
			if ($headEndLrusStatusEstimationResult === 2 or $headEndLrusFrequencyStatusEstimationResult === 2) {
				$addTableForBusinessClass = $section->addTable ();
				$addTableForBusinessClass->addRow ();
				$cellForBusinessClass = $addTableForBusinessClass->addCell ( 10500 );
				$textrunForBusinessClass = $cellForBusinessClass->createTextRun ();
				/*
				 * $textrunForBusinessClass->addImage ( '../img/seatStatusKo.png', array (
				 * 'width' => 20,
				 * 'marginTop' => - 1,
				 * 'marginLeft' => - 1
				 * ) );
				*/
				$textrunForBusinessClass->addText ( htmlspecialchars ( '-  Because Head End LRUs has a 400 Faults  in cruise for more than 30mns' ), $flightstatusFontStyle );
			} else {
				// $textrunForBusinessClass->addText ( htmlspecialchars ( '3 or more B/C Seats has service interruption in cruise for more than 30mns'), $titleFontStyle );
				// $textrunForBusinessClass->addImage('../img/seatStatusOk.png', array('width'=>20,'marginTop' => -1,'marginLeft' => -1));
			}
		/*	
		} // End of Flight Status Estimation Calculation.
		else {
			$section->addText ( htmlspecialchars ( 'Flight status estimate computation not possible because it\'s require Aircraft Seats Info:  ' ), $titleFontStyle  );
		}
		*/
		foreach ( $faultCodes as $faultCode => $faultCodeDesc ) {
			
			$query_ForFault = getQueryForDailyReportFaults ( $commonDbName, $db_Name, $faultCode, $idFlightLeg, $platform );
			$result_QueryForFault = mysqli_query ( $GLOBALS ['dbConnection'], $query_ForFault );
			if ($result_QueryForFault != false) {
				$count = mysqli_num_rows ( $result_QueryForFault );
				$section->addTextBreak ( 1, $textFontStyle );
				//$section->addText ( htmlspecialchars ( 'Fault ' . $faultCode . ' still active or greater than 15 minutes (in cruise mode)' ), $textFontStyle );
				$section->addText ( htmlspecialchars ( 'Fault ' . $faultCode ."-". $faultCodeDesc[0]), $textFontStyle );
				// echo "--Count:- $count--";
				if ($count > 0) {
					/* */
					// Create table for particular faults like 400.
					createDailyRepotSectionForFlightLeg ( $section, $result_QueryForFault, $titleFontStyle );
				} else {
					$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
				}
			}
			/**
			 * Below part of the Code will execute only for 400.
			 */
			// if ($faultCode != 404) {
			$query_ForFrequency = getQueryForFaultFrequency ( $db_Name, $faultCode, $idFlightLeg, $platform );
			$section->addTextBreak ( 1, $textFontStyle );
			//$section->addText ( htmlspecialchars ( 'Fault ' . $faultCode . ' with more than 5 fault less than 15 minutes (in cruise mode)' ), $textFontStyle );
			$section->addText ( htmlspecialchars ( 'Fault ' . $faultCode ."-". $faultCodeDesc[1]), $textFontStyle );
			$result_QueryForFrequency = mysqli_query ( $GLOBALS ['dbConnection'], $query_ForFrequency );
			
			if ($result_QueryForFrequency != false) {
				$rowCount = mysqli_num_rows ( $result_QueryForFrequency );
				if ($rowCount > 0) {
					createTableForFaultFrequency ( $section, $result_QueryForFrequency, $titleFontStyle, $faultCode );
				} else {
					$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
				}
			}
			// } // End Of fault Code Checking.
		} // End Of FaultCode ForEach Loop.
	} // End of FlightLeg Foreach loop.
	}
	else {
		//echo "Test";
		$section->addTextBreak ( 1, $textFontStyle );
		$section->addText ( htmlspecialchars ( 'No Flight Leg Or Legs in Cruise Phase:' ), $titleFontStyle  );
	}
}
/**
 * It will Generate Table in Daily Section Based on the Fault and It's Common for all Faults and it's now for Faults 400 and 404.
 * 
 * @param unknown $section        	
 * @param unknown $result_Query        	
 * @param unknown $titleFontStyle        	
 * @param unknown $faultCode
 *        	- 400 and 404
 */
function createDailyRepotSectionForFlightLeg($section, $result_Query, $titleFontStyle) {
	$totalRowCount = 1;
	// $section->addText ( htmlspecialchars ( 'Fault ' . $faultCode . ' still active or greater than 15 minutes (in cruise mode)' ), $textFontStyle );
	
	$table = $section->addTable ( 'Details Table' );
	$table->addRow ();
	$cell = $table->addCell ( 500 );
	$cell->addText ( htmlspecialchars ( '#' ), $titleFontStyle );
	$cell = $table->addCell ( 1200 );
	$cell->addText ( htmlspecialchars ( 'LRU' ), $titleFontStyle );
	$cell = $table->addCell ( 900 );
	
	$cell->addText ( htmlspecialchars ( 'Fault' ), $titleFontStyle );
	$cell = $table->addCell ( 3300 );
	$cell->addText ( htmlspecialchars ( 'Description' ), $titleFontStyle );
	$cell = $table->addCell ( 1950 );
	$cell->addText ( htmlspecialchars ( 'Reporting Host Name' ), $titleFontStyle );
	$cell = $table->addCell ( 1700 );
	$cell->addText ( htmlspecialchars ( 'Parameters' ), $titleFontStyle );
	$cell = $table->addCell ( 1000 );
	$cell->addText ( htmlspecialchars ( 'State' ), $titleFontStyle );
	$cell = $table->addCell ( 1500 );
	$cell->addText ( htmlspecialchars ( 'Detection' ), $titleFontStyle );
	$cell = $table->addCell ( 1680 );
	$cell->addText ( htmlspecialchars ( 'Clearing Time' ), $titleFontStyle );
	$cell = $table->addCell ( 1400 );
	$cell->addText ( htmlspecialchars ( 'Duration' ), $titleFontStyle );
	$i = 0;
	while ( $row_data = mysqli_fetch_array ( $result_Query ) ) {
		
		$lruName = $row_data ['hostName'];
		$fault_Code = $row_data ['faultCode'];
		$fault_Desc = $row_data ['faultDesc'];
		$fault_ReportingHostName = $row_data ['reportingHostName'];
		$parameter_1 = $row_data ['param1'];
		$parameter_2 = $row_data ['param2'];
		$parameter_3 = $row_data ['param3'];
		$state = getMonitorStateDesc ( $row_data ['monitorState'] );
		$faultDectectionTime = $row_data ['detectionTime'];
		$faultClearingTime = $row_data ['clearingTime'];
		$duration = dateDifference ( $faultDectectionTime, $faultClearingTime, '%h Hours %i Minutes' );
		
		$isListedLruName = false;
		$isMonitorStateActive = false;
		if ((substr ( $lruName, 0, 3 ) == 'DSU') or (substr ( $lruName, 0, 4 ) == 'LAIC') or (substr ( $lruName, 0, 4 ) == 'AVCD') or (substr ( $lruName, 0, 4 ) == 'ADBG')) {
			$isListedLruName = true;
		}
		
		if ($state == 'Active') {
			$isMonitorStateActive = true;
		}
		// Create table Fault recorded in Cruise.
		$table->addRow ();
		// If totalRowCounter is odd Table row coming with color shade.
		if ($totalRowCount % 2 != 0) {
			//
			$cell = $table->addCell ( 500, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $i ++ ) );
			$cell = $table->addCell ( 1200, array (
					'bgColor' => 'C0C0C0' 
			) );
			
			if ($isListedLruName) {
				$cell->addText ( htmlspecialchars ( $lruName ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $lruName ) );
			}
			$cell = $table->addCell ( 900, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $fault_Code ) );
			$cell = $table->addCell ( 3300, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $fault_Desc ) );
			$cell = $table->addCell ( 1950, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $fault_ReportingHostName ) );
			$cell = $table->addCell ( 1700, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $parameter_1 . ' / ' . $parameter_2 . ' / ' . $parameter_3 ) );
			$cell = $table->addCell ( 1000, array (
					'bgColor' => 'C0C0C0' 
			) );
			
			if ($isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $state ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $state ) );
			}
			
			$cell = $table->addCell ( 1500, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $faultDectectionTime ) );
			$cell = $table->addCell ( 1680, array (
					'bgColor' => 'C0C0C0' 
			) );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $faultClearingTime ) );
			}
			$cell = $table->addCell ( 1400, array (
					'bgColor' => 'C0C0C0' 
			) );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $duration ) );
			}
		} else {
			
			$cell = $table->addCell ( 500 );
			$cell->addText ( htmlspecialchars ( $i ++ ) );
			$cell = $table->addCell ( 1200 );
			
			if ($isListedLruName) {
				$cell->addText ( htmlspecialchars ( $lruName ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $lruName ) );
			}
			$cell = $table->addCell ( 900 );
			$cell->addText ( htmlspecialchars ( $fault_Code ) );
			$cell = $table->addCell ( 3300 );
			$cell->addText ( htmlspecialchars ( $fault_Desc ) );
			$cell = $table->addCell ( 1950 );
			$cell->addText ( htmlspecialchars ( $fault_ReportingHostName ) );
			$cell = $table->addCell ( 1700 );
			$cell->addText ( htmlspecialchars ( $parameter_1 . ' / ' . $parameter_2 . ' / ' . $parameter_3 ) );
			$cell = $table->addCell ( 1000 );
			if ($isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $state ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $state ) );
			}
			$cell = $table->addCell ( 1500 );
			$cell->addText ( htmlspecialchars ( $faultDectectionTime ) );
			$cell = $table->addCell ( 1680 );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $faultClearingTime ) );
			}
			$cell = $table->addCell ( 1400 );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $duration ) );
			}
		}
		$totalRowCount ++;
	} // Inner While loop.
}

/**
 * *
 * It will Create Table in Daily Section for Fault Frequency.
 * 
 * @param unknown $section        	
 * @param unknown $result_query        	
 * @param unknown $titleFontStyle        	
 * @param unknown $faultCode-
 *        	it's now 404.
 */
function createTableForFaultFrequency($section, $result_query, $titleFontStyle, $faultCode) {
	
	// Create table Fault recorded in Cruise.
	$table = $section->addTable ( 'Details Table' );
	$table->addRow ();
	$cell = $table->addCell ( 500 );
	$cell->addText ( htmlspecialchars ( '#' ), $titleFontStyle );
	$cell = $table->addCell ( 1400 );
	$cell->addText ( htmlspecialchars ( 'LRU' ), $titleFontStyle );
	$cell = $table->addCell ( 1700 );
	$cell->addText ( htmlspecialchars ( 'Reporting Host' ), $titleFontStyle );
	$cell = $table->addCell ( 1700 );
	$cell->addText ( htmlspecialchars ( 'Fault Count' ), $titleFontStyle );
	// $currentFlightLegID = $idFlightLeg_2;
	$i = 0;
	$totalRowCount = 1;
	while ( $row_dataForResult2 = mysqli_fetch_array ( $result_query ) ) {
		
		$lruName = $row_dataForResult2 ['hostName'];
		$reportingHostname = $row_dataForResult2 ['reportingHostname'];
		$fault_Count = $row_dataForResult2 ['count'];
		$isListedLruName = false;
		if ((substr ( $lruName, 0, 3 ) == 'DSU') or (substr ( $lruName, 0, 4 ) == 'LAIC') or (substr ( $lruName, 0, 4 ) == 'AVCD') or (substr ( $lruName, 0, 4 ) == 'ADBG')) {
			$isListedLruName = true;
		}
		$table->addRow ();
		
		if ($totalRowCount % 2 != 0) {
			$cellProperties = array('bgColor' => 'C0C0C0' );
		}else{
			$cellProperties = array();
		}
			
		$cell = $table->addCell ( 500, $cellProperties );
		$cell->addText ( htmlspecialchars ( $i ++ ) );
		$cell = $table->addCell ( 1400, $cellProperties );
		// echo "----LRU:---$lruName_1----";
		if ($isListedLruName) {
			$cell->addText ( htmlspecialchars ( $lruName ), array (
					'color' => 'FF0000',
					'fgColor' => 'yellow',
					'bold' => true 
			) );
		} else {
			$cell->addText ( htmlspecialchars ( $lruName ) );
		}
		$cell = $table->addCell ( 1700, $cellProperties );
		$cell->addText ( htmlspecialchars ( $reportingHostname ) );
		$cell = $table->addCell ( 1700, $cellProperties );
		$cell->addText ( htmlspecialchars ( $fault_Count ) );
		
		$totalRowCount ++;
	} // end of most inner loop.
}

/**
 * It will generate table for faults at the DailyReport Section.
 *
 * @param unknown $section        	
 * @param unknown $result_1        	
 */
function getDailyReportTableForFaults($section, $result_Query, $titleFontStyle, $textFontStyle, $faultCode) {
	$totalRowCount = 1;
	// $section->addText ( htmlspecialchars ( 'Fault ' . $faultCode . ' still active or greater than 15 minutes (in cruise mode)' ), $textFontStyle );
	
	$table = $section->addTable ( 'Details Table' );
	$table->addRow ();
	$cell = $table->addCell ( 500 );
	$cell->addText ( htmlspecialchars ( '#' ), $titleFontStyle );
	$cell = $table->addCell ( 1200 );
	$cell->addText ( htmlspecialchars ( 'LRU' ), $titleFontStyle );
	$cell = $table->addCell ( 900 );
	
	$cell->addText ( htmlspecialchars ( 'Fault' ), $titleFontStyle );
	$cell = $table->addCell ( 3300 );
	$cell->addText ( htmlspecialchars ( 'Description' ), $titleFontStyle );
	$cell = $table->addCell ( 1950 );
	$cell->addText ( htmlspecialchars ( 'Reporting Host Name' ), $titleFontStyle );
	$cell = $table->addCell ( 1700 );
	$cell->addText ( htmlspecialchars ( 'Parameters' ), $titleFontStyle );
	$cell = $table->addCell ( 1000 );
	$cell->addText ( htmlspecialchars ( 'State' ), $titleFontStyle );
	$cell = $table->addCell ( 1500 );
	$cell->addText ( htmlspecialchars ( 'Detection' ), $titleFontStyle );
	$cell = $table->addCell ( 1680 );
	$cell->addText ( htmlspecialchars ( 'Clearing Time' ), $titleFontStyle );
	$cell = $table->addCell ( 1400 );
	$cell->addText ( htmlspecialchars ( 'Duration' ), $titleFontStyle );
	$i = 0;
	while ( $row_data = mysqli_fetch_array ( $result_Query ) ) {
		
		$lruName = $row_data ['hostName'];
		$fault_Code = $row_data ['faultCode'];
		$fault_Desc = $row_data ['faultDesc'];
		$fault_ReportingHostName = $row_data ['reportingHostName'];
		$parameter_1 = $row_data ['param1'];
		$parameter_2 = $row_data ['param2'];
		$parameter_3 = $row_data ['param3'];
		$state = getMonitorStateDesc ( $row_data ['monitorState'] );
		$faultDectectionTime = $row_data ['detectionTime'];
		$faultClearingTime = $row_data ['clearingTime'];
		$duration = dateDifference ( $faultDectectionTime, $faultClearingTime, '%h Hours %i Minutes' );
		
		$isListedLruName = false;
		$isMonitorStateActive = false;
		if ((substr ( $lruName, 0, 3 ) == 'DSU') or (substr ( $lruName, 0, 4 ) == 'LAIC') or (substr ( $lruName, 0, 4 ) == 'AVCD') or (substr ( $lruName, 0, 4 ) == 'ADBG')) {
			$isListedLruName = true;
		}
		
		if ($state == 'Active') {
			$isMonitorStateActive = true;
		}
		// Create table Fault recorded in Cruise.
		$table->addRow ();
		// If totalRowCounter is odd Table row coming with color shade.
		if ($totalRowCount % 2 != 0) {
			//
			$cell = $table->addCell ( 500, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $i ++ ) );
			$cell = $table->addCell ( 1200, array (
					'bgColor' => 'C0C0C0' 
			) );
			
			if ($isListedLruName) {
				$cell->addText ( htmlspecialchars ( $lruName ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $lruName ) );
			}
			$cell = $table->addCell ( 900, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $fault_Code ) );
			$cell = $table->addCell ( 3300, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $fault_Desc ) );
			$cell = $table->addCell ( 1950, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $fault_ReportingHostName ) );
			$cell = $table->addCell ( 1700, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $parameter_1 . ' / ' . $parameter_2 . ' / ' . $parameter_3 ) );
			$cell = $table->addCell ( 1000, array (
					'bgColor' => 'C0C0C0' 
			) );
			
			if ($isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $state ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $state ) );
			}
			
			$cell = $table->addCell ( 1500, array (
					'bgColor' => 'C0C0C0' 
			) );
			$cell->addText ( htmlspecialchars ( $faultDectectionTime ) );
			$cell = $table->addCell ( 1680, array (
					'bgColor' => 'C0C0C0' 
			) );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $faultClearingTime ) );
			}
			$cell = $table->addCell ( 1400, array (
					'bgColor' => 'C0C0C0' 
			) );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $duration ) );
			}
		} else {
			
			$cell = $table->addCell ( 500 );
			$cell->addText ( htmlspecialchars ( $i ++ ) );
			$cell = $table->addCell ( 1200 );
			
			if ($isListedLruName) {
				$cell->addText ( htmlspecialchars ( $lruName ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $lruName ) );
			}
			$cell = $table->addCell ( 900 );
			$cell->addText ( htmlspecialchars ( $fault_Code ) );
			$cell = $table->addCell ( 3300 );
			$cell->addText ( htmlspecialchars ( $fault_Desc ) );
			$cell = $table->addCell ( 1950 );
			$cell->addText ( htmlspecialchars ( $fault_ReportingHostName ) );
			$cell = $table->addCell ( 1700 );
			$cell->addText ( htmlspecialchars ( $parameter_1 . ' / ' . $parameter_2 . ' / ' . $parameter_3 ) );
			$cell = $table->addCell ( 1000 );
			if ($isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $state ), array (
						'color' => 'FF0000',
						'fgColor' => 'yellow',
						'bold' => true 
				) );
			} else {
				$cell->addText ( htmlspecialchars ( $state ) );
			}
			$cell = $table->addCell ( 1500 );
			$cell->addText ( htmlspecialchars ( $faultDectectionTime ) );
			$cell = $table->addCell ( 1680 );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $faultClearingTime ) );
			}
			$cell = $table->addCell ( 1400 );
			if (! $isMonitorStateActive) {
				$cell->addText ( htmlspecialchars ( $duration ) );
			}
		}
		$totalRowCount ++;
	} // Inner While loop.
}
function generateParagraphForDailyReport($section, $titleFontStyle, $textFontStyle, $mysqlDailyStart, $mysqlDailyEnd) {
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$db_Name = $GLOBALS ['dbName'];
	// static $rowCounterForFailure = 0;
	$query = "SELECT accusedHostName, t.failureCode, failureDesc, monitorState, t.correlationDate, t.lastUpdate, t.idFlightLeg, flightNumber, departureAirportCode, arrivalAirportCode, createDate 
                    FROM (
                        SELECT accusedHostName, failureCode, monitorState, correlationDate, a.lastUpdate, a.idFlightLeg, c.flightNumber, c.departureAirportCode, c.arrivalAirportCode, c.createDate
                        FROM $db_Name.BIT_failure a
                        INNER JOIN $db_Name.SYS_flightPhase b
                            ON a.idFlightLeg = b.idFlightLeg
                        INNER JOIN $db_Name.SYS_flight c
                            ON a.idFlightLeg = c.idFlightLeg                        
                        AND b.idFlightPhase = 5 
                        AND a.correlationDate >= b.startTime 
                        AND a.correlationDate <= b.endTime
                        AND (
                                ('$mysqlDailyStart' <= c.createDate AND '$mysqlDailyEnd' >= c.lastUpdate)
                                OR
                                ('$mysqlDailyStart' <= c.lastUpdate AND '$mysqlDailyEnd' >= c.createDate)
                            )
                    ) AS t, $commonDbName.sys_failureinfo b
                    WHERE t.failureCode = b.failureCode
                    ORDER BY correlationDate";
	
	// echo "<br><br>---$query---</br></br>";
	// $section->addText(htmlspecialchars('query: '.$query), $titleFontStyle);
	// $section->addTextBreak(1, $textFontStyle);
	
	$section->addText ( htmlspecialchars ( 'Failures recorded in cruise mode' ), $titleFontStyle );
	
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	if ($result != false) {
		$count = mysqli_num_rows ( $result );
		if ($count > 0) {
			$currentFlightNumber = '*****';
			/*
			 * static $tableCounter = 0;
			 * static $tCounter = 0;
			 */
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$flightNumber = $row ['flightNumber'];
				/*
				 * $tableCounter ++;
				 * $tCounter++;
				 */
				if ($currentFlightNumber != $flightNumber) {
					// create section
					$idFlightLeg = $row ['idFlightLeg'];
					$departureAirportCode = $row ['departureAirportCode'];
					$arrivalAirportCode = $row ['arrivalAirportCode'];
					$createDate = $row ['createDate'];
					
					$section->addTextBreak ( 2, $textFontStyle );
					$section->addText ( htmlspecialchars ( 'Flight Leg id #' . $idFlightLeg . ' / ' . $flightNumber . ' / ' . $departureAirportCode . ' - ' . $arrivalAirportCode . ' / ' . $createDate . '' ), $titleFontStyle );
					$section->addTextBreak ( 1, $textFontStyle );
					
					// Create table
					$table = $section->addTable ( 'Details Table' );
					$table->addRow ();
					$cell = $table->addCell ( 700 );
					$cell->addText ( htmlspecialchars ( '#' ), $titleFontStyle );
					$cell = $table->addCell ( 1750 );
					$cell->addText ( htmlspecialchars ( 'LRU Name' ), $titleFontStyle );
					$cell = $table->addCell ( 1750 );
					$cell->addText ( htmlspecialchars ( 'failure Code' ), $titleFontStyle );
					$cell = $table->addCell ( 4000 );
					$cell->addText ( htmlspecialchars ( 'Description' ), $titleFontStyle );
					$cell = $table->addCell ( 1000 );
					$cell->addText ( htmlspecialchars ( 'State' ), $titleFontStyle );
					$cell = $table->addCell ( 2000 );
					$cell->addText ( htmlspecialchars ( 'Correlation Date' ), $titleFontStyle );
					$cell = $table->addCell ( 2000 );
					$cell->addText ( htmlspecialchars ( 'Last Update' ), $titleFontStyle );
					
					$currentFlightNumber = $flightNumber;
					$i = 0;
					$totalRowCount = 1;
				}
				// $totalRowCount = $rowCounterForFailure;
				// if($tableCounter == $tCounter) {
				if ($totalRowCount % 2 != 0) {
					// echo "test";
					$table->addRow ();
					$cell = $table->addCell ( 700, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $i ++ ) );
					$cell = $table->addCell ( 1750, array (
							'bgColor' => 'C0C0C0' 
					) );
					$lruName = $row ['accusedHostName'];
					// echo "----LRU:----".substr($lruName,0,3);
					if ((substr ( $lruName, 0, 3 ) == 'DSU') or (substr ( $lruName, 0, 4 ) == 'LAIC') or (substr ( $lruName, 0, 4 ) == 'AVCD') or (substr ( $lruName, 0, 4 ) == 'ADBG')) {
						$cell->addText ( htmlspecialchars ( $lruName ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $lruName ) );
					}
					// $cell->addText ( htmlspecialchars ( $row ['accusedHostName'] ) );
					$cell = $table->addCell ( 1750, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $row ['failureCode'] ) );
					$cell = $table->addCell ( 4000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $row ['failureDesc'] ) );
					$cell = $table->addCell ( 1000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$state = getMonitorStateDesc ( $row ['monitorState'] );
					if ($state == 'Active') {
						$cell->addText ( htmlspecialchars ( $state ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $state ) );
					}
					$cell = $table->addCell ( 2000, array (
							'bgColor' => 'C0C0C0' 
					) );
					$cell->addText ( htmlspecialchars ( $row ['correlationDate'] ) );
					$cell = $table->addCell ( 2000, array (
							'bgColor' => 'C0C0C0' 
					) );
					if ($state == 'Inactive') {
						$cell->addText ( htmlspecialchars ( $row ['lastUpdate'] ) );
					}
				} else {
					
					$table->addRow ();
					$cell = $table->addCell ( 700 );
					$cell->addText ( htmlspecialchars ( $i ++ ) );
					$cell = $table->addCell ( 1750 );
					$lruName_1 = $row ['accusedHostName'];
					if ((substr ( $lruName_1, 0, 3 ) == 'DSU') or (substr ( $lruName_1, 0, 4 ) == 'LAIC') or (substr ( $lruName_1, 0, 4 ) == 'AVCD') or (substr ( $lruName_1, 0, 4 ) == 'ADBG')) {
						$cell->addText ( htmlspecialchars ( $lruName_1 ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $lruName_1 ) );
					}
					// $cell->addText ( htmlspecialchars ( $row ['accusedHostName'] ) );
					$cell = $table->addCell ( 1750 );
					$cell->addText ( htmlspecialchars ( $row ['failureCode'] ) );
					$cell = $table->addCell ( 4000 );
					$cell->addText ( htmlspecialchars ( $row ['failureDesc'] ) );
					$cell = $table->addCell ( 1000 );
					$state = getMonitorStateDesc ( $row ['monitorState'] );
					if ($state == 'Active') {
						$cell->addText ( htmlspecialchars ( $state ), array (
								'color' => 'FF0000',
								'fgColor' => 'yellow',
								'bold' => true 
						) );
					} else {
						$cell->addText ( htmlspecialchars ( $state ) );
					}
					$cell = $table->addCell ( 2000 );
					$cell->addText ( htmlspecialchars ( $row ['correlationDate'] ) );
					$cell = $table->addCell ( 2000 );
					if ($state == 'Inactive') {
						$cell->addText ( htmlspecialchars ( $row ['lastUpdate'] ) );
					}
				}
				$totalRowCount ++;
				// }
			}
		} else {
			$section->addText ( htmlspecialchars ( 'None' ), $italicFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR: ' . $query ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
}
function getFaultsforFailure($dbhandle, $section, $textFontStyle, $lrus, $failureCode) {
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	                                 // echo $dbName;
	                                 // display faults related to that failure for each lru
	$query = "SELECT faultRegex FROM bit_faulttofailuremapping WHERE failureCode = $failureCode";
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	
	if ($result) { // Not all the dumps have all this table
		$row = mysqli_fetch_array ( $result );
		$regex = $row ['faultRegex'];
		
		if ($regex != '') { // Not all offloads have this table - For example offloads from DSU1 will have it whereas from DSU2 there is nothing
			foreach ( $lrus as $lru ) {
				$query = "SELECT DISTINCT t.faultCode, b.faultDesc 
                                FROM BIT_fault t, $commonDbName.sys_faultinfo b
                                WHERE t.hostName = '$lru' AND t.faultCode REGEXP '$regex' AND t.faultCode = b.faultCode ";
				
				$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
				
				if ($result) {
					if (mysqli_num_rows ( $result ) > 0) {
						$j = 0;
						while ( $row = mysqli_fetch_array ( $result ) ) {
							if ($j > 0) {
								$faults .= ', ';
							} else {
								$faults = "- " . $lru . " reporting following fault(s): ";
							}
							
							$faults .= $row ['faultDesc'];
							$j ++;
						}
					} else {
						$faults = "- " . $lru . " reporting no faults.";
					}
					
					$section->addText ( htmlspecialchars ( $faults ), $textFontStyle );
				} else {
					$section->addText ( htmlspecialchars ( '=> ERROR with query: ' . $query . " - mysql error:" . mysqli_error ( $GLOBALS ['dbConnection'] ) ), $titleFontStyle );
					$section->addTextBreak ( 1, $textFontStyle );
				}
			}
			$section->addTextBreak ( 1, $textFontStyle );
		}
	} else {
		$section->addText ( htmlspecialchars ( '=> ERROR with query: ' . $query . " - mysql error:" . mysqli_error ( $GLOBALS ['dbConnection'] ) ), $titleFontStyle );
		$section->addTextBreak ( 1, $textFontStyle );
	}
	$section->addTextBreak ( 1, $textFontStyle );
} // End of getFaultsforFailure.
  
// Retrieve post failure error.
function getFaultsforPostFailure($dbhandle, $section, $textFontStyle, $lrus, $failureCode) {
	// Getting dbName.
	$commonDbName = $GLOBALS ['db']; // BAnalytics Database
	$db_Name = $GLOBALS ['dbName'];
	
	static $counter = 0;
	/* if($regex != '') { */
	// Not all offloads have this table - For example offloads from DSU1 will have it whereas from DSU2 there is nothing
	foreach ( $lrus as $lru ) {
		
		$query = "SELECT DISTINCT t.faultCode, b.faultDesc,count(t.faultCode)
                	          FROM $db_Name.BIT_fault t, $commonDbName.sys_faultinfo b
                	          WHERE t.hostName = '$lru' AND t.faultCode = b.faultCode ";
		// echo "<br><br>Query:---$query<br><br>";
		
		$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
		// echo "--Test-- ".count($result);
		if ($result) {
			
			if (mysqli_num_rows ( $result ) > 0) {
				$j = 0;
				while ( $row = mysqli_fetch_array ( $result ) ) {
					$faultCode = '';
					$faultCode = $row ['faultCode'];
					// echo "--Test2--$faultCode---$lru";
					
					if ($faultCode != '42001003' or $faultCode != '42001005') {
						$counter ++;
						// echo "--TestCounter:-- " . $counter;
						if ($j > 0) {
							$faults .= ', ';
						} else {
							$faults = "- " . $lru . " reporting following fault(s): ";
						}
						
						$faults .= $row ['faultDesc'];
						// echo "FaultDesc:- $faults";
						$j ++;
					}
				}
			} else {
				$faults = "- " . $lru . " reporting no faults.";
			}
			// echo "FaultDesc:- $faults";
			$section->addText ( htmlspecialchars ( $faults ), $textFontStyle );
		} else {
			$section->addText ( htmlspecialchars ( '=> ERROR with query: ' . $query . " - mysql error:" . mysqli_error ( $GLOBALS ['dbConnection'] ) ), $titleFontStyle );
			$section->addTextBreak ( 1, $textFontStyle );
		}
	}
	$section->addTextBreak ( 1, $textFontStyle );
	/*
	 * }
	 * }
	 *
	 * else {
	 * $section->addText(htmlspecialchars('=> ERROR with query: '.$query." - mysql error:". mysql_error($dbhandle)), $titleFontStyle);
	 * $section->addTextBreak(1, $textFontStyle);
	 * }
	 * $section->addTextBreak(1, $textFontStyle);
	 */
}
function getFlightLegRoute($flightLegId) {
	$query = "SELECT departureAirportCode, arrivalAirportCode, idFlightLeg 
                    FROM SYS_flight
                    WHERE idFlightLeg = $flightLegId";
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	$row = mysqli_fetch_array ( $result );
	
	return $row ['departureAirportCode'] . " - " . $row ['arrivalAirportCode'];
}
function getFlightPhase($date) {
	$query = "SELECT idFlightPhase 
                    FROM SYS_flightPhase
                    WHERE startTime <= '$date' AND '$date' <= endTime";
	$result = mysqli_query ( $GLOBALS ['dbConnection'], $query );
	$row = mysqli_fetch_array ( $result );
	if (mysqli_num_rows ( $result ) > 0) {
		$flightPhase = $row ['idFlightPhase'];
	} else {
		$flightPhase = '-';
	}
	
	return $flightPhase;
}
?>
