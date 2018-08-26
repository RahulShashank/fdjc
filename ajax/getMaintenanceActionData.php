<?php
session_start();
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/AircraftDAO.php";

class MaintenanceActionData {
    public $dbConnection;
    public $mainDB;
    public $action = '';
    public $airlineId;
    public $platform;
    public $configType;
    public $software;
    public $tailsignList = '';
    public $failureCodeList;
    public $startDate = '';
    public $endDate = '';
    
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->platform = $_REQUEST['platform'];
        $this->configType = $_REQUEST['configType'];
        $this->software = $_REQUEST['software'];
        $this->tailsignList = $_REQUEST['tailsignList'];
        $this->failureCodeList = $_REQUEST['failureCodeList'];
        $this->startDate = $_REQUEST['startDate'];
        $this->endDate = $_REQUEST['endDate'];
    }
    
    public function hadleRequest() {
        if ($this->action == 'GET_FAILURES') {
            $failures = $this->getFailures();
            $this->sendJSONResponse($failures);
        } else if ($this->action == 'GET_FAILURE_CODES') {
            $failureCodes = $this->getFailureCodes();
            $this->sendJSONResponse($failureCodes);
        }
    }
    
    /**
     * Retrieves the Failure Codes from sys_failurerecommendation table
     * @return unknown[]
     */
    public function getFailureCodes() {
        $query = "select failureCode from $this->mainDB.sys_failurerecommendation";
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $failureCodes = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $failureCodes[] = $row['failureCode'];
            }
        }
        
        return $failureCodes;
    }
    
    public function getFailures() {
        $acDBName = "";
        $failures = array();
        $failureCodes = "";
        error_log('tailsignList selected: ' . $this->tailsignList);
        error_log('FailureCodes selected: ' . print_r($this->failureCodeList, TRUE));
        
        if(isset($this->tailsignList) and empty($this->tailsignList)) {
            error_log("Tailsign list is empty");
            $aircraftDAO = new AircraftDAO($this->dbConnection, $this->mainDB);
            $this->tailsignList = $aircraftDAO->getTailsign($this->airlineId, $this->platform, $this->configType, $this->software);
            error_log("Tailsign List after Aircraft DAO : " . print_r($this->tailsignList, TRUE));
        }
        
        if(isset($this->failureCodeList) and ! empty($this->failureCodeList)) {
            $failureCodes = rtrim(implode(",", $this->failureCodeList), ",");
        } else {
            $failureCodes = rtrim(implode(",", $this->getFailureCodes()), ",");
        }
        
        foreach ($this->tailsignList as $tailsign) {
            // Get database name
            $query = "SELECT databaseName from aircrafts WHERE tailsign='$tailsign'";
            
            if( $stmt = $this->dbConnection->prepare($query) ) {
                $stmt->execute();
                $stmt->bind_result($acDBName);
                $stmt->fetch();
                $stmt->close();
            }
            
            // Get failures
            $query = "SELECT a.accusedHostName, a.failureCode, c.failureDesc, fr.recommendation, a.idFlightLeg, a.correlationDate ".
                "FROM (".
                "SELECT t1.accusedHostName, t1.failureCode, t1.idFlightLeg, t1.correlationDate FROM $acDBName.BIT_failure t1 ".
                "JOIN (SELECT accusedHostName, failureCode, max(correlationDate) maxdate ".
                "FROM $acDBName.BIT_failure ".
                "WHERE failureCode in ($failureCodes)".
                " GROUP BY accusedHostName, failureCode) t2 ".
                "ON t1.accusedHostName = t2.accusedHostName AND t1.correlationDate = t2.maxdate".
                ") a ".
                "LEFT JOIN $this->mainDB.sys_failureinfo c ".
                "ON a.failureCode = c.failureCode ".
                "LEFT JOIN $acDBName.SYS_flightPhase b ".
                "ON a.idFlightLeg = b.idFlightLeg ".
                "LEFT JOIN $this->mainDB.sys_failurerecommendation fr ".
                "ON a.failureCode=fr.failureCode ".
                "WHERE (a.correlationDate BETWEEN b.startTime AND b.endTime) ".
                "AND (DATE(a.correlationDate) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE)) ".
                "ORDER BY a.accusedHostName";
            
            error_log("Failure Data Query: " . $query);
            
            if( $stmt = $this->dbConnection->prepare($query) ) {
                $stmt->execute();
                $stmt->bind_result($accusedHostName, $failureCode, $failureDesc, $recommendation, $idFlightLeg, $correlationDate);
                
                while ($stmt->fetch()) {
                    $failures[] = array(
                        'tailsign' => $tailsign,
                        'accusedHostName' => $accusedHostName,
                        'failureCode' => $failureCode,
                        'failureDesc' => $failureDesc,
                        'recommendation' => $recommendation,
                        'idFlightLeg' => $idFlightLeg,
                        'correlationDate' => $correlationDate,
                    );
                }
                
                $stmt->close();
            } else {
                error_log(mysqli_error($this->dbConnection));
            }
        }
        
        return $failures;
        # JSON-encode the response
        //         echo $json_response = json_encode($failures);
    }
    
    public function sendJSONResponse($data) {
        echo $json_response = json_encode($data);
    }
    
    public function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }
    
    public function echoline($msg) {
        echo "<br/>$msg<br/>";
    }
    
}

$maintenanceActionData = new MaintenanceActionData($dbConnection, $mainDB);
$maintenanceActionData->hadleRequest();

?>
