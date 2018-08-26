<?php
require_once "../database/connecti_database.php";
require_once ('../engineering/checkEngineeringPermission.php');
/*
 * $postdata = file_get_contents ( "php://input" );
 * $request = json_decode ( $postdata, true );
 * $serialNumber = trim($_REQUEST['res']);
 */
$data = json_decode(stripslashes($_POST['data']));
$resultArr = [];
$data = array_unique($data);
foreach ($data as $d) {
    $d=trim($d);
    // $querytailSign = "select rui.tailSign as tailSign, rui.removalDate as removalDate from repair_unitInfo rui,repair_serialNumber rsn where rsn.serialNumber = '$d' and rsn.id = rui.idSerialNumber ";
//     $querytailSign = "select rui.tailSign as repairTailSign, rui.removalDate as removalDate, rfi.removalReason as removalReason, rfi.issueDetected as issueDetected, rfi.correctiveAction as correctiveAction, rfi.Rmv_type as Rmv_type from repair_unitInfo rui,repair_serialNumber rsn, repair_failureinfo rfi where rsn.serialNumber = '$d' and rsn.id = rui.idSerialNumber and rui.id=rfi.idUnit";
    $querytailSign = "select al.acronym as acronym, a.tailsign, rui.tailSign as repairTailSign, rui.removalDate as removalDate, rfi.removalReason as removalReason, 
                    rfi.issueDetected as issueDetected, rfi.correctiveAction as correctiveAction, rfi.Rmv_type as Rmv_type, a.databaseName AS DBName 
                    from $mainDB.repair_unitInfo rui, $mainDB.repair_serialNumber rsn, $mainDB.repair_failureinfo rfi, $mainDB.aircrafts a, $mainDB.airlines al where rsn.serialNumber = '$d' 
                    and rsn.id = rui.idSerialNumber and rui.id=rfi.idUnit and rui.airlineId = a.airlineId and a.repair_tailName=rui.tailSign and rui.airlineId=al.id";
    
    $resulttailSign = mysqli_query($dbConnection, $querytailSign);
    if ($resulttailSign) {
        while ($row = mysqli_fetch_array($resulttailSign)) {
            $resultSubArr = array();
            $resultSubArr["serialNumber"] = $d;
            $resultSubArr["removalDate"] = $row["removalDate"];
            $resultSubArr["repairTailSign"] = $row["repairTailSign"];
            $removalDate = $resultSubArr["removalDate"];
            $resultSubArr['removalReason'] = $row["removalReason"];
            $resultSubArr['issueDetected'] = $row["issueDetected"];
            $resultSubArr['correctiveAction'] = $row["correctiveAction"];
            $resultSubArr['Rmv_Type'] = $row["Rmv_type"];
            $resultSubArr["acronym"] = $row["acronym"];
            $resultSubArr["tailSign"] = $row["tailsign"];
            $dbName = $row["DBName"];
            error_log("DB Name retrieved from aircrafts table is : $dbName");
            
            // Get the acronym for the Serial Number
//             $queryAcronym = "select distinct a.acronym from repair_serialNumber rsn, repair_unitInfo rui, airlines a where rsn.serialNumber='$d' and rsn.id=rui.idSerialNumber and rui.airlineId=a.id";
//             $resultAncronym = mysqli_query($dbConnection, $queryAcronym);
//             if ($resultAncronym) {
//                 $row = mysqli_fetch_assoc($resultAncronym);
//                 $resultSubArr["acronym"] = $row["acronym"];
//             }

//             $queryForDBList = "select a.tailsign, a.databaseName AS DBName from repair_serialNumber rsn, repair_unitInfo rui, aircrafts a where rsn.serialNumber='$d' and rsn.id=rui.idSerialNumber and rui.airlineId = a.airlineId and rui.tailSign=a.repair_tailName and rui.tailSign='" . $resultSubArr["repairTailSign"] . "'";
//             $queryForDBList = "select a.tailsign, a.databaseName AS DBName from aircrafts a where a.repair_tailName='" . $resultSubArr["repairTailSign"] . "'";
//             $resultForDBList = mysqli_query($dbConnection, $queryForDBList);
//             if ($resultForDBList) {
//                 $row = mysqli_fetch_array($resultForDBList);
//                 $dbName = $row["DBName"];
//                 $resultSubArr["tailSign"] = $row["tailsign"];
//                 error_log("DB Name retrieved from aircrafts table is : $dbName");
//             }
            
            $resultSubArr['faultBR'] = 0;
            $resultSubArr['failuresBR'] = 0;
            $resultSubArr['resetsBR'] = 0;
            $resultSubArr['faultAR'] = 0;
            $resultSubArr['failuresAR'] = 0;
            $resultSubArr['resetsAR'] = 0;
            
            $queryTFlBR = "select count(bfl.serialNumber) AS TFaultBR from $dbName.BIT_fault bfl where DATE(bfl.detectionTime) < '$removalDate' and bfl.serialNumber = '$d'";
            $resultTFlBR = mysqli_query($dbConnection, $queryTFlBR);
            if ($resultTFlBR) {
                $row = mysqli_fetch_array($resultTFlBR);
//                 array_push($countTflBR, $row["TFaultBR"]);
                $resultSubArr['faultBR'] = $row["TFaultBR"];
            }
            
            $queryTFrBR = "select count(bfr.serialNumber) AS TFailureBR from $dbName.BIT_failure bfr where DATE(bfr.correlationDate) < '$removalDate' and bfr.serialNumber = '$d'";
            $resultTFrBR = mysqli_query($dbConnection, $queryTFrBR);
            if ($resultTFrBR) {
                $row = mysqli_fetch_array($resultTFrBR);
//                 array_push($countTfrBR, $row["TFailureBR"]);
                $resultSubArr['failuresBR'] = $row["TFailureBR"];
            }
            
            $queryTRBR = "select count(bevts.serialNumber) AS TRBR from $dbName.BIT_events bevts where DATE(bevts.lastUpdate) < '$removalDate' and bevts.serialNumber = '$d'";
            $resultTRBR = mysqli_query($dbConnection, $queryTRBR);
            if ($resultTRBR) {
                $row = mysqli_fetch_array($resultTRBR);
//                 array_push($countTRBR, $row["TRBR"]);
                $resultSubArr['resetsBR'] = $row["TRBR"];
            }
            
            $queryTFlAR = "select count(bfl.serialNumber) AS TFalutAR from $dbName.BIT_fault bfl where DATE(bfl.detectionTime) > '$removalDate' and bfl.serialNumber = '$d'";
            $resultTFlAR = mysqli_query($dbConnection, $queryTFlAR);
            if ($resultTFlAR) {
                $row = mysqli_fetch_array($resultTFlAR);
//                 array_push($countTflAR, $row["TFalutAR"]);
                $resultSubArr['faultAR'] = $row["TFalutAR"];
            }
            
            $queryTFrAR = "select count(bfr.serialNumber) AS TFailureAR from $dbName.BIT_failure bfr where DATE(bfr.correlationDate) > '$removalDate' and bfr.serialNumber = '$d'";
            $resultTFrAR = mysqli_query($dbConnection, $queryTFrAR);
            if ($resultTFrAR) {
                $row = mysqli_fetch_array($resultTFrAR);
//                 array_push($countTfrAR, $row["TFailureAR"]);
                $resultSubArr['failuresAR'] = $row["TFailureAR"];
            }
            
            $resetsAfterRemovalQuery = "select count(bevts.serialNumber) AS TRAR from $dbName.BIT_events bevts where DATE(bevts.lastUpdate) > '$removalDate' and bevts.serialNumber = '$d'";
            $resultTRAR = mysqli_query($dbConnection, $resetsAfterRemovalQuery);
            if ($resultTRAR) {
                $row = mysqli_fetch_array($resultTRAR);
//                 array_push($countTfRAR, $row["TRAR"]);
                $resultSubArr['resetsAR'] = $row["TRAR"];
            }
            
            array_push($resultArr, $resultSubArr);
        } // end while
    }
} // end for each Serial Number
echo $json_response = json_encode($resultArr);
?>