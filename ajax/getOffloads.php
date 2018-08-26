<?php
// Start the session
session_start ();
// echo 'inside here';
require_once "../database/connecti_database.php";

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

$action = $_REQUEST ['action'];
$airlineId = $_REQUEST ['airlineId'];
if($action == "getOffloads") {
    error_log("---------------------- Going to get Offloads data ----------------------");
    
    $status = $request ['status'];
    $tailsign = $request ['tailsign'];
    $depAirport = $request ['depAirport'];
    $arrAirport = $request ['arrAirport'];
    $depStartDate = $request ['depStartDate'];
    $depEndDate = $request ['depEndDate'];
    $arrStartDate = $request ['arrStartDate'];
    $arrEndDate = $request ['arrEndDate'];
    $offloadStartDate = $request ['offloadStartDate'];
    $offloadEndDate = $request ['offloadEndDate'];
    $uploadStartDate = $request ['uploadStartDate'];
    $uploadEndDate = $request ['uploadEndDate'];
    
    $finalArray = array();
    
    if(IsNullOrEmptyString($status)) {
        $processedData = getProcessedData($airlineId, $dbConnection, $mainDB, $request);
        $rejectedData = getRejectedData($airlineId, $dbConnection, $mainDB, $request);
        $finalArray = array_merge($processedData, $rejectedData);
    } else if(!IsNullOrEmptyString($status) and $status == 'Processed') {
        $finalArray = getProcessedData($airlineId, $dbConnection, $mainDB, $request);
    } else if(!IsNullOrEmptyString($status) and $status == 'Rejected') {
        $finalArray = getRejectedData($airlineId, $dbConnection, $mainDB, $request);
    }
    
    # JSON-encode the response
    $json_response = json_encode($finalArray);
    
    // # Return the response
    echo $json_response;
} else if($action == "getTailSign") {
    $query="SELECT group_concat(DISTINCT tailsignFound) as tailsignList from $mainDB.offloads_master where airlineId=$airlineId and tailsignFound <> ''";
    $result = mysqli_query($dbConnection, $query);
    error_log($query);
    $arr = array();
    if ($result and mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row;
        }
    }
    
    # JSON-encode the response
    $json_response = json_encode($arr);
    
    // # Return the response
    echo $json_response;
} else if($action == "getDepArrAirportList") {
    $query="select (SELECT group_concat(DISTINCT depAirport) FROM $mainDB.offloads_master order by depAirport) as depAirportList, (SELECT group_concat(DISTINCT arrAirport) FROM $mainDB.offloads_master order by arrAirport) as arrAirportList";
    $result = mysqli_query($dbConnection, $query);
    
    $arr = array();
    if ($result and mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row;
        }
        
    }
    # JSON-encode the response
    $json_response = json_encode($arr);
    
    // # Return the response
    echo $json_response;
} else if($_GET['action'] == "exportOffloads") {
    error_log("inside exportOffloads");

    $status = $_GET['status'];
    $format = $_GET['format'];

    $finalArray = array();
    
    if(IsNullOrEmptyString($status)) {
        $processedData = getProcessedDataForExport($airlineId, $dbConnection, $mainDB, $_GET);
        $rejectedData = getRejectedDataForExport($airlineId, $dbConnection, $mainDB, $_GET);
        $finalArray = array_merge($processedData, $rejectedData);
    } else if(!IsNullOrEmptyString($status) and $status == 'Processed') {
        $finalArray = getProcessedDataForExport($airlineId, $dbConnection, $mainDB, $_GET);
    } else if(!IsNullOrEmptyString($status) and $status == 'Rejected') {
        $finalArray = getRejectedDataForExport($airlineId, $dbConnection, $mainDB, $_GET);
    }
    
    switch($format) {
        case "xls" :
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"OffloadsExport.xls\"");
            ExportXLSFile($finalArray);
            exit();
        case "csv" :
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=\"OffloadsExport.csv\"");
            ExportCSVFile($finalArray);
            exit();
        default :
            die("Unknown foramt : ". $format);
            break;
    }
}

function IsNullOrEmptyString($str) {
    return (! isset($str) || trim($str) === '');
}

function ExportXLSFile($records) {
    $heading = false;
    if(!empty($records))
        foreach($records as $row) {
            if(!$heading) {
                // display field/column names as a first row
                echo implode("\t", array_keys($row)) . "\n";
                $heading = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
    exit;
}

function ExportCSVFile($records) {
    // create a file pointer connected to the output stream
    $fh = fopen( 'php://output', 'w' );
    $heading = false;
    if(!empty($records))
        foreach($records as $row) {
            if(!$heading) {
                // output the column headings
                fputcsv($fh, array_keys($row));
                $heading = true;
            }
            // loop over the rows, outputting them
            fputcsv($fh, array_values($row));
            
        }
    fclose($fh);
}

function getFirstToken($input) {
    $token = strtok($input, ",");
    
    if($token !== false) {
        return $token;
    }
}

/**
 * Retrieves the data from offloads_master with status as Processed.
 *  
 * @param unknown $alId
 * @param unknown $dbConnection
 * @param unknown $mainDB
 * @param unknown $request
 */
function getProcessedData($alId, $dbConnection, $mainDB, $request) {
    error_log("inside getProcessedData method");
    
    $status = $request ['status'];
    $tailsign = $request ['tailsign'];
    $depAirport = $request ['depAirport'];
    $arrAirport = $request ['arrAirport'];
    $depStartDate = $request ['depStartDate'];
    $depEndDate = $request ['depEndDate'];
    $arrStartDate = $request ['arrStartDate'];
    $arrEndDate = $request ['arrEndDate'];
    $offloadStartDate = $request ['offloadStartDate'];
    $offloadEndDate = $request ['offloadEndDate'];
    $uploadStartDate = $request ['uploadStartDate'];
    $uploadEndDate = $request ['uploadEndDate'];
    
    error_log('status: ' . $status);
    error_log('tailsign: ' . $tailsign);
    error_log('depAirport: ' . $depAirport);
    error_log('arrAirport: ' . $arrAirport);
    error_log('depStartDate: ' . $depStartDate);
    error_log('depEndDate: ' . $depEndDate);
    error_log('arrStartDate: ' . $arrStartDate);
    error_log('arrEndDate: ' . $arrEndDate);
    error_log('offloadStartDate: ' . $offloadStartDate);
    error_log('offloadEndDate: ' . $offloadEndDate);
    error_log('uploadStartDate: ' . $uploadStartDate);
    error_log('uploadEndDate: ' . $uploadEndDate);
    
    $query ="SELECT om.id, om.fileName, om.fileSize, om.status, om.tailsignInFile, om.tailsignFound as tailsign, om.flightNumber, om.depTime, om.arrTime, om.depAirport, om.arrAirport, om.failureReason, om.offloadDate, om.uploadedTime, om.remarks, om.flightLegIds, om.source, ac.id as aircraftId";
    $query .=" FROM $mainDB.offloads_master om, $mainDB.aircrafts ac WHERE om.airlineId=$alId AND om.tailsignFound=ac.tailsign AND om.status='Processed'";
    
    if(!empty($tailsign)) {
        $tailsign = array_filter($tailsign);
        
        if(!empty($tailsign)) {
            $query .=" AND (om.tailsignInFile in (";
            foreach ($tailsign as $ts) {
                $query .= "'$ts',";
            }
            $query = rtrim($query,',');
            $query .= ") OR ";
            
            $query .=" om.tailsignFound in (";
            foreach ($tailsign as $ts) {
                $query .= "'$ts',";
            }
            $query = rtrim($query,',');
            $query .= "))";
        }
    }
    
    if(!IsNullOrEmptyString($depAirport)) {
        $query .=" AND om.depAirport='$depAirport'";
    }
    
    if(!IsNullOrEmptyString($arrAirport)) {
        $query .=" AND om.arrAirport='$arrAirport'";
    }
    
    if(!IsNullOrEmptyString($depStartDate) and !IsNullOrEmptyString($depEndDate)) {
        $query .=" AND (om.depTime between '$depStartDate' and '$depEndDate')";
    }
    
    if(!IsNullOrEmptyString($arrStartDate) and !IsNullOrEmptyString($arrEndDate)) {
        $query .=" AND (om.arrTime between '$arrStartDate' and '$arrEndDate')";
    }
    
    if(!IsNullOrEmptyString($offloadStartDate) and !IsNullOrEmptyString($offloadEndDate)) {
        $query .=" AND (om.offloadDate between '$offloadStartDate' and '$offloadEndDate')";
    }
    
    if(!IsNullOrEmptyString($uploadStartDate) and !IsNullOrEmptyString($uploadEndDate)) {
        $query .=" AND (om.uploadedTime between '$uploadStartDate' and '$uploadEndDate')";
    } else {
        $query .=" AND om.uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
    }
    
    $query .=" ORDER BY om.uploadedTime DESC";
    $result = mysqli_query($dbConnection, $query);
    error_log("Query to get Processed Data: " .$query, 0);
    
    $arr = array();
    if ($result and mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if($row['status'] == "Processed" && empty($row['flightNumber'])) {
                $flightLegId = getFirstToken($row['flightLegIds']);
                $row['flightNumber'] = $flightLegId;
            }
            
            $arr[] = $row;
        }
        
    }
    return $arr;
}

/**
 * Retrieves the data from offloads_master with status as Processed.
 * 
 * @param unknown $alId
 * @param unknown $dbConnection
 * @param unknown $mainDB
 * @param unknown $request
 * @return unknown[]
 */
function getRejectedData($alId, $dbConnection, $mainDB, $request) {
    error_log('inside getRejectedData method');
    
    $depAirport = $request ['depAirport'];
    $arrAirport = $request ['arrAirport'];
    $depStartDate = $request ['depStartDate'];
    $depEndDate = $request ['depEndDate'];
    $arrStartDate = $request ['arrStartDate'];
    $arrEndDate = $request ['arrEndDate'];
    $offloadStartDate = $request ['offloadStartDate'];
    $offloadEndDate = $request ['offloadEndDate'];
    $uploadStartDate = $request ['uploadStartDate'];
    $uploadEndDate = $request ['uploadEndDate'];
    
    error_log('status: ' . $status);
    error_log('tailsign: ' . $tailsign);
    error_log('depAirport: ' . $depAirport);
    error_log('arrAirport: ' . $arrAirport);
    error_log('depStartDate: ' . $depStartDate);
    error_log('depEndDate: ' . $depEndDate);
    error_log('arrStartDate: ' . $arrStartDate);
    error_log('arrEndDate: ' . $arrEndDate);
    error_log('offloadStartDate: ' . $offloadStartDate);
    error_log('offloadEndDate: ' . $offloadEndDate);
    error_log('uploadStartDate: ' . $uploadStartDate);
    error_log('uploadEndDate: ' . $uploadEndDate);
    
    $query ="SELECT om.id, om.fileName, om.fileSize, om.status, om.tailsignInFile, om.tailsignFound as tailsign, om.flightNumber, om.depTime, om.arrTime, om.depAirport, om.arrAirport, om.failureReason, om.offloadDate, om.uploadedTime, om.remarks, om.flightLegIds, om.source";
    $query .=" FROM $mainDB.offloads_master om WHERE om.airlineId=$alId AND om.status='Rejected'";
    
    if(!IsNullOrEmptyString($depAirport)) {
        $query .=" AND om.depAirport='$depAirport'";
    }
    
    if(!IsNullOrEmptyString($arrAirport)) {
        $query .=" AND om.arrAirport='$arrAirport'";
    }
    
    if(!IsNullOrEmptyString($depStartDate) and !IsNullOrEmptyString($depEndDate)) {
        $query .=" AND (om.depTime between '$depStartDate' and '$depEndDate')";
    }
    
    if(!IsNullOrEmptyString($arrStartDate) and !IsNullOrEmptyString($arrEndDate)) {
        $query .=" AND (om.arrTime between '$arrStartDate' and '$arrEndDate')";
    }
    
    if(!IsNullOrEmptyString($offloadStartDate) and !IsNullOrEmptyString($offloadEndDate)) {
        $query .=" AND (om.offloadDate between '$offloadStartDate' and '$offloadEndDate')";
    }
    
    if(!IsNullOrEmptyString($uploadStartDate) and !IsNullOrEmptyString($uploadEndDate)) {
        $query .=" AND (om.uploadedTime between '$uploadStartDate' and '$uploadEndDate')";
    } else {
        $query .=" AND om.uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
    }
    
    $query .=" ORDER BY om.uploadedTime DESC";
    $result = mysqli_query($dbConnection, $query);
    error_log("Query to get Rejected data: " .$query, 0);
    
    $arr = array();
    if ($result and mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row;
        }
    }
    return $arr;
    
}

/**
 * Retrieves the processed data from offloads_master for export
 * 
 * @param unknown $alId
 * @param unknown $dbConnection
 * @param unknown $mainDB
 * @param unknown $GET
 */
function getProcessedDataForExport($alId, $dbConnection, $mainDB, $request) {
    error_log('inside getProcessedDataForExport method');
    
    // echo $status;
    $airlineId = $request['airlineId'];
    
    $tailsign = $request['tailsign'];
    // echo $tailsign;
    
    $depAirport = $request['depAirport'];
    // echo $depAirport;
    
    $arrAirport = $request['arrAirport'];
    // echo $arrAirport;
    
    $depStartDate = $request['depStartDate'];
    // echo $depStartDate;
    
    $depEndDate = $request['depEndDate'];
    // echo $depEndDate;
    
    $arrStartDate = $request['arrStartDate'];
    // echo $arrStartDate;
    
    $arrEndDate = $request['arrEndDate'];
    // echo $arrEndDate;
    
    $offloadStartDate = $request['offloadStartDate'];
    // echo $offloadStartDate;
    
    $offloadEndDate = $request['offloadEndDate'];
    // echo $offloadEndDate;
    
    $uploadStartDate = $request['uploadStartDate'];
    // echo $uploadStartDate;
    
    $uploadEndDate = $request['uploadEndDate'];
    // echo $uploadEndDate ;
    
    $format = $request['format'];
    // echo $format;
    
    $query ="SELECT fileName as FileName, fileSize as FileSize, status as Status, tailsignFound as TailSign, flightNumber as FlightNumber, depTime as DepartureTime, arrTime as ArrivalTime, depAirport as DepartureAirport, arrAirport as ArrivalAirport, offloadDate as OffloadDate, uploadedTime as UploadTime, failureReason as FailureReason, remarks as Remarks, source as Source";
    $query .=" FROM $mainDB.offloads_master WHERE airlineId=$airlineId AND status='Processed'";
    
    if(!empty($tailsign) && $tailsign !== 'null') {
        $tailsign = explode(",", $tailsign);
        
        if(!empty($tailsign)) {
            $query .=" AND (tailsignInFile in (";
            foreach ($tailsign as $ts) {
                $query .= "'$ts',";
            }
            $query = rtrim($query,',');
            $query .= ") OR ";
            
            $query .=" tailsignFound in (";
            foreach ($tailsign as $ts) {
                $query .= "'$ts',";
            }
            $query = rtrim($query,',');
            $query .= "))";
        }
    }
    
    if(!IsNullOrEmptyString($depAirport)) {
        $query .=" AND depAirport='$depAirport'";
    }
    
    if(!IsNullOrEmptyString($arrAirport)) {
        $query .=" AND arrAirport='$arrAirport'";
    }
    
    if(!IsNullOrEmptyString($depStartDate) and !IsNullOrEmptyString($depEndDate)) {
        $query .=" AND (depTime between '$depStartDate' and '$depEndDate')";
    }
    
    if(!IsNullOrEmptyString($arrStartDate) and !IsNullOrEmptyString($arrEndDate)) {
        $query .=" AND (arrTime between '$arrStartDate' and '$arrEndDate')";
    }
    
    if(!IsNullOrEmptyString($offloadStartDate) and !IsNullOrEmptyString($offloadEndDate)) {
        $query .=" AND (offloadDate between '$offloadStartDate' and '$offloadEndDate')";
    }
    
    if(!IsNullOrEmptyString($uploadStartDate) and !IsNullOrEmptyString($uploadEndDate)) {
        $query .=" AND (uploadedTime between '$uploadStartDate' and '$uploadEndDate')";
    } else {
        $query .=" AND uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
    }
    
    $query .=" ORDER BY uploadedTime DESC";
    $result = mysqli_query($dbConnection, $query);
    error_log("Query to get processed data for export: " .$query, 0);
    
    $arr = array();
    if ($result and mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            //             echo "FileSize: ". $row['FileSize'] . "<br/>";
            $row['FileSize'] = strval(round($row['FileSize'] / 1024, 2)) . " KB";
            $arr[] = $row;
        }
    }
    
    return $arr;
}

/**
 * Retrieves the rejected data from offloads_master for export
 *
 * @param unknown $alId
 * @param unknown $dbConnection
 * @param unknown $mainDB
 * @param unknown $GET
 */
function getRejectedDataForExport($alId, $dbConnection, $mainDB, $request) {
    error_log('getRejectedDataForExport method entered');
    // echo $status;
    $airlineId = $request['airlineId'];
    
    $tailsign = $request['tailsign'];
    // echo $tailsign;
    
    $depAirport = $request['depAirport'];
    // echo $depAirport;
    
    $arrAirport = $request['arrAirport'];
    // echo $arrAirport;
    
    $depStartDate = $request['depStartDate'];
    // echo $depStartDate;
    
    $depEndDate = $request['depEndDate'];
    // echo $depEndDate;
    
    $arrStartDate = $request['arrStartDate'];
    // echo $arrStartDate;
    
    $arrEndDate = $request['arrEndDate'];
    // echo $arrEndDate;
    
    $offloadStartDate = $request['offloadStartDate'];
    // echo $offloadStartDate;
    
    $offloadEndDate = $request['offloadEndDate'];
    // echo $offloadEndDate;
    
    $uploadStartDate = $request['uploadStartDate'];
    // echo $uploadStartDate;
    
    $uploadEndDate = $request['uploadEndDate'];
    // echo $uploadEndDate ;
    
    $format = $request['format'];
    // echo $format;
    
    $query ="SELECT fileName as FileName, fileSize as FileSize, status as Status, tailsignFound as TailSign, flightNumber as FlightNumber, depTime as DepartureTime, arrTime as ArrivalTime, depAirport as DepartureAirport, arrAirport as ArrivalAirport, offloadDate as OffloadDate, uploadedTime as UploadTime, failureReason as FailureReason, remarks as Remarks, source as Source";
    $query .=" FROM $mainDB.offloads_master WHERE airlineId=$airlineId AND status='Rejected'";
    
    if(!IsNullOrEmptyString($depAirport)) {
        $query .=" AND depAirport='$depAirport'";
    }
    
    if(!IsNullOrEmptyString($arrAirport)) {
        $query .=" AND arrAirport='$arrAirport'";
    }
    
    if(!IsNullOrEmptyString($depStartDate) and !IsNullOrEmptyString($depEndDate)) {
        $query .=" AND (depTime between '$depStartDate' and '$depEndDate')";
    }
    
    if(!IsNullOrEmptyString($arrStartDate) and !IsNullOrEmptyString($arrEndDate)) {
        $query .=" AND (arrTime between '$arrStartDate' and '$arrEndDate')";
    }
    
    if(!IsNullOrEmptyString($offloadStartDate) and !IsNullOrEmptyString($offloadEndDate)) {
        $query .=" AND (offloadDate between '$offloadStartDate' and '$offloadEndDate')";
    }
    
    if(!IsNullOrEmptyString($uploadStartDate) and !IsNullOrEmptyString($uploadEndDate)) {
        $query .=" AND (uploadedTime between '$uploadStartDate' and '$uploadEndDate')";
    } else {
        $query .=" AND uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
    }
    
    $query .=" ORDER BY uploadedTime DESC";
    $result = mysqli_query($dbConnection, $query);
    error_log("Query to get processed data for export: " .$query, 0);
    
    $arr = array();
    if ($result and mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            //             echo "FileSize: ". $row['FileSize'] . "<br/>";
            $row['FileSize'] = strval(round($row['FileSize'] / 1024, 2)) . " KB";
            $arr[] = $row;
        }
    }
    
    return $arr;
}

?>
