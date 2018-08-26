<?php
session_start();
require_once "../database/connecti_database.php";
require_once "../common/functions.php";

/**
 * Handles all the requests from FlightScoring.php
 * 
 * @author
 */
class FlightScoring {

    public $dbConnection;
    public $mainDB;
    public $action = '';
    public $airlineId = '';
    public $airlineIds = '';
    public $aircraftId = '';
    public $startDate = '';
    public $endDate = '';
    public $flightLegId= '';
    public $remark = '';
    public $lruWeight = array();
    public $platform = '';
    public $configType = '';
    public $software = '';
    public $tailsignList = '';
    public $category = '';
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->airlineIds = $_SESSION['airlineIds'];
        $this->startDate = $_REQUEST['startDate'];
        $this->endDate = $_REQUEST['endDate'];
        $this->flightLegId = $_REQUEST['flightLegId'];
        $this->aircraftId = $_REQUEST['aircraftId'];
        $this->remark = $_REQUEST['remark'];
        $this->platform = $_REQUEST['platform'];
        $this->configType = $_REQUEST['configType'];
        $this->software = $_REQUEST['software'];
        $this->tailsignList = $_REQUEST['tailsignList'];
        $this->category = $_REQUEST['category'];
        
        $_SESSION['airlineId'] = $_REQUEST['airlineId'];
		$_SESSION['platform'] =  $_REQUEST['platform'];
		$_SESSION['configType'] =  $_REQUEST['configType'];		
		$_SESSION['tailsignList'] =  $_REQUEST['tailsignList'];
		$_SESSION['software'] =  $_REQUEST['software'];
		$_SESSION['startDate'] =  $_REQUEST['startDate'];
		$_SESSION['endDate'] =  $_REQUEST['endDate'];
		
		error_log('Session software'. $_SESSION['software']);
    }
    
    /**
     * Main method called to start the process
     */
    public function hadleRequest() {
        $this->getLRUWeight();
        
        if ($this->action == 'GET_FLIGHT_SCORE') {
            $this->getFlightScore();
        } else if ($this->action == 'GET_LRU_WEIGHT') {
            $this->loadLRUWeightData();
        } else if ($this->action == 'UPDATE_REMARKS') {
            $this->updateRemarks();
        }
    }
    
    /**
     * Calculate the flight score for an airline
     */
    public function getFlightScore() {
//         $query = "select fs.flight_date as flightDate, fs.flightleg_id as flightLegId, fs.total_cruise_time as flightDuration, ac.databaseName
//                 from $this->mainDB.flight_score fs, $this->mainDB.aircrafts ac, $this->mainDB.airlines al
//                 where al.id=$this->airlineId and al.id=ac.airlineId and ac.id=fs.ac_id";

        $query = "select fs.flight_date,fs.flightleg_id,fs.total_cruise_time,fs.DSU_fault_duration,fs.LAIC_fault_duration,".
                "fs.ICMT_fault_duration,fs.ADBG_fault_duration,fs.QSEB_fault_duration,fs.SDB_fault_duration,fs.SVDU_fault_duration,".
                "fs.TPMU_fault_duration,fs.TPCU_fault_duration,IFNULL(fs.remarks,'') as remarks,fs.impacted_lrus,". 
                "ac.id as aircraftId, ac.databaseName,ac.tailsign,ac.platform,ac.Ac_Configuration as config,ac.software,c.DSU_count,c.LAIC_count,".
                "c.ICMT_count,c.ADBG_count,c.QSEB_count,c.SDB_count,c.SVDU_count,c.TPMU_count,c.TPCU_count ".
                "from $this->mainDB.flight_score fs, $this->mainDB.aircrafts ac, $this->mainDB.airlines al, $this->mainDB.Configuration c where ";
        
        if($this->IsNullOrEmptyString($this->airlineId)) {
//             error_log("Airline Ids: $this->airlineId");
            if($this->airlineIds > 0) {
                $airlineIds = rtrim(implode(",", $this->airlineIds), ",");
                $airlineIds = rtrim($airlineIds, ",");
                if($airlineIds > 0) {
                    $query .= "al.id in($airlineIds) and ";
                }
            }
        } else {
            $query .= "al.id=$this->airlineId and ";
        }
        
        $query .= "al.id=ac.airlineId and ac.id=fs.ac_id and c.airlineID=al.id and c.ConfigName=ac.Ac_Configuration ";
        
        if(!$this->IsNullOrEmptyString($this->platform)) {
            $query .= " and ac.platform='". $this->platform ."'";
        }
        
        if(!$this->IsNullOrEmptyString($this->configType)) {
            $query .= " and ac.Ac_Configuration='". $this->configType ."'";
        }
        
        if(!empty($this->software)) {
//             $query .= "and ac.software='". $this->software ."'";
            $query .= " and ac.software in(";
            foreach ($this->software as $software) {
                $query .= "'" . $software . "',";
            }
            $query = rtrim($query, ",");
            $query .= ")";
        }
        
        if(!empty($this->tailsignList)) {
//             $tailsignList = explode(',', $this->tailsignList);
            $query .= " and ac.tailsign in(";
            foreach ($this->tailsignList as $tailsign) {
                $query .= "'" . $tailsign . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
        }
        
        if(!$this->IsNullOrEmptyString($this->startDate) and !$this->IsNullOrEmptyString($this->endDate)) {
            $query .=" AND (date(flight_date) BETWEEN CAST('$this->startDate' AS DATE) AND CAST('$this->endDate' AS DATE))";
        }
        
        $query .= " order by fs.flight_date, ac.tailsign";
        
        $result = mysqli_query($this->dbConnection, $query);

        error_log("getFlightScore Query: " . $query);
        
        $finalData = array();
        $noIssueArray = array();
        $warningArray = array();
        $criticalArray = array();
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data = array();
                $data['aircraftId'] = $row['aircraftId'];
                $data['flightDate'] = $row['flight_date'];
                $data['tailSign'] = $row['tailsign'];
                $data['platform'] = $row['platform'];
                $data['config'] = $row['config'];
                $data['software'] = $row['software'];
                $flightLegId = $row['flightleg_id'];
                $data['flightLegId'] = $flightLegId;
//                 if($row['total_cruise_time'] > 0) {
//                     $data['flightDuration'] = gmdate("H:i", $row['total_cruise_time']);
//                     $data['flightDuration'] = $this->secToHR($row['total_cruise_time']);
//                 }
//                 $data['flightDuration'] = $row['total_cruise_time'];
                $databaseName = $row['databaseName'];

//                 $cityPairQuery = "select concat(departureAirportCode, ' - ', arrivalAirportCode) as cityPair from $databaseName.sys_flight where idFlightLeg=".$data['flightLegId'];
                $cityPairQuery = "select concat(sf.departureAirportCode, ' - ', sf.arrivalAirportCode) as cityPair, ".
                                "TIME_FORMAT(TIMEDIFF(max(sfp.endTime),min(sfp.startTime)),'%k hours %i mins %s seconds') as cruise_time from ".
                                "$databaseName.sys_flight sf, $databaseName.sys_flightphase sfp where sf.idFlightLeg=".$data['flightLegId'].
                                " and sf.idFlightLeg=sfp.idFlightLeg and sfp.idFlightPhase in (4,5)";
                
                $cityPairResult = mysqli_query($this->dbConnection, $cityPairQuery);
                if ($cityPairResult) {
                    $cityPairRow = mysqli_fetch_assoc($cityPairResult);
                    $data['cityPair'] = $cityPairRow['cityPair'];
                    $data['flightDuration'] = $cityPairRow['cruise_time'];
                }
                
                // Calculate the score
                $score = $this->calculateScore($row);
                $data['flightScore'] = $score;
                $data['remarks'] = $row['remarks'];
                $data['impactedLRUs'] = $row['impacted_lrus'];
                
//                 $data['remarks'] = 'test';

//                 if(!$this->isNullOrEmptyString($this->category)) {
//                     if($score >= 0 and $score < 35) {
//                         $criticalArray[] = $data;
//                     } else if($score >= 35 and $score < 80) {
//                         $warningArray[] = $data;
//                     } else {
//                         $noIssueArray[] = $data;
//                     }
//                 }
                
                $finalData[] = $data;
            }
        }
        
//         if(!$this->isNullOrEmptyString($this->category)) {error_log("inside if category not empty");
//             if($this->category == 'critical') {
//                 $finalData = array_merge($criticalArray, $noIssueArray, $warningArray);
//                 echo $json_response = json_encode($finalData);
//             } else if($this->category == 'warning') {
//                 $finalData = array_merge($warningArray, $noIssueArray, $criticalArray);
//                 echo $json_response = json_encode($finalData);
//             } else if($this->category == 'none') {
//                 $finalData = array_merge($noIssueArray, $warningArray, $criticalArray);
//                 echo $json_response = json_encode($finalData);
//             }
//         } else {error_log("inside if category null");
//             echo $json_response = json_encode($finalData);
//         }

            echo $json_response = json_encode($finalData);
    }
    
    public function loadLRUWeightData() {
        $finalData = array();
        
        foreach ($this->lruWeight as $name => $weight) {
            $finalData[] = array('lruName'=>$name, 'lruWeight'=>$weight);
        }
        
        echo $json_response = json_encode($finalData);
    }
    
    public function updateRemarks() {
        $query = "update $this->mainDB.flight_score set remarks='$this->remark' where flightleg_id=$this->flightLegId and ac_id=$this->aircraftId";
        echo '$query';
        $result = mysqli_query($this->dbConnection, $query);
        
        mysqli_commit($this->dbConnection);        
    }
    
    /**
     * Retrieves the LRU weight details
     * 
     * @return array
     */
    private function getLRUWeight() {
        $query = "select * from $this->mainDB.lru_weight";
        
        $result = mysqli_query($this->dbConnection, $query);
       
        if($result) {
            while($row = mysqli_fetch_assoc($result)) {
                $this->lruWeight[$row['lru_name']] = $row['weight'];
            }
        }
    }
    
    private function calculateScore($row) {
        $score = 0;
        $cruiseTime = (float)$row['total_cruise_time'];
        $weightedMean = 0;
        
//         error_log( print_r($row, TRUE) );
        
        $totalFaultLRUWeight = ($row['DSU_fault_duration'] * $this->lruWeight['DSU']) +
        ($row['LAIC_fault_duration'] * $this->lruWeight['LAIC']) +
        ($row['ICMT_fault_duration'] * $this->lruWeight['ICMT']) +
        ($row['ADBG_fault_duration'] * $this->lruWeight['ADBG']) +
        ($row['QSEB_fault_duration'] * $this->lruWeight['QSEB']) +
        ($row['SDB_fault_duration'] * $this->lruWeight['SDB']) +
        ($row['SVDU_fault_duration'] * $this->lruWeight['SVDU']) +
        ($row['TPMU_fault_duration'] * $this->lruWeight['TPMU']) +
        ($row['TPCU_fault_duration'] * $this->lruWeight['TPCU']);
        
//         error_log('Total Fault Weight: '. $totalFaultLRUWeight);
        
        $totalFlyingLRUWeight = ($row['DSU_count'] * $this->lruWeight['DSU']) +
        ($row['LAIC_count'] * $this->lruWeight['LAIC']) +
        ($row['ICMT_count'] * $this->lruWeight['ICMT']) +
        ($row['ADBG_count'] * $this->lruWeight['ADBG']) +
        ($row['QSEB_count'] * $this->lruWeight['QSEB']) +
        ($row['SDB_count'] * $this->lruWeight['SDB']) +
        ($row['SVDU_count'] * $this->lruWeight['SVDU']) +
        ($row['TPMU_count'] * $this->lruWeight['TPMU']) +
        ($row['TPCU_count'] * $this->lruWeight['TPCU']);
        
//         error_log('Total LRU Weight: '. $totalFlyingLRUWeight);
        
        if($totalFaultLRUWeight > 0 && $totalFlyingLRUWeight > 0) {
            $weightedMean = $totalFaultLRUWeight / $totalFlyingLRUWeight;
        }
        //         error_log('Weighted mean: '. $weightedMean);
        //         error_log('Cruise Time: '. $cruiseTime);
        
        if($weightedMean > 0 && $cruiseTime > 0) {
            $score = (1-($weightedMean / $cruiseTime))*100;
        }
        
        if($score > 0)
            $score = number_format($score, 2, ".", "");
        
//         echo 'Score: ' . $score . "<br/>";
        
        return $score;
    }
    
    /**
     * Checks if the input string is empty or null
     */
    function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }

    function secToHR($seconds) {
        $timeString = "";
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        
        if($hours > 0) $timeString.="$hours hours ";
        if($minutes > 0) $timeString.="$minutes mins";
        return $timeString;
    }
    
    /**
     * Prints the message in a line
     */
    function echoline($msg) {
        echo "<br/>$msg<br/>";
    }
}

$flightScoring = new FlightScoring($dbConnection, $mainDB);
$flightScoring->hadleRequest();

?>