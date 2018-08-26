<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 2000);
date_default_timezone_set("GMT");

error_log("starting UploadDropzone");
if(!isset($_SESSION)){
	error_log("session in UploadDropzone was not started, starting it");
	session_start();
}

// echo "Start: ";
// printTime(); 

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/seatAnalyticsData.php";
require_once "../common/resetsReportFunctions.php";
require_once "../common/FlightFaultFunctions.php";

require_once ('checkEngineeringPermission.php');

require_once "../common/computeFleetStatusData.php";

//include ("biteXmlParser.php");
//include ("eventXmlParser.php");

// Set to true to avoid tailsign check in event xml parser
$_SESSION['disableTailsignCheck'] = true;

$ds = DIRECTORY_SEPARATOR;
$FILE_PROCESSED = 1;
$TAILSIGN_NOT_FOUND = 2;
$DB_NOT_FOUND = 3;

$storeFolder = 'upload_offloads';
$archiveFolder = 'Archive';
$processedFolder = 'Processed';
$rejectedFolder = 'Rejected';
$readXmlFiles = true;

// Get user uid. We are going to upload in a specific directory for each user.
$uid = $auth->getSessionUID($hash);

// Check if the user id is empty. If not, through error message.
if(empty($uid) || !isset($uid)) {
    error_log("User id is not set. It could be due to session expiry");
    echo "NO_UID";
    exit();
}


$airlineId = $_REQUEST['airlineId'];
error_log("airlineId from UploadDropzone is $airlineId");
$acronym = $_REQUEST['acronym'];
error_log("acronym from UploadDropzone is $acronym");

//BeR saving airlineId for process
$_SESSION['airlineId'] = $airlineId;
$_SESSION['acronym'] = $acronym;
$_SESSION['source'] = "Manual";

if (! empty($_FILES)) {
    error_log("ProcessOffloads entered -> Files are going to be processed");
    
    $tempFile = $_FILES['file']['tmp_name'];

    $targetPath = dirname(dirname(__FILE__)) . $ds . $storeFolder . $ds . $uid . $ds;
	//BeR saving targetPath for process
	$_SESSION['targetPath'] = $targetPath;

    $archivePath = dirname(dirname(__FILE__)) . $ds . $storeFolder . $ds . $archiveFolder . $ds;
	//BeR saving archivepath for process
	$_SESSION['archivePath'] = $archivePath;
    
    if (! file_exists($targetPath)) {
        mkdir($targetPath, 0755);
    }
    
	//BeR: saving all targetFile* details for process
    $targetFileName =  $_FILES['file']['name'];
	$_SESSION['targetFileName'] = $targetFileName;
    $targetFile =  $targetPath. $_FILES['file']['name'];
	$_SESSION['targetFile'] = $targetFile;
    $targetFileType = pathinfo($targetFile, PATHINFO_EXTENSION);
	$_SESSION['targetFileType'] = $targetFileType;

    $moved = move_uploaded_file($tempFile, $targetFile);
    if ($moved) {
        error_log("$targetFileName successfully transfered to server", 0);
        $fileSize = strval(round(filesize($targetFile) / 1024, 2));
        echo "<br/>File Size: " . $fileSize . "KB <br/>";
    } else {
        error_log("$tempFile not correctly transfered to server", 0);
        echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$tempFile</b> not correctly transfered to server.<br>";
        exit();
    }

	
	
	//BeR - NORMALLY ERASE FROM HERE TO JUST KEEP UPLOAD PART
	/*
    
    // Get the master tailsign data to check LRUs & SerialNumbers and find the tailsign
    $masterTailSignArray = getMasterTailSignData($dbConnection, $airlineId);

    // check if the zip file contains a folder and reject
//     checkIfZipContainsFolder($targetFileType, $targetFile);
    
    // Print Starting of the table
    printTableStart();
    
    if ($targetFileType == "zip") {
        $zip = new ZipArchive();
        $res = $zip->open($targetFile);
        if ($res === TRUE) {
            for($i = 0; $i < $zip->numFiles; $i++) {
                $fName = $zip->getNameIndex($i);
//                 $zip->extractTo($targetPath, array($fName));
//                 $extension = pathinfo($fName, PATHINFO_EXTENSION);
                $fileinfo = pathinfo($fName);
                $extension = $fileinfo['extension'];
                $filename = $fileinfo['basename'];
                $fileSize = 0;
                
                if(strcasecmp($extension, "tgz") == 0 or strcasecmp($extension, "xml") == 0 or strcasecmp($extension, "tar") == 0) {
                    try {
                        copyFile("zip://".$targetFile."#".$fName, $targetPath.$filename);
                        $fileSize = filesize($targetPath.$filename);

                        if($fileSize == 0) {
                            $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
//                             echo "going to copy zip://$targetFile$fName to archive folder $destFolder$filename.<br/>";
                            copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                            unlink($targetPath.$filename);
                            
                            $respArray = array();
                            $respArray['STATUS_CODE'] = 2;
                            $respArray['FAILURE_REASON'] = "Empty $extension File";
                            
                            // Update offloads_master table
                            updateOffloadMaster($filename, $fileSize, $respArray);
                            echo "<br/><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$filename</b> is empty.";
                            
                            continue;
                        }                        
                    } catch (Exception $e) {
                        echo "Exception in copying the file: " . $e;
                    }
                }
                
                if(strcasecmp($extension, "tgz") == 0 ) {
                    $tailsign = "";
                    extractTGZFiles($targetPath);
                    extractTARFile($targetPath);
                    $files = extractAndReadXMLFiles($targetPath);
                    $respArray = processFiles($files);
                    
                    $statusCode = $respArray["STATUS_CODE"];
                    $tailsign = $respArray["TAIL_SIGN"];
                    $dataArray = $respArray["DATA"];
                    if ($statusCode == 0) {
                        // Move the processed tgz file to Archive folder
                        $destFolder = $archivePath . $processedFolder . $ds . $tailsign . $ds;
//                         $zip->extractTo($destFolder, array($fName));
                        copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                    } else if ($statusCode > 1) {
                        // Move the tgz file to Rejected folder
                        $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
//                         $zip->extractTo($destFolder, array($fName));
                        copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                    }

                    // Print table data
                    foreach ($dataArray as $rowData) {
                        printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"], $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"]);
                    }
                    
                    // Update offloads_master table
                    updateOffloadMaster($filename, $fileSize, $respArray);
                } else if(strcasecmp($extension, "tar") == 0 ) {
                    $dir = "";
//                     $status = "";
                    extractTARFile($targetPath);
                    $files = extractAndReadXMLFiles($targetPath);
                    
                    // process the files
                    $respArray = processFiles($files);
                    
                    $statusCode = $respArray["STATUS_CODE"];
                    $tailsign = $respArray["TAIL_SIGN"];
                    $dataArray = $respArray["DATA"];
                    foreach ($dataArray as $rowData) {
                        printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"], $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"]);
                    }
                    if ($statusCode == 0) {
//                         $status = "Processed";
                        // Move the processed tgz file to Archive folder
                        $destFolder = $archivePath . $processedFolder . $ds . $tailsign . $ds;
//                         $zip->extractTo($destFolder, array($fName));
                        copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                    } else if ($statusCode > 1) {
//                         $status = "Rejected";
                        // Move the tgz file to Rejected folder
                        $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
//                         $zip->extractTo($destFolder, array($fName));
                        copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                    }
                    
                    // Update offloads_master table
                    updateOffloadMaster($filename, $fileSize, $respArray);
                } else if(strcasecmp($extension, "xml") == 0 ) {
                    $tailsign = "";
//                     $status = "";
                    $files = extractAndReadXMLFiles($targetPath);
                    $respArray = processFiles($files);
                    $statusCode = $respArray["STATUS_CODE"];
                    $tailsign = $respArray["TAIL_SIGN"];
                    $dataArray = $respArray["DATA"];
                    foreach ($dataArray as $rowData) {
                        printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"], $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"]);
                    }
                    if ($statusCode == 0) {
//                         $status = "Processed";
                        // Move the processed tgz file to Archive folder
                        $destFolder = $archivePath . $processedFolder . $ds . $tailsign . $ds;
//                         copyFile($targetFile, $destFile);
//                         $zip->extractTo($destFolder, array($fName));
                        copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                    } else if ($statusCode > 1) {
//                         $status = "Rejected";
                        // Move the tgz file to Rejected folder
                        $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
//                         copyFile($targetFile, $destFile);
//                         $zip->extractTo($destFolder, array($fName));
                        copyFile("zip://".$targetFile."#".$fName, $destFolder.$filename);
                    }
                    
                    // Update offloads_master table
                    updateOffloadMaster($filename, $fileSize, $respArray);
                    
                    $dir = pathinfo(current(array_keys($files)), PATHINFO_DIRNAME);
                    recursiveRemoveDirectory($dir);
                }
            }
            $zip->close();
            
            // Delete file from upload folder
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
        } else {
            echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Error while unzipping <b>$targetFileName</b>.<br>";
            // Delete file from upload folder
            unlink($targetFile);
        }
        recursiveRemoveDirectory($targetPath);
    } else if($targetFileType == "tgz") {
        $fileSize = filesize($targetFile);
        // check if the file size is 0
        if($fileSize == 0) {
            $destFile = $archivePath . $rejectedFolder . $ds  . $airlineId . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
            unlink($targetFile);
            
            $respArray = array();
            $respArray['STATUS_CODE'] = 2;
            $respArray['FAILURE_REASON'] = "Empty tgz File";
            
            // Update offloads_master table
            updateOffloadMaster($targetFileName, $fileSize, $respArray);
            echo "<br/><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$targetFileName</b> is empty.<br>";
            exit();
        }
        
        $tailsign = "";
//         $status = "";
        extractTGZFilesForProcessing($targetPath);
        extractTARFileForProcessing($targetPath);
        $files = extractAndReadXMLFiles($targetPath);
        $respArray = processFiles($files);
        
        $statusCode = $respArray["STATUS_CODE"];
        $tailsign = $respArray["TAIL_SIGN"];
        $dataArray = $respArray["DATA"];
        foreach ($dataArray as $rowData) {
            printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"], $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"]);
        }
        if ($statusCode == 0) {
//             $status = "Processed";
            // Move the processed tgz file to Archive folder
            $destFile = $archivePath . $processedFolder . $ds . $tailsign . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
        } else if ($statusCode > 1) {
//             $status = "Rejected";
            // Move the tgz file to Rejected folder
            $destFile = $archivePath . $rejectedFolder . $ds  . $airlineId . $ds . $targetFileName;
            try {
                copyFile($targetFile, $destFile);
            } catch (Exception $e) {
                echo "Exception after finisng process satus code > 1";
            }
        }
        
        // Update offloads_master table
        updateOffloadMaster($targetFileName, $fileSize, $respArray);
        
        $dir = pathinfo(current(array_keys($files)), PATHINFO_DIRNAME);
        delFiles($dir);
        recursiveRemoveDirectory($targetPath);
    } else if($targetFileType == "tar") {
        $fileSize = filesize($targetFile);
        // check if the file size is 0
        if($fileSize == 0) {
            $destFile = $archivePath . $rejectedFolder . $ds  . $airlineId . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
            unlink($targetFile);
            
            $respArray = array();
            $respArray['STATUS_CODE'] = 2;
            $respArray['FAILURE_REASON'] = "Empty tar File";
            
            // Update offloads_master table
            updateOffloadMaster($targetFileName, $fileSize, $respArray);
            echo "<br/><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$targetFileName</b> is empty.<br>";
            exit();
        }
        
        $dir = "";
//         $status = "";
        extractTARFileForProcessing($targetPath);
        $files = extractAndReadXMLFiles($targetPath);
        // process the files
        $respArray = processFiles($files);
        $statusCode = $respArray["STATUS_CODE"];
        $tailsign = $respArray["TAIL_SIGN"];
        $dataArray = $respArray["DATA"];
        foreach ($dataArray as $rowData) {
            printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"], $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"]);
        }
        
        if ($statusCode == 0) {
//             $status = "Processed";
            // Move the processed tgz file to Archive folder
            $destFile = $archivePath . $processedFolder . $ds . $tailsign . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
        } else if ($statusCode > 1) {
//             $status = "Rejected";
            // Move the tgz file to Rejected folder
            $destFile = $archivePath . $rejectedFolder . $ds . $airlineId . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
        }
        
        // Update offloads_master table
        updateOffloadMaster($targetFileName, $fileSize, $respArray);
        
        $dir = pathinfo(current(array_keys($files)), PATHINFO_DIRNAME);
        delFiles($dir);
        recursiveRemoveDirectory($targetPath);
    } else if($targetFileType == "xml") {
        $fileSize = filesize($targetFile);
        // check if the file size is 0
        if($fileSize == 0) {
            $destFile = $archivePath . $rejectedFolder . $ds  . $airlineId . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
            unlink($targetFile);
            
            $respArray = array();
            $respArray['STATUS_CODE'] = 2;
            $respArray['FAILURE_REASON'] = "Empty xml File";
            
            // Update offloads_master table
            updateOffloadMaster($targetFileName, $fileSize, $respArray);
            echo "<br/><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$targetFileName</b> is empty.<br>";
            exit();
        }
        
        $tailsign = "";
//         $status = "";
        $files = extractAndReadXMLFiles($targetPath);
        $respArray = processFiles($files, false);
        $statusCode = $respArray["STATUS_CODE"];
        $tailsign = $respArray["TAIL_SIGN"];
        $dataArray = $respArray["DATA"];
        foreach ($dataArray as $rowData) {
            printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"], $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"]);
        }
        if ($statusCode == 0) {
//             $status = "Processed";
            // Move the processed tgz file to Archive folder
            $destFile = $archivePath . $processedFolder . $ds . $tailsign . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
        } else if ($statusCode > 1) {
//             $status = "Rejected";
            // Move the tgz file to Rejected folder
            $destFile = $archivePath . $rejectedFolder . $ds . $airlineId . $ds . $targetFileName;
            copyFile($targetFile, $destFile);
        }
        
        // Update offloads_master table
        updateOffloadMaster($targetFileName, $fileSize, $respArray);
        
        $dir = pathinfo(current(array_keys($files)), PATHINFO_DIRNAME);
        recursiveRemoveDirectory($dir);
    }

//     echo "End: ";
//     printTime();
    
    echo "<br>";
    
	//BeR - END OF CODE COMMENTING
	*/	
	
	
} else {
    echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Cannot upload files on server. One reason could be the size of the files.<br>";
}
 // end if else


?>
