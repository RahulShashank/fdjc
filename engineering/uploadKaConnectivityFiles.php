<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 0);
date_default_timezone_set("Pacific");

require_once "../database/connecti_database.php";
require_once "../database/connecti_mongoDB_Ka.php";
require_once("../common/validateUser.php");

MongoCursor::$timeout = -1;

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$airlineId = $_REQUEST['airlineId'];
//$username = "root";
//$password = "";
//$hostname = "10.76.108.177";
//$mainDB = "irv_thiru_con";


//Flags for Recording data initially set to 0 - means not recording 1 - means recording
$isAltitudeRecordingOn = '0';
$isWifiOnTimeRecording = '0';
$isWifiOffTimeRecording = '0';
$isOmtsOffTimeRecording = '0';
$isOmtsOnTimeRecording = '0';
$isOmtsRestrictTimeRecording = '0';
$isWifiRestrictedAreaRecording = '0';
$recordClimbTimeAsStartTime = '0';
$recordDecentTimeAsEndTime = '0';
$recordLatLonElementFrequency = 0;
$intervalLatLon = 20;

//Get iterations

//Variables for SBB
/*
$iterationSBB1 = 0;
$iterationARP = 0;
$totalActiveUser = 0;
$userValue = 0;
$iterationFlight = 0;
$prevLatitude = 'stuck';
$prevLongitude = 'stuck';
$iterationsAntenna = 0;
$iterationsSDUCC1 = 0;
$iterationsNCU_GisDB = 0;
$iterationsGSMstartupCC1 = 0;
$iterationsWiFi_GSM_disconnect = 0;
$iterations_NCU_DSU_down = 0;
$iterationsWiFi_FALSE = 0;
$AirspaceCheck = 'restrictedAirspaceRegion';
$RestrictedAirspace = 0;
$iterationsOMTS_groundTest = 0;
$iterationsSNMPservice_down =0;
$iterations_SystemMode = 0;
$iterations_SwitchOff = 0;
$iterations_OMTS_DSU3_down = 0;
$iterations_OMTS_ADBG_down = 0;
$iterations_GSM_MIB_down = 0;
$iterations_WiFi_autoServiceEnableFalse = 0;
$iterations_WiFi_FapDisabled = 0;
$iterations_NCU_ADBG_down = 0;
$iterations_NCU_KO = 0;
$iterations_DSU_P5 = 0;
$iterations_AVCD = 0;
$iterations_NCU_LedOrErrorMsg = 0;
$iterations_ProcessSW = 0;
$iterations_SDU_health = 0;
$iterationsSDUCC1_v2 = 0;
$iterationsSDUCC2_v2 = 0;
$iterationsSDU_Ant_bus = 0;
$iterations_DLNA = 0;
$iterationsAntenna_v2 = 0;
$iterations_nslookup = 0;
$iterations_groundFailure = 0;
*/

//Variables for Ka
$iterations_structureBlockage = 0;
$iterations_notReady = 0;
$iterations_noLine = 0;
$iterations_dataLink = 0;
$iterationDSU1 = 0;
$iterationDSU2 = 0;
$iterationDSU3 = 0;
$iterationWAP1 = 0;
$iterationWAP2 = 0;
$iterationWAP3 = 0;
$iterationWAP4 = 0;
$iterationWAP5 = 0;
$iterationWAP6 = 0;
$iterationMODMAN = 0;
$iterationKANDU = 0;
$iterationOAE = 0;


//altitude 10K ft
$altThreshold=10000;
$totalTimeDurationOn = 0;
$totalTimeDurationOff = 0;
$prevFlightPhase = 'descentapproach';

$wifiOnArray = array();
$wifiOffArray = array();
$altitudeAbove10KArray = array();
$totalDurationOnArray = array();
$totalDurationOffArray = array();
$trajectoryArray = array();
$wifiAvailabilityEventsArray = array();
$omtsAvailabilityEventsArray = array();
$altitudeEventArray = array();
$wifiAvailabilityArray = array();
$omtsAvailabilityArray = array();
$tempWifiOffArray = array();
$tempOmtsOffArray = array();
$flightFailure = '';
 
 
 
$ds = DIRECTORY_SEPARATOR;
$storeFolder = 'uploads';
//BeR 28Feb18: increasing noOfLinesFromEOF to 60 as with Ka log tool version we have more lines of logging. It needs a bigger sample to catch the last two MODMAN_Monitoring iterations (see function isSelectedFileHasBeenUploadedBefore)
$noOfLinesFromEOF = 60;
$aircraftId = $_REQUEST['aircraftId'];

$query = "SELECT tailsign, platform, isp FROM aircrafts a WHERE a.id = $aircraftId";
$result = mysqli_query($dbConnection, $query);
if($result && mysqli_num_rows($result) > 0) {
	$row = mysqli_fetch_array($result);	
	$tailSign = $row['tailsign'];
	$platform = $row['platform'];
	$isp = $row['isp'];	
	$platformParser = $isp . "_" . $platform; 	
} else {
	 echo "<br>error: ".mysqli_error($dbConnection);
	 exit;
}


if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT tailsign, databaseName FROM aircrafts WHERE id = $aircraftId";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $tailSign = $row ['tailsign'];	  
      $dbName = $row['databaseName'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

echo "<img src=\"../img/ok.png\" style=\"vertical-align:top\">&nbsp;&nbsp;Reading data for <b>$tailSign</b>. Detected platform is <b>$platform</b> and ISP is <b>$isp</b>.<br><br>";
//echo " PlatformParser - $platformParser<br>";

$dbConnection = connectMysqlDatabase($hostname, $username, $password, $dbName);

// Get user uid. We are going to upload in a specific directory for each user.
$uid = $auth->getSessionUID($hash);

if (!empty($_FILES)) {
	$tempFile = $_FILES['file']['tmp_name']; 
	//var_dump($_FILES);
	echo "<br>";
	//echo "file $tempFile<br><br>";       
    $targetPath = dirname ( dirname( __FILE__ ) ) . $ds. $storeFolder . $ds . $uid . $ds;
	
    //echo "Upload in " . $targetPath ."<br><br>";

	if (!file_exists($targetPath)) {
	    mkdir($targetPath, 0755);
	} else {
	    // echo "The directory $targetPath exists.";
	    // Make sure all files and directories are deleted in the uploads directory
    	recursiveRemoveDirectory($targetPath);
	}

    $targetFileName =  $_FILES['file']['name'];
	//echo "target file name is " . $targetFileName ."<br><br>";
    $targetFile =  $targetPath. $_FILES['file']['name'];
	//echo "target file is " . $targetFile ."<br><br>";
    $targetFileType = pathinfo($targetFile, PATHINFO_EXTENSION);
	//echo "target file type is " . $targetFileType ."<br><br>";
    $moved = move_uploaded_file($tempFile, $targetFile);
	//echo "moving log function binary result " . $moved ."<br><br>";
	if($moved) {
		echo "<img src=\"../img/ok.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$targetFileName</b> successfully transfered to server.<br>";
	} else {
		echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$tempFile</b> not correctly transfered to server.<br>";
		
		switch( $_FILES['file']['error'] ) {
			case UPLOAD_ERR_OK:
				$message = false;;
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$message .= ' - file too large.';
				break;
			case UPLOAD_ERR_PARTIAL:
				$message .= ' - file upload was not completed.';
				break;
			case UPLOAD_ERR_NO_FILE:
				$message .= ' - zero-length file uploaded.';
				break;
			default:
				$message .= ' - internal error #'.$_FILES['newfile']['error'];
				break;
		}
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;PHP message: $message"; exit;
	}

    //echo "<hr>";
} else {
	echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$tempFile</b> not correctly transfered to server.<br><br>";
	
	switch( $_FILES['file']['error'] ) {
		case UPLOAD_ERR_OK:
			$message = false;;
			break;
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			$message .= ' - file too large (limit of '.get_max_upload().' bytes).';
			break;
		case UPLOAD_ERR_PARTIAL:
			$message .= ' - file upload was not completed.';
			break;
		case UPLOAD_ERR_NO_FILE:
			$message .= ' - zero-length file uploaded.';
			break;
		default:
			$message .= ' - internal error #'.$_FILES['newfile']['error'];
			break;
	}
	
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;PHP message: $message"; exit;
}

function recursiveRemoveDirectory($directory) {
	foreach(glob("{$directory}/*") as $file) {
		if(is_dir($file)) {
			recursiveRemoveDirectory($file);
		} else {
			unlink($file);
		}
	}
	
}

if($targetFileType == "zip") {
	$zip = new ZipArchive;
	$res = $zip->open($targetFile);
	if($res === TRUE) {
		echo "<img src=\"../img/ok.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$targetFileName</b> successfully unzipped.<br>";
		$zip->extractTo($targetPath);
		$zip->close();

		// Delete file from upload folder
		recursiveRemoveDirectory($targetFile);unlink($targetFile);
	} else {
		echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Error while unzipping <b>$targetFileName</b>.<br>";
		// Delete file from upload folder
		recursiveRemoveDirectory($targetFile);unlink($targetFile);
	}
}

 
function writeIntoMongoDb($document,$timeStamp,$collectionActivity,$tailSign)
{
	   
	//$queryCriteria = array('$and' => array( array('timestamp' => array('$eq' => $timeStamp)), array('tailSign' => array('$eq' => $tailSign))));
	//$options = array('upsert' => true);
	#var_dump($queryCriteria);
	//$result = $collectionActivity->update($queryCriteria,$document,$options);

	//var_dump($document);
	//echo "into writeMongo writing document:<br>";
	//echo var_dump($document).'<br>';
	//echo "into writeMongo timestamp is " . $timeStamp ."<br>";
	//echo "into writeMongo tail sign is " . $tailSign ."<br>";
	
	//BeR: 11Aug17 - changing write to insert only as update was in double by doing another check on existing data and is very slow
	$result = $collectionActivity->insert($document);
	
	//echo var_dump($result).'<br>';
	//Manoj Beging: Added the below on 1/12/2017 to fix the mongo timeout error
	//$result->timeout(-1); 
	//Manoj End
		
}

function isElementFoundInMongoDb($timeStamp, $collectionActivity, $tailSign)
{
	//echo "the Timestamp in search is $timeStamp<br>";
	$queryCriteria = array('timestamp' => array('$eq' => $timeStamp));
	//BeR: 11Aug17 - updating query to include check in tail sign
	//$queryCriteria = array('$and' => array( array('timestamp' => array('$eq' => $timeStamp)), array('tailSign' => array('$eq' => $tailSign))));
	//echo var_dump($queryCriteria).'<br>';
	$cursor = $collectionActivity->find($queryCriteria);
	//echo var_dump($cursor).'<br>';
	//Manoj Beging: Added the below on 10/04/2016 to fix the mongo timeout error
	$cursor->timeout(-1); 
	//Manoj End
	foreach ($cursor as $doc) {
		return sizeof($doc);
	}
}

function afterString ($this, $inthat)
{

	if (!is_bool(strpos($inthat, $this)))
	return substr($inthat, strpos($inthat,$this)+strlen($this));
}
	
	
function emptyConnectivityActivityCollection()
{
	$mongoDB = new Mongo(); 
	$db = $mongoDB->connectivityLogs;	
	
	$collection = $db->connectivityActivity;	
	$response = $collection->drop();
	
	$collection = $db->connectivityEvents;	
	$response = $collection->drop();
}

function readLinesFromFile($file, $numLines = 100) 
{ 
	try{
		$fp = fopen($file, "r"); 
	}
	catch(Exception $e) {
	}
	if($fp == "") {
		echo "<br><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;There is a problem opening the file <b>$file</b>.<br>";
		return "";
	}
	
    $chunk = 4096; 
    $fs = sprintf("%u", filesize($file)); 
    $max = (intval($fs) == PHP_INT_MAX) ? PHP_INT_MAX : filesize($file); 

	//BeR - 11Aug17. Need to understand purpose of below for loop. Some log files are being eliminated because of it even though their formatting look fine.
    for ($len = 0; $len < $max; $len += $chunk) { 
        $seekSize = ($max - $len > $chunk) ? $chunk : $max - $len; 

        fseek($fp, ($len + $seekSize) * -1, SEEK_END); 
        $data = fread($fp, $seekSize) . $data; 
		
        if (substr_count($data, "\n") >= $numLines + 1) { 
            preg_match("!(.*?\n){".($numLines)."}$!", $data, $match); 
            fclose($fp); 
			//echo "hit the weird check"."<br>";
            return $match[0]; 
        } 
    } 
    fclose($fp); 
    return $data; 
} 

function isSelectedFileHasBeenUploadedBefore($fileString,$platformParser,$noOfLinesFromEOF,$collectionActivity,$tailSign)
{
	
	for($i=1;$i<= $noOfLinesFromEOF;$i++)
	{
		
		$data = readLinesFromFile($fileString,$i);
		//echo "result from readLinesFromFile function is " . $data ."<br><br>";
		
		if($data == "") {
			// no data to read;
			echo "Issue in data opening or formatting. Those data won't be loaded" . "<br>";
			//BeR - 11Aug17: changing to return 1 so that the log file is considered as already loaded hence as if eliminated.
			return;
			//return 1;
		}
		
		//BeR 6Mar18: changing to platformparser for MAU = KaNoVAR_AVANT
		if( ($platformParser == 'KaNoVAR_AVANT') )
		{
			//Take into consideration of Last But one Line dataset only ignore last dataset for any long file
			//BeR 9Jan17: changing string comparison to MODMAN to adjust to Ka log tool
			if ((strpos($data,'MODMAN_Monitoring') !== false) && (substr_count($data,"MODMAN_Monitoring") > 1))
			{
				$data1 = explode(',', $data);
				//echo $data1[0] . "<br>";
				$timeStampForFile = convertToMongoDBTime($data1[0]);
				//echo $data1[1] . "<br>";
				//echo $data1[2] . "<br>";
				//echo "time stamp for checking existing data is $timeStampForFile<br>";

				$size = isElementFoundInMongoDb("$timeStampForFile",$collectionActivity,$tailSign);
				if($size > 0)
				{
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/warning.png\">&nbsp;&nbsp;The data has already been uploaded into the Database. It is not going to be uploaded.";
					return 1;
				}
				else
				{
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ok.png\">&nbsp;&nbsp;This is new data to be uploaded.<br/>";
					return 0;
				}

			}
		}
		elseif($platformParser == 'Gogo_AVANT')
		{
			//Take into consideration of Last But one Line dataset only ignore last dataset for any long file
			if ((strpos($data,'THALES_FLIGHT') !== false) && (substr_count($data,"THALES_FLIGHT") > 1))			
			{
				$data1 = explode(',', $data);
				//echo $data1[0] . "<br>";
				$timeStampForFile = convertToMongoDBTime($data1[0]);
				//echo "$timeStampForFile";
				$size = isElementFoundInMongoDb("$timeStampForFile",$collectionActivity);
				if($size > 0)
				{
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ko.png\">&nbsp;&nbsp;Log File Already Uploaded into Database";
					return 1;
				}
				else
				{
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ok.png\">&nbsp;&nbsp;This is a New Log file to be uploaded";
					return 0;
				}
			
			}		
		}
		else
		{
			echo "<img src=\"../img/ko.png\">&nbsp;&nbsp;Internet Service Provider not recognized.<br>";
		}
	}
}


//////////////// BEGINING OF CODE

// following function used during development
//emptyConnectivityActivityCollection();	

echo "The Start Time Stamp is " . date("Y/M/d:H:i:s") . "<br><br>";

$nbOfReadFiles = 0;

if (is_dir($targetPath))
{	
	//BeR 21Mar18: initializing first time stamp
	$firstTimeStamp_Final = date("Y-m-d h:i:sa");	

  if ($dh = opendir($targetPath))
  {    
    while (($file = readdir($dh)) !== false)
	{
		//echo "<br> file=$file <br> ";
		//echo "<br> Target path=$targetPath <br> ";
		//Manoj added the below to support .gz files as well 1st July, 2016
		$fileExt = pathinfo($file, PATHINFO_EXTENSION);
		//echo "<br> fileExt=$fileExt <br> ";
		if($fileExt == "gz")
		{
			$fileLoc = $targetPath . $file;
			//echo "====>File to be unzipped is $fileLoc";
			$f = file_get_contents($fileLoc);
			$uncompressed = gzdecode($f);
			$filename = chop($file,".gz"); 
			$fileLoc = $targetPath . $filename;
			$myfile = fopen($fileLoc, "w") or die("Unable to open file!");
			fwrite($myfile, $uncompressed);
			fclose($myfile);
			$file=$filename;
		}
		// Manoj - End of the code
		
		//echo "file variable is " . $file ."<br><br>";
		
		$test_explode = explode(".", $file);
		//echo "test_explode = " . $test_explode ."<br><br>";
		
		$test_substr = substr( $file, 0, 7 );
		//echo "test_substr = " . $test_substr ."<br><br>";
			
		$tempfile=$_FILES['file']['name'];
		$filelog=explode(".", $tempfile);
		
		//BeR 9Jan17: changing substr pattern to ka_conn which should be the commonality in ka log tool files
		if( ($file != '.') && ($file != '..') && ($filelog[1] == 'log') && (substr( $file, 0, 7 ) === "ka_conn"))
		{			
			//$connectivityFileName = $targetPath . $ds . $file;
			$connectivityFileName = $targetPath . $file; //Manoj Modified this on 1st July, 2016. other wise one extra / is appending after uid.
			echo "ConnectivityFileName variable is " . $connectivityFileName ."<br><br>";
		//	echo "<hr>";
			echo "<br><img src=\"../img/document.png\" style=\"vertical-align:top\">&nbsp;&nbsp;Reading data for <b>$file</b>.<br>";
					
			$isFileAlreadyUploaded = isSelectedFileHasBeenUploadedBefore(	$connectivityFileName,
																			$platformParser,
																			$noOfLinesFromEOF,
																			$collectionActivity,
																			$tailSign);
			
			//echo "<br><br> isFileAlreadyUploaded binary result is " . $isFileAlreadyUploaded ."<br><br>";
			

			if(!$isFileAlreadyUploaded)
			{
				// Open The LOG file and take the handle
				 if (($fileHandle = fopen("$connectivityFileName", "r")) !== FALSE) 
				 {
					$nbOfReadFiles++;
					$isFirstRowOfFile = 1;
					$firstTimeStamp = "";
					$lastTimeStamp = "";
					$sectionArray = array();
					
					$keyString=0;
					$isFirstArray = 1;
					 while (($data = fgetcsv($fileHandle, 10000, ",")) !== FALSE) 
					 {
						$message = "";
					  
						$num = count($data);
						$event = $data[1];
						$myParsedData = array();
						
						//echo "This is inside file $connectivityFileName: $data[0] and $data[1] and $num<br>";
						//echo "The Plaform Parser is : " . $platformParser . "Event is " . $event . "<br>";

						//echo "platform parser is $platformParser<br>";
						//echo "event is $event<br>";
						
						//echo "var dump of sectionArray:<br>";
						//echo var_dump($sectionArray).'<br>';
						
						
						//BeR 11Jan18: adding MODMAN_Monitoring condition
						//BeR 6Mar18: adjusting to platformparser for MAU only for now
						if( ($platformParser == "KaNoVAR_AVANT" && $event == 'MODMAN_Monitoring') )
						{ 
							//BeR 11Jan18: Ka logs writing in mongoDB working ok except for "load average" field. To be re-worked in Ka log tool script
							//$temp_sectionArray_count = count($sectionArray);
							//echo "count sectionArray is $temp_sectionArray_count<br>";
							if(count($sectionArray) > 0 ) {
								if($isFirstRowOfFile != 1){
									$temp = $sectionArray['timestamp'];
									//echo "Entering mongoWrite call. Writing timestamp $temp<br>";
									writeIntoMongoDb(	$sectionArray,
														$sectionArray['timestamp'],
														$collectionActivity,
														$tailSign);	
														
									unset($sectionArray);
									$sectionArray = array();
								} else {
									$firstTimeStamp = $sectionArray['timestamp'];							
									$isFirstRowOfFile = 0;
								}
							}	

							$sectionArray['timestamp'] = convertToMongoDBTime($data[0]);
							$sectionArray['tailSign'] = $tailSign;	
						}
						
						
						for($i = 2 ; $i < $num ; $i++) {
							$keyAndValue = explode(":", $data[$i]);
							$key = trim($keyAndValue[0]);
							$key = explode(".", $key)[0]; // Need to explode again because some keys have a .0 which is not useful today...
							$value = trim($keyAndValue[1]);
							
							if( (stripos($key,'altitude') !== False) || (stripos($key,'longitude') !== False) || (stripos($key,'latitude') !== False))
							{							
								$integerIDs = json_decode($value, true);
								$value = (float)$integerIDs;
									
							}
							
							if($key != '') {
								$valueAsRequired = explode("\\", $value);
								$valueAsRequired = explode("\"", $valueAsRequired[0]);
								if($valueAsRequired[1] != null)
								{
									$myParsedData[$key] = $valueAsRequired[1];										
								}
								else
								{
									$myParsedData[$key] = $valueAsRequired[0];									
								}
							}
						}
						
						//echo "var dump of myParsedData:<br>";
						//echo var_dump($myParsedData).'<br>';
						$sectionArray[$event] = $myParsedData;
						
						$lastTimeStamp = convertToMongoDBTime($data[0]);							
					}
					
					// Store whatever we have as we have reached the end of the file
					/*if(count($sectionArray) > 0 ) {
					
						writeIntoMongoDb(	$sectionArray,
											$sectionArray['timestamp'],
											$collectionActivity);	
					}*/
					
					// Close the file
					fclose($fileHandle);
					//echo "<br>File timestamps: $firstTimeStamp - $lastTimeStamp<br>";
					
					if( ($firstTimeStamp != "") && ($lastTimeStamp != "") ) {
						//echo "<br>We can read the file !!";						
						//BeR 21Mar18: processing all file first in mongoDB then running computeKa at the end
						//include "computeKaConnectivityData.php";
						//echo "<br>firstTimeStamp in process loop is $firstTimeStamp<br>";
						//echo "<br>lastTimeStamp in process loop is $lastTimeStamp<br>";
						$firstTimeStamp_convert = strtotime($firstTimeStamp);
						//echo "<br>firstTimeStamp_convert in process loop is $firstTimeStamp_convert<br>";
						$firstTimeStamp_Final_convert = strtotime($firstTimeStamp_Final);
						//echo "<br>firstTimeStamp_Final_convert in process loop is $firstTimeStamp_Final_convert<br>";
						if($firstTimeStamp_convert < $firstTimeStamp_Final_convert){
							$firstTimeStamp_Final = $firstTimeStamp;
							//echo "<br>firstTimeStamp_Final is $firstTimeStamp_Final<br>";
						}
						$lastTimeStamp_convert = strtotime($lastTimeStamp);
						$lastTimeStamp_Final_convert = strtotime($lastTimeStamp_Final);
						if($lastTimeStamp_convert > $lastTimeStamp_Final_convert){
							$lastTimeStamp_Final = $lastTimeStamp;
							//echo "<br>lastTimeStamp_Final is $lastTimeStamp_Final<br>";
						}
					} else {
						echo "<br><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;The file doesn't content relevant data (i.e. too few lines). It is not going to be parsed.<br>";
					}
					
				} else {
					echo "<br><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;There is a problem opening the file <b>$file</b>.<br>";
				}
			}

		}		
	}
    closedir($dh);
	$firstTimeStamp = $firstTimeStamp_Final;
	$lastTimeStamp = $lastTimeStamp_Final;
	//echo "<br>firstTimeStamp before entering computeKa is $firstTimeStamp<br>";
	//echo "<br>lastTimeStamp before entering computeKa is $lastTimeStamp<br>";
	
	if( ($firstTimeStamp != "") && ($lastTimeStamp != "") ){
		//$result = $collectionActivity->createIndex(array('timestamp' => 1));
		include "computeKaConnectivityData.php";
		
		$currentDate=date("Y-m-d H:i:s");			
		$query = "INSERT INTO ". $GLOBALS['mainDB'].".connectivity_upload(id,airlineId,tailsign,aircraftId,filename,date) VALUES ('',$airlineId,'$tailSign','$aircraftId','$tempfile','$currentDate');";					
		$result = mysqli_query($dbConnection, $query);
						
		if(!$result){
		    echo "<br/> Error in Inserting the File Details in Connectivity Upload Table <br/> ";
		}
		mysqli_commit($dbConnection);
	}
  }
}

// Compute the data necessary for the BITE ground tool
//require_once("computeConnectivityData.php");
//echo "<hr>";
echo "<br/><br/><img src=\"../img/ok.png\" style=\"vertical-align:top\">&nbsp;&nbsp;End of Computing Connectivity Events...<br>";
					
echo "<br>The End Time Stamp is " . date("Y/M/d:H:i:s") . "<br><br/>";

//echo "Removing files from folder $targetPath";
recursiveRemoveDirectory($targetPath);

if($nbOfReadFiles < 1) {
	echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;No connectivity log files have been read.<br>";
	exit;
}

function convertToMongoDBTime($timestamp) {
//echo " The time stamp received is : $timestamp";
	$dateTime = new datetime($timestamp);
	$objDateTime = date_format($dateTime, 'Y-m-d H:i:s');
	
	return $objDateTime;
}


// Function to connect database
function connectMysqlDatabase($hostname, $username, $password, $dbName)
{	
		$dbConnection =  mysqli_connect ( $hostname, $username, $password, $dbName) or die ( "Unable to connect to MySQL" );
		
			// Check connection
		if (mysqli_connect_errno ()) 
		{
			echo "Failed to connect to MySQL: " . mysqli_connect_error ();
		}
		
		//Check wheather the connection is been established		
		if($dbConnection)
		{
			//echo " Connected to Mysql DB Successfully<br>";
		}
		Else
		{
			exit;
		}	
			
		// Set Autocommit to off
		mysqli_autocommit ( $dbConnection, FALSE );

		return $dbConnection;
}

	
//Read the Flight Leg from FlightPhase table
function readFlightLegForAltitudeEvent(&$dbConnection, $dbName, $altitudeEventStartTime)
{
	$query = "SELECT idFlightLeg FROM $dbName.SYS_flight WHERE createDate <= '$altitudeEventStartTime' AND lastUpdate >= '$altitudeEventStartTime'";	
	$result = mysqli_query($dbConnection, $query);
	
	if($result) 
	{
		$row = mysqli_fetch_array($result);
		$idFlightLeg = $row['idFlightLeg'];	
		
		echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ok.png\" style=\"vertical-align:top\">&nbsp;&nbsp;Storing data for Flight Leg <b>$idFlightLeg</b>.<br>";				
	} else {
		echo "<br>Problem while executing query $query<br><br>";
	}

	return $idFlightLeg;	
}

//write the connectivity status data on to mysql database
function writeConnectivityStatusForFlightLegToMysqlDb(&$dbConnection, $dbName, $idFlightLeg,$wifiOnPercentage,$omtsOnPercentage)
{
	 if($wifiOnPercentage >= 90)
	 {
		$wifiStatus = 0;
	 }
	 elseif($wifiOnPercentage >= 80 && $wifiOnPercentage < 90)
	 {
		$wifiStatus = 1;
	 }
	 else
	 {
		$wifiStatus = 2; 
	 }
	 
	 if($omtsOnPercentage >= 90)
	 {
		$omtsStatus = 0;
	 }
	 elseif($omtsOnPercentage >= 80 && $omtsOnPercentage < 90)
	 {
		$omtsStatus = 1;
	 }
	 else
	 {
		$omtsStatus = 2; 
	 }
	 
	 if($wifiStatus == 2 || $omtsStatus == 2)
	 {
		$flightLegConnectivityStatus = 2;
	 }
	 elseif($wifiStatus == 1 || $omtsStatus == 1)
	 {
		$flightLegConnectivityStatus = 1;
	 } 
	 else
	 {
		$flightLegConnectivityStatus = 0;
	 }
	 
	 $query = "UPDATE $dbName.flightstatus SET connectivityStatus = $flightLegConnectivityStatus WHERE idFlightLeg = '$idFlightLeg'";
	 
	//Check and commit the insertion into database
	if (mysqli_query($dbConnection, $query) == TRUE)
	{
		// Commit transaction
		mysqli_commit($dbConnection);
		
		//echo "Flight Leg Connectivity Status inserted successfully";
	}
	else 
	{
		echo "Error: " . $query . "<br>";
	}
	 
}

//write the connectivity status data on to mysql database
function writeConnectivityStatusForAircraftOnToMysqlDb(&$dbConnection, $dbName, $bAnalytics, $tailSign)
{
	$connectivityStatus = 0;
	$count = 0;
	$query = "SELECT connectivityStatus FROM $dbName.flightstatus";
	
	$result = mysqli_query($dbConnection, $query);
	
	if($result) 
	{
		while ($row = mysqli_fetch_array($result))
		{
			$connectivityStatus = $connectivityStatus + $row[0];
			$count++;			
			//echo 	"connectivityStatus is : " . $connectivityStatus  . "and Count is " . $count . "<br>";
		}
	}
	
	if($count != 0){
		$connectivityStatus = $connectivityStatus/$count;
	}else{
		$connectivityStatus = -1;
	}
	
	
	
	 if($connectivityStatus > 1)
	 {
		$aircraftConnectivityStatus = 2;
	 }
	 elseif($connectivityStatus > 0.5 && $connectivityStatus <= 1)
	 {
		$aircraftConnectivityStatus = 1;
	 }
	 else
	 {
		$aircraftConnectivityStatus = 0; 
	 }


	 //$query = "INSERT IGNORE INTO $bAnalytics.aircrafts(tailsign, connectivityStatus) VALUES ($tailSign, $aircraftConnectivityStatus)";
	$query = "UPDATE " . $GLOBALS['mainDB'] . ".aircrafts SET connectivityStatus=$aircraftConnectivityStatus WHERE tailsign = '$tailSign'";
	 
	//Check and commit the insertion into database
	if (mysqli_query($dbConnection, $query) == TRUE)
	{			
		// Commit transaction
		mysqli_commit($dbConnection);
	
		//echo "Aircraft Connectivity Status inserted successfully in Mysql DB";
	}
	else 
	{
		echo "Error: " . $query . "<br>";
	}
	 
}

//Write the data into the Mongodb database
function writeIntoConnectivityEventCollection($document,$idFlightLeg,$testTimeStamp,$collection)
{
	if( $idFlightLeg != "") {
		$queryCriteria = array('idFlightLeg' => array('$eq' => $idFlightLeg));
		$options = array('upsert' => true);
		$res = $collection->update($queryCriteria,
							$document,
							$options);
	}else{
		$queryCriteria = array('startTime' => array('$eq' => $testTimeStamp));
		$options = array('upsert' => true);
		$res = $collection->update($queryCriteria,
							$document,
							$options);	
	}
	
	//echo "Document inserted successfully<br>";

}


//Function for Wifi Restriected area start time recording
function wifiRestrictedAreaStartTime($timeStamp,$latitude,$longitude,&$tempWifiRestrictedAreaArray,&$tempWifiRestrictedAreaArrayCoordinates)
{
	$tempWifiRestrictedAreaArray['description'] = "WIFI RESTRICTED AREA";
	$tempWifiRestrictedAreaArrayCoordinates['startLatitude'] = $latitude;
	$tempWifiRestrictedAreaArrayCoordinates['startLongitude'] = $longitude;	   
	$tempWifiRestrictedAreaArray['startTime'] = $timeStamp;	
	
	//echo "The Wifi Restricted Area Start Time is : " . $timeStamp . "<br>" ;
}

//Function for Wifi Restriected area end time recording
function wifiRestrictedAreaEndTime($timeStamp, $latitude, $longitude, &$tempWifiRestrictedAreaArray, &$tempWifiRestrictedAreaArrayCoordinates, &$totalTimeDurationWifiRestrictedArea)
{
	$tempWifiRestrictedAreaArrayCoordinates['endLatitude'] = $latitude;
	$tempWifiRestrictedAreaArrayCoordinates['endLongitude'] = $longitude;	   
	$tempWifiRestrictedAreaArray['endTime'] = $timeStamp;			
	$tempWifiRestrictedAreaArray['location'] =  array(	"type" => "LineString", 
											"coordinates" => array(
												array($tempWifiRestrictedAreaArrayCoordinates['startLongitude'],$tempWifiRestrictedAreaArrayCoordinates['startLatitude']),
												array($tempWifiRestrictedAreaArrayCoordinates['endLongitude'],$tempWifiRestrictedAreaArrayCoordinates['endLatitude'])
										));			

	$timeFirstOmts  = strtotime($tempWifiRestrictedAreaArray['startTime']);
	$timeSecondOmts = strtotime($tempWifiRestrictedAreaArray['endTime']);
	$timeDurationOmtsOff = $timeSecondOmts - $timeFirstOmts;
	$totalTimeDurationWifiRestrictedArea = $totalTimeDurationWifiRestrictedArea + $timeDurationOmtsOff;
	
	//echo "The Wifi Restricted Area End Time is : " . $timeStamp . "<br>" ;
}

//Function for recording failure for wifi		
function failureRecordForWifiEvent($failureString, &$tempWifiOffArray)
{
	if( $tempWifiOffArray['computedFailure'] == "Unknown" ) {
		$tempWifiOffArray['computedFailure'] = "";
	}
	
	if(is_array($tempWifiOffArray))
	{
		/*$inString = implode(",",$tempWifiOffArray);
		if(stripos($inString,$failureString) != false){
			//echo 'Fault Already Found<br>';
		}
		else{*/
			//$tempWifiOffArray['computedFailure'] = $tempWifiOffArray['computedFailure'] . $failureString . ":";
			
			if ($tempWifiOffArray['computedFailure'] != ""){
				$tempWifiOffArray['computedFailure'] = $tempWifiOffArray['computedFailure'] ."/"."'". $failureString ."'".":";
			}else{
			$tempWifiOffArray['computedFailure'] = $tempWifiOffArray['computedFailure'] . $failureString . ":";
			}
			
			/*$tempWifiOffArray['computedFailure'] = implode(' ',array_unique(explode(',', $tempWifiOffArray['computedFailure'])));
			$tempWifiOffArray['computedFailure'] = substr_replace($tempWifiOffArray['computedFailure'], '', strrpos($tempWifiOffArray['computedFailure'], ','), strlen(','));*/
		//}
	}
	else
	{
		//$tempWifiOffArray = array();
		$tempWifiOffArray['computedFailure'] = $tempWifiOffArray['computedFailure'] . $failureString . ":";	
	}

}		

//Function for recording failure for OMTS	
function failureRecordForOmtsEvent($failureString,&$tempOmtsOffArray)
{
	if( $tempOmtsOffArray['computedFailure'] == "Unknown" ) {
		$tempOmtsOffArray['computedFailure'] = "";
	}
	
	if(is_array($tempOmtsOffArray))
	{
		/*$inString = implode(",",$tempOmtsOffArray);
		if(stripos($inString,$failureString) != false){
				//echo 'Fault Already Found<br>';
		}
		else
		{*/
			$tempOmtsOffArray['computedFailure'] = $tempOmtsOffArray['computedFailure'] . $failureString . ":";	
		//}
	}
	else
	{
		//$tempOmtsOffArray = array();
		$tempOmtsOffArray['computedFailure'] = $tempOmtsOffArray['computedFailure'] . $failureString . ":";	
	}
	
	
}

?>
