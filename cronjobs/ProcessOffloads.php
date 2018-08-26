<?php
error_log("Starting ProcessOffloads");
if(!isset($_SESSION)){
	error_log("session in Automatic ProcessOffloads was not started, starting it");
	session_start();
}

ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);
// date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/seatAnalyticsData.php";
require_once "../common/resetsReportFunctions.php";
require_once "../common/FlightFaultFunctions.php";
require_once "../common/AircraftDAO.php";
require_once "../engineering/SPNLPartNumberMatching.php";

if(!$_SESSION['disablePermissionCheck']) {
    require_once('checkEngineeringPermission.php');
}

require_once "../common/computeFleetStatusData.php";

include ("../engineering/biteXmlParser.php");
include ("../engineering/eventXmlParser.php");

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

//BeR 17Nov17: picking up variables from UploadDropzone needed for ProcessOffloads
$aircraftId = $_SESSION['aircraftID'];
error_log("aircraftID is $aircraftId");
$targetPath = $_SESSION['targetPath'];
error_log("targetPath is $targetPath");
$targetFileName = $_SESSION['targetFileName'];
error_log("targetFileName is $targetFileName");
$targetFile = $_SESSION['targetFile'];
error_log("targetFile is $targetFile");
$targetFileType = $_SESSION['targetFileType'];
error_log("targetFileType is $targetFileType");
$archivePath = $_SESSION['archivePath'];
error_log("archivePath is $archivePath");

// Get user uid. We are going to upload in a specific directory for each user.
// $uid = $auth->getSessionUID($hash);

//$airlineId = $_REQUEST['airlineId'];
$airlineId = $_SESSION['airlineId'];
error_log("airlineID for ProcessOffloads is $airlineId");

$acronym = $_SESSION['acronym'];
error_log("acronym for ProcessOffloads is $acronym");

$source = $_SESSION['source'];
error_log("source of ProcessOffloads is $source");

// Get the master tailsign data to check LRUs & SerialNumbers and find the tailsign
$masterTailSignArray = getMasterTailSignData($dbConnection, $airlineId);
    
// check if the zip file contains a folder and reject
// checkIfZipContainsFolder($targetFileType, $targetFile);
    
// Print Starting of the table
// printTableStart();

if ($targetFileType == "zip") {
    $zip = new ZipArchive();
    $res = $zip->open($targetFile);
    if ($res === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i ++) {
            $fName = $zip->getNameIndex($i);
            // $zip->extractTo($targetPath, array($fName));
            // $extension = pathinfo($fName, PATHINFO_EXTENSION);
            $fileinfo = pathinfo($fName);
            $extension = $fileinfo['extension'];
            $filename = $fileinfo['basename'];
            $fileSize = 0;
            
            if (strcasecmp($extension, "tgz") == 0 or strcasecmp($extension, "xml") == 0 or strcasecmp($extension, "tar") == 0) {
                try {
                    copyFile("zip://" . $targetFile . "#" . $fName, $targetPath . $filename);
                    $fileSize = filesize($targetPath . $filename);
                    
                    if ($fileSize == 0) {
                        error_log("File rejected due empty or 0kb: $destFolder");
//                         $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
                        $destFolder = $archivePath . $rejectedFolder . $ds . $acronym . $ds;
                        // echo "going to copy zip://$targetFile$fName to archive folder $destFolder$filename.<br/>";;
                        copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                        unlink($targetPath . $filename);
                        
                        $respArray = array();
                        $respArray['STATUS_CODE'] = 2;
                        $respArray['FAILURE_REASON'] = "Empty $extension File";
                        
                        // Update offloads_master table
                        updateOffloadMaster($source, $filename, $fileSize, $respArray);
                        echo "<br/><img src=\"../img/ko.png\" style=\"vertical-align:top\">&nbsp;&nbsp;<b>$filename</b> is empty.";
                        
                        continue;
                    }
                } catch (Exception $e) {
                    echo "Exception in copying the file: " . $e;
                }
            }
            
            if (strcasecmp($extension, "tgz") == 0) {
                error_log("Source of upload: $source");
                if($source == "Manual") {
                    $tailsign = checkIfFileNameContainsTailsign($dbConnection, $mainDB, $airlineId, $filename);
                    $_SESSION['tailsign'] = $tailsign;
                    
                    if(! empty($tailsign)) {
                        $_SESSION['tailsignSource'] = "File Name";
                    }
                }
                
                $tailsign = "";
                extractTGZFiles($targetPath);
                extractTARFile($targetPath);
                $files = extractAndReadXMLFiles($targetPath);
                if (empty($files)) {
                    $respArray = array();
                    $respArray["STATUS_CODE"] = 2;
                    $respArray["FAILURE_REASON"] = "Empty tgz file or no BITE or EVENT files inside the tgz";
                    $respArray["REMARKS"] = "Empty tgz file or no BITE or EVENT files inside the tgz";
                    $statusCode = 2;
                } else {
                    $respArray = processFiles($files);
                    $statusCode = $respArray["STATUS_CODE"];
                    $tailsign = $respArray["TAIL_SIGN"];
                    $dataArray = $respArray["DATA"];
                }
                if ($statusCode == 0) {
                    // $destFolder = $archivePath . $processedFolder . $ds . $tailsign . $ds;
                    $destFolder = $archivePath . $processedFolder . $ds . $acronym . $ds . $tailsign . $ds;
                    copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                } else if ($statusCode > 1) {
//                     $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
                    $destFolder = $archivePath . $rejectedFolder . $ds . $acronym . $ds;
                    copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                }
                
                // Print table data
//                 foreach ($dataArray as $rowData) {
//                     printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"],
//                         $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"],
//                         $rowData["FAILURE_REASON"], $rowData["REMARKS"]);
//                 }
                
                // Update offloads_master table
                updateOffloadMaster($source, $filename, $fileSize, $respArray);
            } else if (strcasecmp($extension, "tar") == 0) {
                $dir = "";
                // $status = "";
                
                if($source == "Manual") {
                    $tailsign = checkIfFileNameContainsTailsign($dbConnection, $mainDB, $airlineId, $fileName);
                    $_SESSION['tailsign'] = $tailsign;
                    
                    if(! empty($tailsign)) {
                        $_SESSION['tailsignSource'] = "File Name";
                    }
                }
                
                extractTARFile($targetPath);
                $files = extractAndReadXMLFiles($targetPath);
                
                if (empty($files)) {
                    $respArray = array();
                    $respArray["STATUS_CODE"] = 2;
                    $respArray["FAILURE_REASON"] = "Empty tar file or no BITE or EVENT files inside the tar";
                    $respArray["REMARKS"] = "Empty tar file or no BITE or EVENT files inside the tar";
                    $statusCode = 2;
                } else {
                    // process the files
                    $respArray = processFiles($files);
                    
                    $statusCode = $respArray["STATUS_CODE"];
                    $tailsign = $respArray["TAIL_SIGN"];
                    $dataArray = $respArray["DATA"];
                    
//                     foreach ($dataArray as $rowData) {
//                         printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"],
//                             $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"],
//                             $rowData["FAILURE_REASON"], $rowData["REMARKS"]);
//                     }
                }
                
                if ($statusCode == 0) {
                    // $destFolder = $archivePath . $processedFolder . $ds . $tailsign . $ds;
                    $destFolder = $archivePath . $processedFolder . $ds . $acronym . $ds . $tailsign . $ds;
                    copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                } else if ($statusCode > 1) {
//                     $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
                    $destFolder = $archivePath . $rejectedFolder . $ds . $acronym . $ds;
                    copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                }
                
                // Update offloads_master table
                updateOffloadMaster($source, $filename, $fileSize, $respArray);
            } else if (strcasecmp($extension, "xml") == 0) {
                $tailsign = "";
                
                $files = extractAndReadXMLFiles($targetPath);
                
                if (empty($files)) {
                    $respArray = array();
                    $respArray["STATUS_CODE"] = 2;
                    $respArray["FAILURE_REASON"] = "Not a BITE or EVENT file";
                    $respArray["REMARKS"] = "Upload only BITE or EVENT files";
                    $statusCode = 2;
                } else {
                    // process the files
                    $respArray = processFiles($files);
                    $statusCode = $respArray["STATUS_CODE"];
                    $tailsign = $respArray["TAIL_SIGN"];
                    $dataArray = $respArray["DATA"];
//                     foreach ($dataArray as $rowData) {
//                         printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"],
//                             $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"],
//                             $rowData["FAILURE_REASON"], $rowData["REMARKS"]);
//                     }
                }
                
                if ($statusCode == 0) {
                    // $destFolder = $archivePath . $processedFolder . $ds . $tailsign . $ds;
                    $destFolder = $archivePath . $processedFolder . $ds . $acronym . $ds . $tailsign . $ds;
                    copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                } else if ($statusCode > 1) {
//                     $destFolder = $archivePath . $rejectedFolder . $ds . $airlineId . $ds;
                    $destFolder = $archivePath . $rejectedFolder . $ds . $acronym . $ds;
                    copyFile("zip://" . $targetFile . "#" . $fName, $destFolder . $filename);
                }
                
                // Update offloads_master table
                updateOffloadMaster($source, $filename, $fileSize, $respArray);
            }
            
            // Delete the content of the tgz folder created by tgz extraction, except the main zip file.
            delDirContentWithASkip($targetPath, $targetFileName);
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
} else if ($targetFileType == "tgz") {
    $tailsign = "";
    $fileSize = filesize($targetFile);

    if($source == "Manual") {
        $tailsign = checkIfFileNameContainsTailsign($dbConnection, $mainDB, $airlineId, $targetFileName);
        $_SESSION['tailsign'] = $tailsign;
        
        if(! empty($tailsign)) {
            $_SESSION['tailsignSource'] = "File Name";
        }
    }
    
    // check if the file size is 0
    if ($fileSize == 0) {
        $respArray = array();
        $respArray['STATUS_CODE'] = 2;
        $respArray['FAILURE_REASON'] = "Empty tgz File";
        $statusCode = 2;
    } else {
        extractTGZFilesForProcessing($targetPath);
        extractTARFileForProcessing($targetPath);
        $files = extractAndReadXMLFiles($targetPath);
        
        if (empty($files)) {
            $respArray = array();
            $respArray["STATUS_CODE"] = 2;
            $respArray["FAILURE_REASON"] = "Empty tgz file or no BITE or EVENT files inside the tgz";
            $respArray["REMARKS"] = "Empty tgz file or no BITE or EVENT files inside the tgz";
            $statusCode = 2;
        } else {
            $respArray = processFiles($files);
            $statusCode = $respArray["STATUS_CODE"];
            $tailsign = $respArray["TAIL_SIGN"];
            $dataArray = $respArray["DATA"];
            
//             foreach ($dataArray as $rowData) {
//                 printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"],
//                     $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"], $rowData["FAILURE_REASON"],
//                     $rowData["REMARKS"]);
//             }
        }
    }
    
    if ($statusCode == 0) {
        // $destFile = $archivePath . $processedFolder . $ds . $tailsign . $ds . $targetFileName;
        $destFile = $archivePath . $processedFolder . $ds . $acronym . $ds . $tailsign . $ds . $targetFileName;
        copyFile($targetFile, $destFile);
    } else if ($statusCode > 1) {
//         $destFile = $archivePath . $rejectedFolder . $ds . $airlineId . $ds . $targetFileName;
        $destFile = $archivePath . $rejectedFolder . $ds . $acronym . $ds . $targetFileName;
        try {
            copyFile($targetFile, $destFile);
        } catch (Exception $e) {
            echo "Exception after finisng process satus code > 1";
        }
    }
    
    // Update offloads_master table
    updateOffloadMaster($source, $targetFileName, $fileSize, $respArray);
    
    recursiveRemoveDirectory($targetPath);
} else if ($targetFileType == "tar") {
    $fileSize = filesize($targetFile);

    if($source == "Manual") {
        $tailsign = checkIfFileNameContainsTailsign($dbConnection, $mainDB, $airlineId, $targetFileName);
        $_SESSION['tailsign'] = $tailsign;
        
        if(! empty($tailsign)) {
            $_SESSION['tailsignSource'] = "File Name";
        }
    }
    
    // check if the file size is 0
    if ($fileSize == 0) {
        $respArray = array();
        $respArray['STATUS_CODE'] = 2;
        $respArray['FAILURE_REASON'] = "Empty tar File";
        $statusCode = 2;
    } else {
        extractTARFileForProcessing($targetPath);
        $files = extractAndReadXMLFiles($targetPath);
        
        if (empty($files)) {
            $respArray = array();
            $respArray["STATUS_CODE"] = 2;
            $respArray["FAILURE_REASON"] = "Empty tar file or no BITE or EVENT files inside the tar";
            $respArray["REMARKS"] = "Empty tar file or no BITE or EVENT files inside the tar";
            $statusCode = 2;
        } else {
            // process the files
            $respArray = processFiles($files);
            $statusCode = $respArray["STATUS_CODE"];
            $tailsign = $respArray["TAIL_SIGN"];
            $dataArray = $respArray["DATA"];
            
//             foreach ($dataArray as $rowData) {
//                 printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"],
//                     $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"], $rowData["FAILURE_REASON"],
//                     $rowData["REMARKS"]);
//             }
        }
    }
    
    if ($statusCode == 0) {
        // $destFile = $archivePath . $processedFolder . $ds . $tailsign . $ds . $targetFileName;
        $destFile = $archivePath . $processedFolder . $ds . $acronym . $ds . $tailsign . $ds . $targetFileName;
        copyFile($targetFile, $destFile);
    } else if ($statusCode > 1) {
//         $destFile = $archivePath . $rejectedFolder . $ds . $airlineId . $ds . $targetFileName;
        $destFile = $archivePath . $rejectedFolder . $ds . $acronym . $ds . $targetFileName;
        copyFile($targetFile, $destFile);
    }
    
    // Update offloads_master table
    updateOffloadMaster($source, $targetFileName, $fileSize, $respArray);
    
    recursiveRemoveDirectory($targetPath);
} else if ($targetFileType == "xml") {
    $tailsign = "";
    $fileSize = filesize($targetFile);
    // check if the file size is 0
    if ($fileSize == 0) {
        $respArray = array();
        $respArray['STATUS_CODE'] = 2;
        $respArray['FAILURE_REASON'] = "Empty xml File";
        $statusCode = 2;
    } else {
        $files = extractAndReadXMLFiles($targetPath);
        
        if (empty($files)) {
            $respArray = array();
            $respArray["STATUS_CODE"] = 2;
            $respArray["FAILURE_REASON"] = "Not a BITE or EVENT file";
            $respArray["REMARKS"] = "Upload only BITE or EVENT files";
            $statusCode = 2;
        } else {
            // process the files
            $respArray = processFiles($files, false);
            $statusCode = $respArray["STATUS_CODE"];
            $tailsign = $respArray["TAIL_SIGN"];
            $dataArray = $respArray["DATA"];
            
//             foreach ($dataArray as $rowData) {
//                 printTableRow($rowData["FILE_NAME"], $rowData["FILE_SIZE"], $rowData["FLIGHT_NO"], $rowData["TS_FROM_FILE"], $rowData["TS_FOUND"],
//                     $rowData["DEP_AIRPORT"], $rowData["ARR_AIRPORT"], $rowData["DEP_TIME"], $rowData["ARR_TIME"], $rowData["STATUS"], $rowData["FAILURE_REASON"],
//                     $rowData["REMARKS"]);
//             }
        }
    }
    
    if ($statusCode == 0) {
        // $destFile = $archivePath . $processedFolder . $ds . $tailsign . $ds . $targetFileName;
        $destFile = $archivePath . $processedFolder . $ds . $acronym . $ds . $tailsign . $ds . $targetFileName;
        copyFile($targetFile, $destFile);
    } else if ($statusCode > 1) {
//         $destFile = $archivePath . $rejectedFolder . $ds . $airlineId . $ds . $targetFileName;
        $destFile = $archivePath . $rejectedFolder . $ds . $acronym . $ds . $targetFileName;
        copyFile($targetFile, $destFile);
    }
    
    // Update offloads_master table
    updateOffloadMaster($source, $targetFileName, $fileSize, $respArray);
    
    recursiveRemoveDirectory($targetPath);
} else {
    recursiveRemoveDirectory($targetPath);
}

function recursiveRemoveDirectory($directory) {
    if(! empty($directory) && strlen($directory) > 1 && strpos($directory, 'upload_offloads') !== false) {
        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                recursiveRemoveDirectory($file);
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }
}

function recursiveMoveDirectory($src, $dst) {
    $dir = opendir($src);
    if (! file_exists($dst)) {
        mkdir($dst, 0755);
    }
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                if (! file_exists($dst . '/' . $file)) {
                    mkdir($dst . '/' . $file, 0755);
                }
                recursiveMoveDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    return true;
}

function IsNullOrEmptyString($str) {
    return (! isset($str) || trim($str) === '');
}

function printTableStart() {
    echo "<div style=\"overflow: auto;\">";
    echo "<table class=\"table table-bordered\" style=\"width: 1100px;\" >";
    echo "<thead>";
    echo "<tr>";
    echo "<th style=\"width:20%\">File Name</th>";
    echo "<th style=\"width:5%\">Size</th>";
    echo "<th style=\"width:10%\">Flight Number</th>";
    echo "<th style=\"width:10%\">Tail Sign<br/>from file</th>";
    echo "<th style=\"width:10%\">Tail Sign<br/>identified</th>";
    echo "<th style=\"width:10%\">City Pair</th>";
    echo "<th style=\"width:10%\">Departure Time</th>";
    echo "<th style=\"width:10%\">Arrival Time</th>";
    echo "<th style=\"width:15%\">Status</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
}

function printTableRow($fName, $size, $flightNo, $tailfromfile, $tailfromdb, $depAirport, $arrAirport, $depTime, $arrTime, $stat, $failReason, $remarks) {
    echo "<tr>";
    echo "<td>$fName</td>";
    echo "<td>$size KB</td>";
    echo "<td>$flightNo</td>";
    echo "<td>$tailfromfile</td>";
    echo "<td>$tailfromdb</td>";
    if(! IsNullOrEmptyString($depAirport) or ! IsNullOrEmptyString($arrAirport)) {
        echo "<td>$depAirport > $arrAirport</td>";
    } else {
        echo "<td></td>";
    }
    echo "<td>$depTime</td>";
    echo "<td>$arrTime</td>";
    if(! IsNullOrEmptyString($failReason)) {
        $stat .= ". $failReason.";
    }
        
    if(! IsNullOrEmptyString($remarks)) {
        $stat .= " $remarks.";
    }
    echo "<td>$stat</td>";
    echo "</tr>";
}

function printTableEnd() {
    echo "</tbody></table>";
}

function echoline($msg) {
    echo "<br/>$msg<br/>";
}

/**
 * Copy the file from source to destination
 *
 * @param File $sourceFile
 * @param File $destFile
 */
function copyFile($sourceFile, $destFile) {
    $path = pathinfo($destFile);
    error_log("SourceFile: $sourceFile");
    error_log("DestFile: $destFile");
    if (! file_exists($path['dirname'])) {
        mkdir($path['dirname'], 0755, true);
    }
    if (! copy($sourceFile, $destFile)) {
        echo "copy failed \n";
    }
}

/**
 * Retrieves the data from master tailsign table for the set of LRUs for validation
 *
 * @param unknown $dbConnection
 * @return array[]|string[][]
 */
function getMasterTailSignData($dbConnection, $airlineId) {
    $tailsignFromDB = "";
    $prevTailsignFromDB = "";
    $hostNameFromDB = "";
    $serialNoFromDB = "";
    $mainTSArray = array();
    $tempArray = array();
    $lruStr = "'DSU1','DSU2','ICMT1','SVDU1A','SVDU1C','SVDU2A','SVDU2C','SVDU3A','SVDU3C','SVDU10A','SVDU10C','SVDU11A','SVDU11C'";
    
    $query = "select tailsign, host_name, serial_number from $mainDB.serialnumber_info where airline_id=$airlineId and host_name in($lruStr) order by tailsign, FIELD(host_name, $lruStr);";

    $result = mysqli_query($dbConnection, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tailsignFromDB = (string) $row['tailsign'];
            $hostNameFromDB = (string) $row['host_name'];
            $serialNoFromDB = (string) $row['serial_number'];
            
            if ($prevTailsignFromDB != $tailsignFromDB) {
                if (! empty($tempArray)) {
                    $mainTSArray[$prevTailsignFromDB] = $tempArray;
                    unset($tempArray);
                }
                
                $tempArray = array();
                $tempArray[$hostNameFromDB] = $serialNoFromDB;
            }
            
            $tempArray[$hostNameFromDB] = $serialNoFromDB;
            $prevTailsignFromDB = $tailsignFromDB;
        } // end while
          
        // To add the final set of HostName & Serial Number array.
        $mainTSArray[$prevTailsignFromDB] = $tempArray;
    } else {
//         echoline("No result");
        echoline(mysqli_error($dbConnection));
    } // end if
    
    return $mainTSArray;
}
 // end getMasterTailSignData

/**
 * Compares LRU & Serial No from file and master table and find the TailSign
 *
 * @param unknown $xml
 * @param unknown $masterTailSignArray
 * @return string|unknown
 */
function getTailSignFromMasterTable($xml, $masterTailSignArray) {
    $lruSerialNoArrayFromFile = array();
    $previousHostName = "";
    $count = 1;
    $unitCount = 1;
    $tailsign = "";
    foreach ($xml->Equipment as $equipment) {
        $hostName = trim((string) $equipment['Hostname']);
        $tempSerialNumber = (string) $equipment->EquipmentDetails->StaticInfo['serialNumber'];
        if ($hostName == "DSU1" || $hostName == "DSU2" || $hostName == "ICMT1" || $hostName == "SVDU1A" || $hostName == "SVDU1C" || $hostName == "SVDU2A" ||
             $hostName == "SVDU2C" || $hostName == "SVDU3A" || $hostName == "SVDU3C" || $hostName == "SVDU10A" || $hostName == "SVDU10C" ||
             $hostName == "SVDU11A" || $hostName == "SVDU11C") {
            $lruSerialNoArrayFromFile[$hostName] = $tempSerialNumber;
        }
    }
    
    // Check if the hostName and serial number matches with values from DB
    $matchFound = false;
    
    foreach ($masterTailSignArray as $tailsignFromDB => $lruSerialNoArrayFromDB) {
        $matchCount = 0;
        
        foreach ($lruSerialNoArrayFromDB as $lruFromDB => $slNoFromDB) {
            if (array_key_exists($lruFromDB, $lruSerialNoArrayFromFile)) {
                $serialNoFromFile = $lruSerialNoArrayFromFile[$lruFromDB];
                
                if ($serialNoFromFile == $slNoFromDB)
                    $matchCount ++;
                
                if ($matchCount > 4) {
                    $matchFound = true;
                    break;
                }
            }
        } // end for each
        
        if ($matchFound) {
            $tailsign = $tailsignFromDB;
            break;
        }
    } // end for each
    
    return $tailsign;
}
 // end getTailSignFromFileAndDB

/**
 * Compares the LRU & Serial Number from file with data in all the data bases belong to that airline
 *
 * @param unknown $dbConnection
 * @param unknown $mainDB
 * @param unknown $airlineId
 * @return string|unknown
 */
function getTailSignByCheckingAllDBs($dbConnection, $mainDB, $airlineId, $xml) {
    $tailsign = "";
    $lruSerialNoArrayFromFile = array();
    
    // Get all the tailsign and database name for the airline
    $tailSignDBArray = array();
    $query = "SELECT tailsign, databaseName FROM $mainDB.aircrafts WHERE airlineId='$airlineId'";
    $result = mysqli_query($dbConnection, $query);
    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $tsFromDB = $row['tailsign'];
            // $dbName = strtolower($row['databaseName']);
            $dbName = $row['databaseName'];
            $tailSignDBArray[$tsFromDB] = $dbName;
        }
    }
    
    foreach ($xml->Equipment as $equipment) {
        $hostName = trim((string) $equipment['Hostname']);
        $tempSerialNumber = (string) $equipment->EquipmentDetails->StaticInfo['serialNumber'];
        if ($hostName == "DSU1" || $hostName == "DSU2" || $hostName == "ICMT1" || $hostName == "SVDU1A" || $hostName == "SVDU1C" || $hostName == "SVDU2A" ||
             $hostName == "SVDU2C" || $hostName == "SVDU3A" || $hostName == "SVDU3C" || $hostName == "SVDU10A" || $hostName == "SVDU10C" ||
             $hostName == "SVDU11A" || $hostName == "SVDU11C") {
            $lruSerialNoArrayFromFile[$hostName] = $tempSerialNumber;
        }
    }
    
    $lruStr = "";
    // construct the hostname string to use in query
    foreach ($lruSerialNoArrayFromFile as $lru => $serialNo) {
        $lruStr = $lruStr . "'$lru'" . ",";
    }
    $lruStr = substr($lruStr, 0, - 1);
    
    // Check if the hostName and tail sign matches with values from DB
    $matchFound = false;
    foreach ($tailSignDBArray as $ts => $db) {
        $matchCount = 0;
        
        $query = "select hostname, serialNumber from $db.bit_lru where hostname in($lruStr) ";
        $result = mysqli_query($dbConnection, $query);
        if ($result) {
            while ($row = mysqli_fetch_array($result)) {
                $lruFromDB = $row['hostname'];
                $serialNoFromDB = $row['serialNumber'];
                
                if ($matchCount > 4) {
                    $matchFound = true;
                    break;
                }
                
                // Compare the values with values in lruSerialNoArrayFromFile
                foreach ($lruSerialNoArrayFromFile as $lruFromFile => $serialNoFromFile) {
                    if ($lruFromFile == $lruFromDB && $serialNoFromFile == $serialNoFromDB) {
                        $matchCount ++;
                        break;
                    }
                }
            }
            if ($matchFound) {
                // echo "Tail Sign from DB: " . $ts;
                $tailsign = $ts;
                break;
            }
        }
    } // end for each DB
    
    return $tailsign;
} // end getTailSignByCheckingAllDBs

function extractTGZFiles($targetPath) {
    // read tgz files if any in directory
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
    foreach ($dir as $fileinfo) {
        $pathInfo = pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION);
        if ($fileinfo->isFile() && (strcasecmp($pathInfo, "tgz") == 0)) {
            try {
                $sourceFile = $fileinfo->getPathname();
                $destDir = $fileinfo->getPath();
                $command = "tar xzvf $sourceFile -C $destDir 2>&1";
                exec($command, $result);
//                 error_log("output of exec command: ". print_r($result));
                
                unlink($fileinfo->getPathname());
                
            } catch (Exception $ex) {
                echo "Exception on file".$ex->getMessage();
                error_log("Error in extractTGZFiles: ".$ex->getMessage());
            }
        }
    }
}

function extractTGZFilesForProcessing($targetPath) {
    // read tgz files if any in directory
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
    foreach ($dir as $fileinfo) {
        $pathInfo = pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION);
        if ($fileinfo->isFile() && (strcasecmp($pathInfo, "tgz") == 0)) {
            try {
                $filename = $fileinfo->getFilename();
                $p = new PharData($fileinfo->getPathname());
                $p->decompress(); // creates /path/to/my.tar
            } catch (Exception $ex) {
                error_log("Exception while extracting TGZ file: ".$ex);
            }
        }
    }
}

function extractTARFile($targetPath) {
    // read tar files if any in directory - do it twice as old bite contain data in a tar of tar
    $i = 0;
    while ($i < 2) {
        $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile() && pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION) == "tar") {
                try {
                    $filename = $fileinfo->getFilename();
                    $tarPath = $fileinfo->getPathname();
                    $phar = new PharData($tarPath);
                    $phar->extractTo($targetPath, null, true);
                    unset($phar);
                    Phar::unlinkArchive($tarPath);
                    $readXmlFiles = true;
                } catch (Exception $ex) {
                    echo "Exception on file".$ex->getMessage();
                    error_log("Error in extractTARFile: ".$ex->getMessage());
                }
            }
        }
        $i ++;
    }
}

function extractTARFileForProcessing($targetPath) {
    $i = 0;
    while ($i < 2) {
        $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile() && pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION) == "tar") {
                try {
                    $filename = $fileinfo->getFilename();
                    $tarPath = $fileinfo->getPathname();
                    $phar = new PharData($tarPath);
                    $phar->extractTo($targetPath, null, true);
                    unset($phar);
//                     Phar::unlinkArchive($tarPath);
//                     unlink($tarPath);
//                     $readXmlFiles = true;
                } catch (Exception $ex) {
                    error_log("Exception while extracting TAR file: ".$ex);
//                     echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Error while untarring $filename / Message: " . $ex->getMessage() . ".<br>";
                }
            }
        }
        $i ++;
    }
}

function extractAndReadXMLFiles($targetPath) {
    // read all xml files in directory and sort them by time;
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
    $files = array();
    
    // read all xml files and sort them by creation date as indicated in the name of the file xxxxx_time_date.xml
    foreach ($dir as $fileinfo) {
        if ($fileinfo->isFile() && pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION) == "xml") {
            $filename = $fileinfo->getFilename();
            if (strpos($filename, 'BITE') === 0 || strpos($filename, 'EVENT') === 0 
                || isValidBITEFileContent($fileinfo->getPathname()) || isValidEVENTFileContent($fileinfo->getPathname())) {
                $filepathname = $fileinfo->getPathname();
                $string = str_replace(".xml", "", $filename);
                $timedate = substr($string, - 15);
                $timedate = explode('_', $timedate);
                $date = $timedate[1];
                $time = $timedate[0];
                $datetime = "$date" . "$time";
                $files[$filepathname] = $datetime;
            }
        }
    }
    asort($files);
    return $files;
}

function delDir($dir) {
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

function delFiles($dir) {
//     echoline("inside del files");
    
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if (! $file->isDir()){
            unlink($file->getRealPath());
        }
    }
}

/**
 * Process the extracted files
 * 
 * @param unknown $files
 */
function processFiles($files, $unlinkProcessedFile=true) {
    $mainArray = array();
    $dataArray = array();
    global $airlineId;
    global $mainDB;
    global $dbConnection;
    $tailsign = "";
    $tailsignIdentified = "";
    $tailsignSource = "";
    $tailsignFromFile = "";
    $tailSignExists = false;
    
    if(!empty($_SESSION['tailsign'])) {
        $tailSignExists = true;
        $tailsignIdentified = $_SESSION['tailsign'];
        $tailsignSource = $_SESSION['tailsignSource'];
        $tailsignFromFile = $_SESSION['tailsignFromFile'];
    }
    
    foreach ($files as $filepathname => $datetime) {
        $failureReason = "";
        $remarks = "";
        
        $filename = basename($filepathname);
        // some files have blank spaces in their name so remove them (looks it is for EVENT files)
        $filename = str_replace(' ', '', $filename);
        $filebasename = strtok($filename, '.');
        // echo "found xml: $filename\n";
        
        // Manupulate Date & Time Formate from dateTime String.
        $year =   substr($datetime,0,4);
        $Month =  substr($datetime,4,2);
        $day  =   substr($datetime, 6,2);
        $hourse = substr($datetime, 8, 2);
        $minute = substr($datetime, 10,2);
        $sec    = substr($datetime, 12,2);
        
        $newDate= $year."-".$Month."-".$day ;
        $newTime= $hourse.":".$minute.":".$sec;
        $newdatetimeformate = $newDate." ".$newTime;
        
        $depAirport = "";
        $arrAirport = "";
        $depTime = "";
        $arrTime = "";
        $flightNumber = "";
        $status = "Processed";
        $flightLegId = "";
        
        // We don't know for which aircraft we are loading
        // Read XML a first time to get tailsign
        $fileContent = file_get_contents($filepathname);
        if(trim($fileContent) == '') {
            $status = "Rejected";
            $failureReason = "Empty XML file";
            $remarks = "Please verify and upload the file with BITE data";
            $trData = array();
            $trData["FILE_NAME"] = $filename;
            $trData["FILE_SIZE"] = "0";
            $trData["STATUS"] = $status;
            $trData["FAILURE_REASON"] = $failureReason;
            $trData["REMARKS"] = $remarks;
            $trData["TS_FROM_FILE"] = $trData["TS_FOUND"] = $trData["DEP_AIRPORT"] = $trData["ARR_AIRPORT"] = $trData["DEP_TIME"] = $trData["ARR_TIME"] = $trData["FLIGHT_NO"] = $trData["OFFLOAD_DATE"] = "";
            array_push($dataArray, $trData);
            
            // Delete file from upload folder
            if($unlinkProcessedFile) {
                unlink($filepathname);
            }
            
            continue;
        }
        
        $xml = @simplexml_load_file($filepathname);
        if ($xml === false) {
            error_log("Error while loading the xml file: $filepathname");
            $status = "Rejected";
            $failureReason = "Failed loading XML";
            $remarks = "Unexpected error while loading the XML file";
            $trData = array();
            $trData["FILE_NAME"] = $filename;
            $trData["FILE_SIZE"] = "0";
            $trData["STATUS"] = $status;
            $trData["FAILURE_REASON"] = $failureReason;
            $trData["REMARKS"] = $remarks;
            $trData["TS_FROM_FILE"] = $trData["TS_FOUND"] = $trData["DEP_AIRPORT"] = $trData["ARR_AIRPORT"] = $trData["DEP_TIME"] = $trData["ARR_TIME"] = $trData["FLIGHT_NO"] = $trData["OFFLOAD_DATE"] = "";
            array_push($dataArray, $trData);
            
            // Delete file from upload folder
            if($unlinkProcessedFile) {
                unlink($filepathname);
            }
            
            continue;
        }
        
        $fSize = strval(round(filesize($filepathname) / 1024, 2));
        $depAirport = trim($xml->FlightLegInfo[0]['DepartureAirport']);
        $arrAirport = trim($xml->FlightLegInfo[0]['ArrivalAirport']);
        $depTime = trim($xml->FlightLegInfo[0]['FlightLegStartTime']);
        $arrTime = trim($xml->FlightLegInfo[0]['FlightLegStopTime']);
        $flightNumber = trim($xml->FlightLegInfo[0]['FlightNumber']);
        
        if(! $tailSignExists) {
            if (/* isValidBITEFile($filename) ||  */isValidBITEFileContent($filepathname)) {
                $tailsign = trim($xml->FlightLegInfo[0]['AircraftTailSign']);
                if(! empty($tailsign)) {
                $tailsignFromFile = $tailsign;
                }
            } else if (/* isValidEVENTFile($filename) || */ isValidEVENTFileContent($filepathname)) {
                $tailsign = trim($xml[0]['aircraftTailSign']);
                if(! empty($tailsign)) {
                $tailsignFromFile = $tailsign;
                }
                $depAirport = trim($xml[0]['departureAirport']);
                $arrAirport = trim($xml[0]['arrivalAirport']);
            } else {
                $status = "Rejected";
                $failureReason = "Not a BITE or EVENT file";
                $remarks = "Upload only BITE or EVENT files";
                
                $trData = array();
                $trData["FILE_NAME"] = $filename;
                $trData["FILE_SIZE"] = "0";
                $trData["STATUS"] = $status;
                $trData["FAILURE_REASON"] = $failureReason;
                $trData["REMARKS"] = $remarks;
                $trData["TS_FROM_FILE"] = $trData["TS_FOUND"] = $trData["DEP_AIRPORT"] = $trData["ARR_AIRPORT"] = $trData["DEP_TIME"] = $trData["ARR_TIME"] = $trData["FLIGHT_NO"] = $trData["OFFLOAD_DATE"] = "";
                array_push($dataArray, $trData);
                
                // Delete file from upload folder
                if($unlinkProcessedFile) {
                    unlink($filepathname);
                }
                
                // go to next file;
                continue;
            }
        }
            
        if(! $tailSignExists) {
            // Remove the special character (.) and query to identify the tailsign.
            $tailsign = ltrim($tailsign, "."); // It looks like some i5000 files put a '.' before the tailsign
            // check if able to get the DB and other details
            $query = "SELECT tailsign FROM $mainDB.aircrafts WHERE airlineId='$airlineId' and tailsign='$tailsign'";
            $result = mysqli_query($dbConnection, $query);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $tailsign = $row['tailsign'];
                $tailsignFromFile = $tailsign;
                $tailsignIdentified = $tailsign;
                $tailsignSource = "Tailsign from file content";
                $tailSignExists = true;
            }
        }
        
        // Compare LRUs & serialNumber from file and master table and find the matching tail sign
        global $masterTailSignArray;
        if (! $tailSignExists) {
            $tailsign = getTailSignFromMasterTable($xml, $masterTailSignArray);
            
            if (! empty($tailsign)) {
                $tailsignIdentified = $tailsign;
                $tailsignSource = "Serial Number search";
                $tailSignExists = true;
            }
        } // end if ! $tailsignExists
        
        // Get the details for the identified tail sign.
        if ($tailSignExists) {
            $mainArray['TAIL_SIGN'] = $tailsignIdentified;
            // MBS : Altered above query as below. Consider changing LEFT JOIN to INNER JOIN after data is populated.
            $query = "SELECT a.id, a.type, a.databaseName, a.flightLegIdCount, a.eventFlightLegIdCount, a.platform, a.airlineId, b.firstClassSeats, b.businessClassSeats, b.totalEconomyClassSeats FROM $mainDB.aircrafts a LEFT JOIN $mainDB.aircraft_seatinfo b ON (a.aircraftConfigId=b.id) WHERE a.tailsign='$tailsignIdentified'";
            $result = mysqli_query($dbConnection, $query);
            if (mysqli_num_rows($result) > 0) {
                // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/plane.png\" style=\"vertical-align:top\">&nbsp;&nbsp;Data are for tailsign <i>$tailsign</i>.<br>";
                $row = mysqli_fetch_array($result);
                $aircraftId = $row['id'];
                $type = $row['type'];
                // $dataBaseName = strtolower($row['databaseName']);
                $dataBaseName = $row['databaseName'];
                $flightLegIdCount = $row['flightLegIdCount'];
                // MBS : Added below line to handle eventFlightLegIdCount
                $eventFlightLegIdCount = $row['eventFlightLegIdCount'];
                $platform = $row['platform'];
                
                // MBS : Added below 4 lines
                $airlineId = $row['airlineId'];
                $firstClassSeats = $row['firstClassSeats'];
                $businessClassSeats = $row['businessClassSeats'];
                $totalEconomyClassSeats = $row['totalEconomyClassSeats'];
            }
        } else {
            $status = "Rejected";
//             $failureReason = "Tail Sign not recognized";
            $failureReason = "Tail Sign not recognized. No reference to Serial Number or First time data upload";
            $remarks = "Please upload the file to respective tail if Tailsign in known";
            $statusCode = $GLOBALS['TAILSIGN_NOT_FOUND'];
            $tailsign = "";
            
//             echoline("Offload Date: $newdatetimeformate");
            
            $mainArray['TAIL_SIGN'] = $tailsign;
            $trData = array();
            $trData["FILE_NAME"] = $filename;
            $trData["FILE_SIZE"] = $fSize;
            $trData["FLIGHT_NO"] = $flightNumber;
            $trData["TS_FROM_FILE"] = $tailsignFromFile;
            $trData["TS_FOUND"] = "";
            $trData["DEP_AIRPORT"] = $depAirport;
            $trData["ARR_AIRPORT"] = $arrAirport;
            $trData["DEP_TIME"] = $depTime;
            $trData["ARR_TIME"] = $arrTime;
            $trData["STATUS"] = $status;
            $trData["FAILURE_REASON"] = $failureReason;
            $trData["REMARKS"] = $remarks;
            $trData["OFFLOAD_DATE"] = $newdatetimeformate;
            array_push($dataArray, $trData);
            
//             print_r($trData);
            // Delete file from upload folder
            if($unlinkProcessedFile) {
                unlink($filepathname);
            }
            
            continue;
        } // end if else
        
        // Select aircraft database as we update the aircraft table later and we had to select Banalytics DB
        $selectedDb = mysqli_select_db($dbConnection, $dataBaseName);
        if (!$selectedDb) {
            $status = "Rejected";
//             $failureReason = "New Tail Sign";
            $failureReason = "Tail Sign not recognized. No reference to Serial Number or First time data upload";
            $remarks = "Please upload the file to respective tail if Tailsign is known";
            $statusCode = $GLOBALS['TAILSIGN_NOT_FOUND'];
            $tailsign = "";
            
            $mainArray['TAIL_SIGN'] = $tailsign;
            $trData = array();
            $trData["FILE_NAME"] = $filename;
            $trData["FILE_SIZE"] = $fSize;
            $trData["FLIGHT_NO"] = $flightNumber;
            $trData["TS_FROM_FILE"] = $tailsignFromFile;
            $trData["TS_FOUND"] = $tailsignIdentified;
            $trData["DEP_AIRPORT"] = $depAirport;
            $trData["ARR_AIRPORT"] = $arrAirport;
            $trData["DEP_TIME"] = $depTime;
            $trData["ARR_TIME"] = $arrTime;
            $trData["STATUS"] = $status;
            $trData["FAILURE_REASON"] = $failureReason;
            $trData["REMARKS"] = $remarks;
            $trData["OFFLOAD_DATE"] = $newdatetimeformate;
            array_push($dataArray, $trData);
            
            // Delete file from upload folder
            if($unlinkProcessedFile) {
                unlink($filepathname);
            }
            
            continue;
        }
        
        // Check if file has already been uploaded
        $query = "SELECT id FROM offloads WHERE name = '$filebasename'";
        $result = mysqli_query($dbConnection,$query);
        
        if($result) {
            if(mysqli_num_rows($result) == 0) {
                $query = "SELECT MAX(offloadDate) AS maxDate FROM offloads WHERE name LIKE 'BITE%'";
                $result = mysqli_query($dbConnection,$query);
                if($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $maxOffloadDate = $row['maxDate'];
                    if(!isset($maxOffloadDate)) {
                        // it is the first time we are popluating the database - Need to provide a default date.
                        // mysql returns NULL when
                        $maxOffloadDate = "0000-00-00 00:00:00";
                    }
                } else {
                    // set to default date
                    $maxOffloadDate = "0000-00-00 00:00:00";
                }
                
                
                if(strpos($filename, "BITE") === 0) {
                    $flightLegId = $flightLegIdCount;
                    
                    $biteAvantXmlParser=new BiteAvantXmlParser();
                    $biteAvantXmlParser->init($dbConnection, $tailsignIdentified, $type, $flightLegIdCount, $newdatetimeformate, $maxOffloadDate);
                    $istatus=$biteAvantXmlParser->parse($filepathname);
                    
                    if($istatus == 1) {
                        $strQuery="INSERT INTO offloads (name,offloadDate, idFlightLeg) VALUES ('$filebasename', '$newdatetimeformate', $flightLegIdCount)";
                        $offloadresult = mysqli_query($dbConnection,$strQuery);
                        
                        // add resets data
                        $cruiseTime = getCruiseTime($dbConnection,$flightLegIdCount,$dataBaseName);
                        $dateFlightLeg = "";
                        
                        if ($cruiseTime > 0) {
                            $totalCommandedResets = computeResetsForFlightLeg($dbConnection, $dataBaseName, $flightLegIdCount, 'SVDU', 'CommandedReboot');
                            $totalUncommandedResets = computeResetsForFlightLeg($dbConnection, $dataBaseName, $flightLegIdCount, 'SVDU', 'UncommandedReboot');
                            $nbOfSeats = getNumberOfSeats($dbConnection, $dataBaseName);
                            $systemResetsCount = getSystemResetsCount($dbConnection, $dataBaseName, $flightLegIdCount, $nbOfSeats);
                            
                            $queryFL = "SELECT createDate FROM SYS_flight WHERE idFlightLeg = $flightLegIdCount";
                            $resultFL = mysqli_query($dbConnection,$queryFL);
                            $rowFL = mysqli_fetch_array($resultFL);
                            $dateFlightLeg = $rowFL['createDate'];
                            
                            $queryInsert = "INSERT INTO $mainDB.resets_report (acid,seatsCount,flightDate,flightLegId,totalCruise, totalCommandedResets, totalUncommandedResets, systemResetsCount, lruType)
									SELECT * FROM ( SELECT $aircraftId AS f1, $nbOfSeats AS f2, \"$dateFlightLeg\" AS f3,$flightLegIdCount AS f4,$cruiseTime AS f5, $totalCommandedResets AS f6, $totalUncommandedResets AS f7, $systemResetsCount AS f8, 'SVDU' AS f9) AS tmp
									WHERE NOT EXISTS (
										SELECT id FROM $mainDB.resets_report WHERE acid = '$aircraftId' AND flightLegId = '$flightLegIdCount'
									) ";
                            $resultInsert = mysqli_query($dbConnection,$queryInsert);
                            if(!$resultInsert) {
                                $status = "Error inserting resets_report : " . mysqli_error($dbConnection);
                            }
                        }
                        
                        // compute Flight Fault count
                        if ($cruiseTime > 0) {
                            $flightFaultFuntions = new FlightFaultFunctions();
                            $flightFaultFuntions->init($dbConnection, $mainDB, $dataBaseName, $flightLegIdCount, $aircraftId, $dateFlightLeg, $cruiseTime);
                            $flightFaultFuntions->processFlightFaultCountForFlightLeg();
                        }
                        
                        // Update software version using SPNL Part Number matching
//                         updateSWVersion($airlineId, $aircraftId, $flightLegIdCount);
                        
                        // compute flight leg status
                        //MBS : Altered below call to have 3 more params.
                        computeFlightLegStatus($dataBaseName, $platform, $flightLegIdCount, $firstClassSeats, $businessClassSeats, $totalEconomyClassSeats);
                        
                        // update flight legIdCount in Banalytics database
                        $flightLegIdCount++;
                        // Performance note: I think I need to do the update all the time to make sure I have the latest data even if there is a problem
                        // But I don't need to read the data all the time because of $flightLegIdCount variable
                        $selected = mysqli_select_db($dbConnection,$mainDB)
                        or die("Could not select ".$mainDB);
                        $query = "UPDATE aircrafts SET flightLegIdCount = $flightLegIdCount ";
                        if(!isset($aircraftId)) {
                            $query .= "WHERE tailsign='$tailsignIdentified'";
                        }  else {
                            $query .= "WHERE id = $aircraftId";
                        }
                        
                        $result = mysqli_query($dbConnection,$query);
                        if(!$result){
                            $status = "Error updating flightLegIdCount in aircrafts : " . mysqli_error($dbConnection);
                        }
                        
                        mysqli_commit($dbConnection);
                        
                    } else {
                        mysqli_rollback($dbConnection);
                        $errorMsg = $biteAvantXmlParser->getErrMsg();
                        $status = "Error while processing : $errorMsg";
                    }
                } else if(strpos($filename, "EVENT") === 0) {
                    //MBS: Added "eventFlightLegIdCount" column to aircrafts table in Banalytics. Handle this.
                    $eventXmlParser=new EventXmlParser();
                    $eventXmlParser->init($dbConnection, $tailsignIdentified, $eventFlightLegIdCount);
                    $istatus=$eventXmlParser->parse($filepathname);
                    
                    if($istatus == 1) {
                        $strQuery="INSERT INTO offloads (name,offloadDate, idEventFlightLeg) VALUES ('$filebasename', '$newdatetimeformate', " . $eventFlightLegIdCount . ")";
                        $offloadresult = mysqli_query($dbConnection,$strQuery);
                        
                        // update eventFlightLegIdCount in Banalytics database
                        $eventFlightLegIdCount++;
                        // Performance note: I think I need to do the update all the time to make sure I have the latest data even if there is a problem
                        // But I don't need to read the data all the time because of $flightLegIdCount variable
                        $selected = mysqli_select_db($dbConnection,$mainDB)
                        or die("Could not select ".$mainDB);
                        $query = "UPDATE aircrafts SET eventFlightLegIdCount = $eventFlightLegIdCount ";
                        if(!isset($aircraftId)) {
                            $query .= "WHERE tailsign='$tailsignIdentified'";
                        }  else {
                            $query .= "WHERE id = $aircraftId";
                        }
                        
                        $result = mysqli_query($dbConnection,$query);
                        if(!$result){
                            $status = "Error updating eventFlightLegIdCount in aircrafts : " . mysqli_error($dbConnection);
                        }
                        
                        // Commit transaction
                        if(!mysqli_commit($dbConnection)){
                            $status = "Error Commiting aircrafts";
                        }
                        
                    } else {
                        // Rollback transaction
                        mysqli_rollback($dbConnection);
                        
                        $errorMsg = $eventXmlParser->getErrMsg();
                        $status = "Error while processing : $errorMsg";
                    }
                } else {
                    $status = "Rejected";
                    $failureReason = "Not a BITE or EVENT file";
                    $remarks = "Upload only BITE or EVENT files";
                }
            } else {
                $status = "Rejected";
                $failureReason = "This file has already been uploaded";
                $remarks = "";
                $statusCode = $GLOBALS['FILE_PROCESSED'];
            }
        } else {
            $status = "Rejected";
            $failureReason = "Error: ". mysql_error($dbConnection);
            $remarks = "Unexpected Error occured during the process. Please upload the file again if it is not processed";
        }
        
        $trData = array();
        $trData["FILE_NAME"] = $filename;
        $trData["FILE_SIZE"] = $fSize;
        $trData["FLIGHT_NO"] = $flightNumber;
        $trData["TS_FROM_FILE"] = $tailsignFromFile;
        $trData["TS_FOUND"] = $tailsignIdentified;
        $trData["DEP_AIRPORT"] = $depAirport;
        $trData["ARR_AIRPORT"] = $arrAirport;
        $trData["DEP_TIME"] = $depTime;
        $trData["ARR_TIME"] = $arrTime;
        $trData["STATUS"] = $status;
        $trData["FAILURE_REASON"] = $failureReason;
        $trData["REMARKS"] = $remarks;
        $trData["OFFLOAD_DATE"] = $newdatetimeformate;
        $trData["FLIGHT_LEG_ID"] = $flightLegId;
        
        array_push($dataArray, $trData);
        
        // Delete file from upload folder
        if($unlinkProcessedFile) {
            unlink($filepathname);
        }
        
    }// end for each file
    
    if($statusCode != $GLOBALS['TAILSIGN_NOT_FOUND']) {
        //Compute Aircraft Status
        computeAndUpdateAircraftStatus($dataBaseName, $mainDB, $aircraftId, $tailsignIdentified, $newdatetimeformate);
        
        //Compute Airline Status
        computeAndUpdateAirlineStatus($mainDB, $airlineId, $newdatetimeformate);
    }
    
    $processedCount = 0;
    $failureCount = 0;
    $failReason = "";
    
    foreach ($dataArray as $rowData) {
        if($rowData['STATUS'] == 'Processed') {
            $processedCount++;
//             $status = "Processed";
            $statusCode = 0;
//             $failureReason = "";
//             $remarks = "";
        } else if($rowData['STATUS'] == 'Rejected') {
            $failureCount++;
            $failReason= $rowData['FAILURE_REASON'];
            $remarks = $rowData['REMARKS'];
        }
        
        // add condition and message for new tailsign
    }

    if($processedCount > 0 && $failureCount == 0) {
        $failReason = "";
        $remarks = "";
    } else if($processedCount > 0 && $failureCount > 0) {
        $failReason = "";
        $remarks = "File processed but one of the file has only header info or file size is 0kb";
    }
    
    $keyArray = array('STATUS_CODE', 'FAILURE_REASON', 'REMARKS',  'TAIL_SIGN', 'TAILSIGN_SOURCE', 'DATA');
    $valueArray = array($statusCode, $failReason, $remarks, $tailsignIdentified, $tailsignSource, $dataArray);
    $mainArray = array_combine($keyArray, $valueArray);
    
    return $mainArray;
    
}// end processFiles

/**
 * Uploads the offload file details to be used in Download Offloads page
 *
 * @param unknown $fileName
 * @param unknown $respArray
 */
function updateOffloadMaster($source, $fileName, $fileSize, $respArray) {
    global $airlineId;
    global $mainDB;
    global $dbConnection;
    
    $fileExists = checkFileExistInOffloadMaster($airlineId, $fileName, $fileSize);
    if($fileExists) {
        return false;
    }
    
    $query = "INSERT INTO offloads_master(airlineId,fileName,fileSize,status,tailsignInFile,tailsignFound,flightNumber,depTime,arrTime,depAirport,arrAirport,offloadDate,oppFound,failureReason,remarks,flightLegIds,source,tailsignSource) VALUES";
    
    $status = "";
    $statusCode = $respArray["STATUS_CODE"];
    $tailsign = $respArray["TAIL_SIGN"];
    $tailsignSource = $respArray["TAILSIGN_SOURCE"];
    $dataArray = $respArray["DATA"];
    $failureReason = $respArray["FAILURE_REASON"];
    $remarks = $respArray["REMARKS"];
    
    $flightNumber = "";
    $depAirport = "";
    $arrAirport = "";
    $depTime = "";
    $arrTime = "";
    $offloadDate = "";
    $tailsignInFile = "";
    $remarks;
    $flightLegIds = "";
    
    if ($statusCode == 0) {
        $status = "Processed";
    } else if ($statusCode > 1) {
        $status = "Rejected";
    }
    
    $oppFound = false;
    if(!empty($dataArray)) {
        foreach ($dataArray as $rowData) {
            $offloadDate = $rowData["OFFLOAD_DATE"];
            if(! empty($rowData["TS_FROM_FILE"])) {
                $tailsignInFile = $rowData["TS_FROM_FILE"];
            }
            
            if (strpos($rowData["FILE_NAME"], "BITE_OPP") === 0) {
                $flightNumber= $rowData["FLIGHT_NO"];
                $depAirport = $rowData["DEP_AIRPORT"];
                $arrAirport = $rowData["ARR_AIRPORT"];
                $depTime = $rowData["DEP_TIME"];
                $arrTime = $rowData["ARR_TIME"];
                
                if(empty($flightNumber)) {
                    $flightNumber = $rowData["FLIGHT_LEG_ID"];
                }
                
                $oppFound = true;
            }
            
            if(!IsNullOrEmptyString($rowData["FLIGHT_LEG_ID"])) {
                $flightLegIds .= $rowData["FLIGHT_LEG_ID"] . ",";
            }
        }// end for each
        
        $flightLegIds = rtrim($flightLegIds,',');
    }
    
    if($statusCode == 0 or $statusCode > 1) {
        $query .= "($airlineId,'$fileName',$fileSize,'$status','$tailsignInFile','$tailsign','$flightNumber','$depTime','$arrTime','$depAirport','$arrAirport','$offloadDate','$oppFound','$failureReason','$remarks','$flightLegIds','$source','$tailsignSource')";
        $result = mysqli_query($dbConnection, $query);
        if (! $result) {
            echo mysqli_error($dbConnection);
            error_log(mysqli_error($dbConnection));
        }
    }
    
    if(!mysqli_commit($dbConnection)){
//         echo "Error Commiting master offloads";
        error_log("Error Commiting master offloads");
    }
    
}

/**
 * Checks if the file already exists in DB with the same size
 * @param unknown $fileName
 * @param unknown $fileSize
 * @return boolean
 */
function checkFileExistInOffloadMaster($airlineId, $fileName, $fileSize) {
    global $mainDB;
    global $dbConnection;
    
    $query = "select fileSize from banalytics.offloads_master where fileName = '$fileName' and airlineId=$airlineId";
    $result = mysqli_query($dbConnection,$query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        
        if($fileSize == $row['fileSize']) {
            return true;
        }
    }
    
    return false;
}

function printTime() {
    $now = new DateTime(null, new DateTimeZone('Asia/Kolkata'));
    echo $now->format('Y-m-d H:i:s');
}

function checkIfZipContainsFolder($fileType, $file) {
    if ($fileType == "zip") {
        $zip = new ZipArchive();
        $res = $zip->open($file);
        if ($res === TRUE) {
            for($i = 0; $i < $zip->numFiles; $i++) {
                $fName = $zip->getNameIndex($i);
                if (strpos($fName, '/') !== false) {
                    echo "<img src=\"../img/ko.png\" style=\"vertical-align:top\">Zip file should not contain folders<br>";
                    exit();
                }
            }
        }
    }
}

function startsWith($mainStr, $searchStr){
    return substr($mainStr, 0, strlen($searchStr)) == $searchStr;
}

function getFileCount($path) {
    $fi = new FilesystemIterator($apth, FilesystemIterator::SKIP_DOTS);
    return iterator_count($fi);
}

function convert($size) {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function delDirContentWithASkip($dir, $skipFile) {
    if(strlen($dir) > 1 and strpos($dir, 'upload_offloads') !== false) {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $fileinfo = pathinfo($file);
            $filename = $fileinfo['basename'];
            
            if ($filename != $skipFile) {
                if (! $file->isDir()){
                    unlink($file->getRealPath());
                } else {
                    rmdir($file->getRealPath());
                }
            }
        }
    }
}

function isValidBITEFile($fileName) {
    return (substr($fileName, 0, strlen("BITE")) == "BITE");
}

function isValidEVENTFile($fileName) {
    return (substr($fileName, 0, strlen("EVENT")) == "EVENT");
}

/**
 * Validates if the file is a valid BITE file
 *
 * @param unknown $fileWithPath
 * @return boolean
 */
function isValidBITEFileContent($fileWithPath) {
    $fileinfo = pathinfo($fileWithPath);
    $fileName = $fileinfo['basename'];
    
    $biteStrArray = array("OffloadReport","BIT_Report","FlightLegInfo","FlightPhaseInfo");
    $valid = false;
    
    if(substr($fileName, 0, strlen("BITE")) == "BITE") {
        $valid = true;
    } else {
        foreach ($biteStrArray as $searchString) {
            if(searchFileForText($fileWithPath, $searchString)) {
                $valid = true;
                break;
            }
        }
    }
    
    return $valid;
}

/**
 * Validates if the file is a valid EVENT file
 *
 * @param unknown $fileWithPath
 * @return boolean
 */
function isValidEVENTFileContent($fileWithPath) {
    $fileinfo = pathinfo($fileWithPath);
    $fileName = $fileinfo['basename'];
    
    $eventStrArray = array("SystemEventInfo","events","eventName");
    $valid = false;
    
    if(substr($fileName, 0, strlen("EVENT")) == "EVENT") {
        $valid = true;
    } else {
        foreach ($eventStrArray as $searchString) {
            if(searchFileForText($fileWithPath, $searchString)) {
                $valid = true;
                break;
            }
        }
    }
    
    return $valid;
}

/**
 * Search the given text in the given file
 * 
 * @param unknown $fileWithPath
 * @param unknown $searchString
 * @return boolean
 */
function searchFileForText($fileWithPath, $searchString) {
    $exists = false;
    
    if(file_exists($fileWithPath)){
        $fileWithPath = str_replace(" ", "\ ", $fileWithPath);
        $command = "grep $searchString $fileWithPath";
        
        if(exec($command)) {
            $exists = true;
        }
    }
    
    return $exists;
}

function checkIfFileNameContainsTailsign($dbConnection, $mainDB, $airlineId, $fileName) {
    $aircraftDAO = new AircraftDAO($dbConnection, $mainDB);
    $aircrafts = $aircraftDAO->getAircraftsForAirline($airlineId);
    $tailsign = "";
    
    foreach ($aircrafts as $aircraft) {
        if (strpos($fileName, $aircraft['tailsign']) !== false) {
            $airlineId = $aircraft['airlineId'];
            $acronym = $aircraft['acronym'];
            $tailsign = $aircraft['tailsign'];
            break;
        }
    }
    
    return $tailsign;
}
?>