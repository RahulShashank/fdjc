<?php
session_start();
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);
require_once "../database/connecti_database.php";
include ("../engineering/checkEngineeringPermission.php");

class BiteUploadMatrixData {

    public $dbConnection;
    public $mainDB;
    public $action = '';
    public $startDate = '';
    public $endDate = '';
    public $airlineIds;
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->startDate = $_REQUEST['startDate'];
        $this->endDate = $_REQUEST['endDate'];
        $this->airlineIds = $_SESSION['airlineIds'];
    }
    
    /**
     * Main method called to start the process
     */
    public function hadleRequest() {
        if ($this->action == 'GET_HISTORY') {
            $this->getBiteUploadMatrixData();
        } else if ($this->action == 'GET_NOT_ASSIGNED_FILES') {
            $this->getUnAssignedFiles();
        }
    }
    
    public function getBiteUploadMatrixData() {
        $airlineIds = rtrim(implode(",", $this->airlineIds), ",");
        
        $query = "SELECT distinct om.airlineId, al.name as airlineName FROM $this->mainDB.offloads_master om, $this->mainDB.airlines al ".
                 "WHERE om.airlineId = al.id AND (DATE(om.uploadedTime) BETWEEN '$this->startDate 00:00:00' AND '$this->endDate 23:59:59') ".
                 "and (upper(al.name) not like '%IRVINE%' or al.acronym not like 'IRV') ";
        
        if(! $this->IsNullOrEmptyString($airlineIds) && $airlineIds != "-1") {
            $query .= " AND al.id in($airlineIds)";
        }
        
        $query .= " order by al.name";
//         error_log("Query: " . $query);
        $result = mysqli_query($this->dbConnection, $query);

        $finalData = array();
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $airlineId = $row['airlineId'];
                $airlineData = array();
                $airlineData["airline_name"] = $row['airlineName'];
                // counts
                $countQuery = "SELECT (".
                "SELECT count(om.id) FROM $this->mainDB.offloads_master om, $this->mainDB.airlines al ".
                "WHERE om.airlineId = al.id ".
                "AND (DATE(om.uploadedTime) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE)) ".
                "AND om.airlineId = $airlineId ".
                "AND om.source='Manual' ".
                "AND om.status='Processed') as manual_processed_count, ".
                "(SELECT count(om.id) FROM $this->mainDB.offloads_master om, $this->mainDB.airlines al ".
                "WHERE om.airlineId = al.id ".
                "AND (DATE(om.uploadedTime) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE)) ".
                "AND om.airlineId = $airlineId ".
                "AND om.source='Manual' ".
                "AND om.status='Rejected') as manual_rejected_count, ".
                "(SELECT count(om.id) FROM $this->mainDB.offloads_master om, $this->mainDB.airlines al ".
                "WHERE om.airlineId = al.id ".
                "AND (DATE(om.uploadedTime) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE)) ".
                "AND om.airlineId = $airlineId ".
                "AND om.source='Automatic' ".
                "AND om.status='Processed') as automatic_processed_count, ".
                "(SELECT count(om.id) FROM $this->mainDB.offloads_master om, $this->mainDB.airlines al ".
                "WHERE om.airlineId = al.id ".
                "AND (DATE(om.uploadedTime) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE)) ".
                "AND om.airlineId = $airlineId ".
                "AND om.source='Automatic' ".
                "AND om.status='Rejected') as automatic_rejected_count";
//                 error_log("count Query: " . $countQuery);
                
                $countResult = mysqli_query($this->dbConnection, $countQuery);
                if ($countResult) {
                    while ($countRow = mysqli_fetch_assoc($countResult)) {
                        $airlineData["manual_processed_count"] = $countRow['manual_processed_count'];
                        $airlineData["manual_rejected_count"] = $countRow['manual_rejected_count'];
                        // $airlineData["manual_rejected_count"] = 2;
                        $airlineData["automatic_processed_count"] = $countRow['automatic_processed_count'];
                        $airlineData["automatic_rejected_count"] = $countRow['automatic_rejected_count'];
                        // $airlineData["automatic_rejected_count"] = 2;
                        $airlineData["total_count"] = $countRow['manual_processed_count'] + $countRow['manual_rejected_count'] + $countRow['automatic_processed_count'] + $countRow['automatic_rejected_count'];
                    }
                } else {
                    error_log("Error: " . mysqli_error($this->dbConnection));
                }
                
                $finalData[] = $airlineData;
            }
        }
        
        echo $json_response = json_encode($finalData);
    }
    
    public function getUnAssignedFiles() {
        $query = "SELECT count(om.id) as not_assigned_files FROM $this->mainDB.offloads_master om WHERE (DATE(om.uploadedTime) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE)) AND om.status='NotAssigned'";
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $finalData = array();
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $finalData = $row['not_assigned_files'];
            }
        }
        
        echo $json_response = json_encode($finalData);
    }
    
    public function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }

    public function echoline($msg) {
        echo "<br/>$msg<br/>";
    }
}

$biteUploadMatrixData = new BiteUploadMatrixData($dbConnection, $mainDB);
$biteUploadMatrixData->hadleRequest();

?>