<?php 
define("AUTO_IMPORT_REPO", '/usr/local/bitefiles_repository');
define("UPLOAD_OFFLOADS_DIR", dirname(dirname(__FILE__)).'/upload_offloads');

require_once "../database/connecti_database.php";
require_once "../common/AirlineDAO.php";
require_once "../common/CommonUtils.php";
require_once "../dao/OffloadsMasterDAO.php";

session_start();

// define("FTP_FOLDER", "/var/www/html/bite_analytics/cronjobs/ftp");
// define("FTP_FOLDER", "/home/bitnami/ftp");
// define("FTP_FOLDER", "/home/exchangebitefiles/files");

// $inputFileName = $_GET['filename'];
$inputFileName = $_POST['filename'];
$fileinfo = pathinfo($inputFileName);
$inputFileExtn = $fileinfo['extension'];
error_log("####### Automatic Import of BITE files #######");
error_log("Input File Name is : $inputFileName");

$statusFile = "cronstatus.dat";
$file = null;
$readyForProcess = false;

$file = @fopen($statusFile, 'r');

if (FALSE === $file) {
    error_log("Failed to open the file or file is not available");
    writeData($statusFile, "IN_PROGRESS");
    $readyForProcess = true;
} else {
    $contents = stream_get_contents($file);
    $contents = str_replace(array("\r", "\n"), '', $contents);
    
    error_log("Status is - $contents");
    if ($contents == "COMPLETED" or $contents == "") {
        writeData($statusFile, "IN_PROGRESS");
        $readyForProcess = true;
    }
}

$AUTO_IMPORT_TMP_DIR = AUTO_IMPORT_REPO . DIRECTORY_SEPARATOR . "tmp";

if($readyForProcess) {
    error_log("Processing started for file - $inputFileName");
    
    // Process the file
    if($inputFileExtn == "tgz") {
        error_log("tgz file is going to be processed");
        
        // check if the file is a duplicate file by checking the filename contains the pattern XXXXX(x).XXX
        if(CommonUtils::isDuplicateFile($fileinfo['basename'])) {
            error_log("$inputFileName - is a duplicate file, hence stop processing the file and deleting it.");
            unlink($inputFileName);
            writeData($statusFile, "COMPLETED");
            exit;
        }
        
        $airlineId = 0;
        $acronym = "";
        $tgzFileName = $fileinfo['basename'];
        $tarFile = $AUTO_IMPORT_TMP_DIR . DIRECTORY_SEPARATOR . $fileinfo['filename'] . ".tar";
        
        // Copy file from ftp directory to tmp directory
        $tmpDirTgzFileWithPath = $AUTO_IMPORT_TMP_DIR . DIRECTORY_SEPARATOR . $tgzFileName;
        if (copyFiles($inputFileName, $tmpDirTgzFileWithPath)) {
            unlink($inputFileName);
        }
        
        // check if the file name contains tailsign
        if ($airlineId == 0) {
            $aircrafts = getAircrafts($dbConnection);
            $tailsign = "";
            
            foreach ($aircrafts as $aircraft) {
                if (contains($tgzFileName, $aircraft['tailsign'])) {
                    $airlineId = $aircraft['airlineId'];
                    $acronym = $aircraft['acronym'];
                    $tailsign = $aircraft['tailsign'];
                    $_SESSION['tailsign'] = $tailsign;
                    $_SESSION['tailsignSource'] = "File Name";
                    break;
                }
            }
        }
        
        // Extract and read the file content and check the serial_number table
        if($airlineId == 0) {
            $tailsign = "";
            
            try {
                // Extract tgz
                $p = new PharData($tmpDirTgzFileWithPath);
                $p->decompress();
                
                // Extract tar
                $phar = new PharData($tarFile);
                $phar->extractTo($AUTO_IMPORT_TMP_DIR);
                unset($phar);
                Phar::unlinkArchive($tarFile);
                
                // Read the file content to check the serial number table
                $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($AUTO_IMPORT_TMP_DIR));
                foreach ($dir as $fileinfo) {
                    if ($fileinfo->isFile() && pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION) == "xml") {
                        $xmlFileWithPath = $fileinfo->getPathname();
                        
                        if(isValidBITE($xmlFileWithPath)) {
                            $xml = @simplexml_load_file($xmlFileWithPath);
                            
                            // check if we could find the tailsign from the tailsign in inside file content
                            $tailsignfromFile = trim($xml->FlightLegInfo[0]['AircraftTailSign']);
                            // Remove the special character (.) and query to identify the tailsign.
                            $tailsignfromFile = ltrim($tailsignfromFile, "."); // It looks like some i5000 files put a '.' before the tailsign
                            // check if able to get the DB and other details
                            $query = "SELECT ac.* FROM $mainDB.aircrafts ac, $mainDB.airlines al WHERE ac.airlineId=al.id and (upper(al.name) not like '%IRVINE%' or al.acronym not like 'IRV') and ac.tailsign='$tailsignfromFile'";
                            $result = mysqli_query($dbConnection, $query);
                            if ($result && mysqli_num_rows($result) > 0) {
                                $row = mysqli_fetch_assoc($result);
                                $tailsign = $row['tailsign'];
                                $_SESSION['tailsignSource'] = "File content";
                                $_SESSION['tailsignFromFile'] = $tailsignfromFile;
                                break;
                            }
                            
                            $masterTailSignArray = getSerialNumberInfoData($dbConnection);
                            $tailsign = getTailSignFromSerialNumber($xml, $masterTailSignArray);
                            if(!empty($tailsign)) {
                                $_SESSION['tailsignSource'] = "Serial Number search";
                                break;
                            }
                        }
                    }
                }
                delDirWithASkip($AUTO_IMPORT_TMP_DIR, $tgzFileName);
            } catch (Exception $e) {
                error_log($e);
            }
            
            if(!empty($tailsign)) {
                $airlineDao = new AirlineDAO($dbConnection, $mainDB);
                $airline = $airlineDao->findAirlineByTailsign($tailsign);
                $airlineId = $airline['id'];
                $acronym = $airline['acronym'];
            }
            
        }
        
        // Check if the filename contains airline acronym
        if($airlineId == 0) {
            $airlines = getAirlines($dbConnection);
            foreach ($airlines as $airline) {
                if (contains($tgzFileName, $airline['acronym'])) {
                    $airlineId = $airline['id'];
                    $acronym = $airline['acronym'];
                    break;
                }
            }
        }
        
        if ($airlineId > 0) {
            error_log("Airline Id found : " . $airlineId);
            
//             if($airlineId == 16 || $airlineId == 31 || $airlineId == 1) {
                
                // Set the values in session so that it could be used in ProcessOffloads.php
                $autoImportDir = UPLOAD_OFFLOADS_DIR . DIRECTORY_SEPARATOR . "auto_import";
                if (! file_exists($autoImportDir)) {
                    mkdir($autoImportDir, 0755);
                }
                
                $tgzFileInUploadDir = $autoImportDir . DIRECTORY_SEPARATOR . $tgzFileName;
                if (copyFiles($tmpDirTgzFileWithPath, $tgzFileInUploadDir)) {
                    unlink($tmpDirTgzFileWithPath);
                }
                
                $_SESSION['airlineId'] = $airlineId;
                $_SESSION['acronym'] = $acronym;
                $_SESSION['targetPath'] = $autoImportDir;
                $_SESSION['targetFileName'] = $tgzFileName;
                $_SESSION['targetFile'] = $tgzFileInUploadDir;
                $_SESSION['targetFileType'] = $inputFileExtn;
                $_SESSION['archivePath'] = UPLOAD_OFFLOADS_DIR . DIRECTORY_SEPARATOR . "Archive" . DIRECTORY_SEPARATOR;
                $_SESSION['disablePermissionCheck'] = true;
                $_SESSION['source'] = "Automatic";
                
                try {
//                     include ('../engineering/ProcessOffloads.php');
                    include_once 'ProcessOffloads.php';
                } catch (Exception $e) {
                    error_log("Exception in ProcessOffloads from ImportBITEFiles: " . $e);
                }
//             } else {
//                 error_log("This file does not belong to OMA or FWI or AAL : $inputFileName");
                
//                 $unProcessedArchiveDir = FTP_FOLDER . DIRECTORY_SEPARATOR . "UN_PROCESSED_FILES";
//                 if (! file_exists($unProcessedArchiveDir)) {
//                     mkdir($unProcessedArchiveDir, 0755, true);
//                 }
                
//                 $archiveDirTgzFileWithPath = $unProcessedArchiveDir . DIRECTORY_SEPARATOR . $tgzFileName;
//                 if (copyFiles($tmpDirTgzFileWithPath, $archiveDirTgzFileWithPath)) {
//                     unlink($tmpDirTgzFileWithPath);
//                 }
//             }
        } else {
//             error_log("Could not identify the Airline for this file: $inputFileName");

//             $unProcessedArchiveDir = FTP_FOLDER . DIRECTORY_SEPARATOR . "UN_PROCESSED_FILES";
//             if (! file_exists($unProcessedArchiveDir)) {
//                 mkdir($unProcessedArchiveDir, 0755, true);
//             }
            
//             $archiveDirTgzFileWithPath = $unProcessedArchiveDir . DIRECTORY_SEPARATOR . $tgzFileName;
//             if (copyFiles($tmpDirTgzFileWithPath, $archiveDirTgzFileWithPath)) {
//                 unlink($tmpDirTgzFileWithPath);
//             }
            
            error_log("Airline Id is NOT found for this file");
            $fileSize = 0;
            $tgzInNotAssignedDir = UPLOAD_OFFLOADS_DIR . DIRECTORY_SEPARATOR . "Archive" . DIRECTORY_SEPARATOR . "Unassigned" . DIRECTORY_SEPARATOR . $tgzFileName;
            $fileSize = filesize($tmpDirTgzFileWithPath);
            
            if (copyFiles($tmpDirTgzFileWithPath, $tgzInNotAssignedDir)) {
                unlink($tmpDirTgzFileWithPath);
            }
            
            // Update the offloads_master table with this data
            $offloadsMasterDAO = new OffloadsMasterDAO();
            $offloadsMasterDAO->update(0, $tgzFileName, $fileSize, "Unassigned", "", "", "", "", "", "", "", "0000-00-00", 0, "", "", "", "Automatic");
        }
    } elseif ($inputFileExtn == "zip") {        
        // Extract the files and delete the zip 
        $zip = new ZipArchive;
        if ($zip->open($inputFileName) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i ++) {
                $tgzFileName = $zip->getNameIndex($i);
                $fileinfo = pathinfo($tgzFileName);
                $extension = $fileinfo['extension'];
                
                if (strcasecmp($extension, "tgz") == 0) {
                    try {
                        $destFile = pathinfo($inputFileName, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $tgzFileName;
                        copyFiles("zip://" . $inputFileName . "#" . $tgzFileName, $destFile);
                        error_log("$tgzFileName copied to tmp folder and is going to be deleted");
                    }catch (Exception $e) {
                        error_log("AutoImportBITEFiles.php => Exception while processing zip file");
                    }
                }
            }
            
            $zip->close();
            unlink($inputFileName);
        } else {
            error_log("Extracting zip file failed");
        }
    }// end else if

    // Process completed
    error_log("Processing completed");
    writeData($statusFile, "COMPLETED");
} else {
    error_log("A BITE file is being processed. So exiting the process");
    exit;
}

function writeData($inputFile, $data) {
    $file = @fopen($inputFile, 'w');
    if (FALSE === $file) 
        error_log("Error in opening file - $inputFile - in write mode");
    
    fwrite($file, $data);
    fclose($file);
}

/**
 * Validates if the file is a valid BITE file
 *
 * @param unknown $fileWithPath
 * @return boolean
 */
function isValidBITE($fileWithPath) {
    $fileinfo = pathinfo($fileWithPath);
    $fileName = $fileinfo['basename'];
    
    $biteStrArray = array("OffloadReport","BIT_Report","FlightLegInfo","FlightPhaseInfo");
    $valid = false;
    
    if(substr($fileName, 0, strlen("BITE")) == "BITE") {
        $valid = true;
    } else {
        foreach ($biteStrArray as $searchString) {
            if(searchTextInFile($fileWithPath, $searchString)) {
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
function searchTextInFile($fileWithPath, $searchString) {
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

function getAirlines($dbConnection) {
    $query = "SELECT id,acronym FROM $mainDB.airlines where (upper(name) not like '%IRVINE%' or acronym not like 'IRV') ORDER BY name";
    $result = mysqli_query($dbConnection, $query);
    
    $airlines = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $airlines[] = $row;
        }
    }
    
    return $airlines;
}

function getAircrafts($dbConnection) {
    //     $query = "select ac.tailsign,ac.airlineId,al.acronym from $mainDB.aircrafts ac, $mainDB.airlines al where ac.airlineId=al.id order by ac.airlineId, ac.tailsign";
    $query = "select ac.tailsign,ac.airlineId,al.acronym from $mainDB.aircrafts ac, $mainDB.airlines al where ac.airlineId=al.id and (upper(al.name) not like '%IRVINE%' or al.acronym not like 'IRV') order by ac.airlineId, ac.tailsign";
    
    $result = mysqli_query($dbConnection, $query);
    
    $aircrafts = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $aircrafts[] = $row;
        }
    }
    
    return $aircrafts;
}

function contains($mainString, $searchString) {
    $exists = false;
    
    if (strpos($mainString, $searchString) !== false) {
        $exists = true;
    }
    
    return $exists;
}

/**
 * Retrieves the data from master tailsign table for the set of LRUs for validation
 *
 * @param unknown $dbConnection
 * @return array[]|string[][]
 */
function getSerialNumberInfoData($dbConnection) {
    $tailsignFromDB = "";
    $prevTailsignFromDB = "";
    $hostNameFromDB = "";
    $serialNoFromDB = "";
    $mainTSArray = array();
    $tempArray = array();
    
    $lruStr = "'DSU1','DSU2','ICMT1','SVDU1A','SVDU1C','SVDU2A','SVDU2C','SVDU3A','SVDU3C','SVDU10A','SVDU10C','SVDU11A','SVDU11C'";
    
//     $query = "select airline_id, tailsign, host_name, serial_number from $mainDB.serialnumber_info where host_name in($lruStr) order by airline_id, tailsign, FIELD(host_name, $lruStr)";
    $query = "select si.airline_id, si.tailsign, si.host_name, si.serial_number from $mainDB.serialnumber_info si, airlines al where si.airline_id=al.id and (upper(al.name) not like '%IRVINE%' or al.acronym not like 'IRV') and si.host_name in($lruStr) order by si.airline_id, si.tailsign, FIELD(si.host_name, $lruStr)";
    
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
        error_log(mysqli_error($dbConnection));
    } // end if
    
    return $mainTSArray;
}

/**
 * Compares LRU & Serial No from file and master table and find the TailSign
 *
 * @param unknown $xml
 * @param unknown $masterTailSignArray
 * @return string|unknown
 */
function getTailSignFromSerialNumber($xml, $masterTailSignArray) {
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

function delDirWithASkip($dir, $skipFile) {
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


function copyFiles($sourceFile, $destFile) {
    $path = pathinfo($destFile);
    error_log("SourceFile: $sourceFile");
    error_log("DestFile: $destFile");
    if (! file_exists($path['dirname'])) {
        mkdir($path['dirname'], 0755, true);
    }
    if (! copy($sourceFile, $destFile)) {
        echo "copy failed \n";
        return false;
    }
    
    return true;
}

?>
?>?>