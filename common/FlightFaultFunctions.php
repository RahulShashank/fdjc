<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/seatAnalyticsData.php";

class FlightFaultFunctions {
    var $dbConnection;
    var $mainDB;
    var $aircraftDB;
    var $flightLegId;
    var $cruiseStartTime;
    var $cruiseEndTime;
    var $aircraftId;
    var $cruiseTime;
    var $dateFlightLeg;
    
    public function init($dbConnection, $mainDB, $aircraftDB, $flightLegId, $aircraftId, $dateFlightLeg, $cruiseTime) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->aircraftDB=$aircraftDB;
        $this->flightLegId=$flightLegId;
        $this->aircraftId=$aircraftId;
        $this->dateFlightLeg=$dateFlightLeg;
        $this->cruiseTime=$cruiseTime;
    }
    
    public function processFlightFaultCountForFlightLeg() {
        $this->cruiseStartTime = getStartTime($this->dbConnection, $this->flightLegId, $this->aircraftDB);
        $this->cruiseEndTime = getEndTime($this->dbConnection, $this->flightLegId, $this->aircraftDB);
        
        $lruArray = array('DSU','LAIC','ICMT','ADBG','QSEB','SDB','SVDU','TPMU','TPCU');
        $faultCountArray = array();
        
        foreach ($lruArray as $lruName) {
            $faultCount = 0;
            
            $cruiseStartDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $this->cruiseStartTime);
            $cruiseEndDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $this->cruiseEndTime);
            
            $query = "select distinct hostName, detectionTime, count(distinct hostName, detectionTime) as count,
                    clearingTime, monitorState from $this->aircraftDB.bit_fault where idFlightLeg=$this->flightLegId
                    and faultCode=400 and hostName like '$lruName%' group by hostName";
            
            $result = mysqli_query($this->dbConnection, $query);
            
            if($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $hostName = $row['hostName'];
                    $count = $row['count'];
                    $detectionTime = $row['detectionTime'];
                    $clearingTime = $row['clearingTime'];
                    $monitorState = $row['monitorState'];
                    $detectionDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $detectionTime);
                    $clearingDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $clearingTime);
                    
                    if ($count == 1) {
                        if ($monitorState == 3 or
                             ($monitorState == 1 and
                             (($detectionDateTime >= $cruiseStartDateTime and $detectionDateTime <= $cruiseEndDateTime) or
                             ($clearingDateTime >= $cruiseStartDateTime and $clearingDateTime <= $cruiseEndDateTime)))) {
                                 $faultCount++;
                        }
                    } else {
                        $queryForLRU = "select hostName, detectionTime, clearingTime, monitorState
                                        from $this->aircraftDB.bit_fault 
                                        where idFlightLeg=$this->flightLegId and faultCode=400 and hostName='$hostName' 
                                        group by hostName, detectionTime
                                        order by detectionTime";
                        $resultForLRU = mysqli_query($this->dbConnection, $queryForLRU);
                        
                        if($resultForLRU) {
                            $monitorStateSet = false;
                            
                            while ($rowForLRU = mysqli_fetch_assoc($resultForLRU)) {
                                $detectionTimeLRU = $row['detectionTime'];
                                $clearingTimeLRU = $row['clearingTime'];
                                $monitorStateLRU = $row['monitorState'];
                                $detectionDateTimeLRU = DateTime::createFromFormat('Y-m-d H:i:s', $detectionTimeLRU);
                                $clearingDateTimeLRU = DateTime::createFromFormat('Y-m-d H:i:s', $clearingTimeLRU);
                                
                                if($monitorState == 1) {
                                    if (($detectionDateTimeLRU >= $cruiseStartDateTime and $detectionDateTimeLRU <= $cruiseEndDateTime) or
                                         ($clearingDateTimeLRU >= $cruiseStartDateTime and $clearingDateTimeLRU <= $cruiseEndDateTime)) {
                                        $faultCount ++;
                                    } else if ($detectionDateTimeLRU < $cruiseStartDateTime and $monitorStateSet) {
                                        $monitorStateSet = false;
                                        $faultCount--;
                                    }
                                } else if($monitorState == 3) {
                                    if($detectionDateTimeLRU >= $cruiseStartDateTime and $detectionDateTimeLRU <= $cruiseEndDateTime) {
                                        $faultCount++;
                                    } else if($detectionDateTimeLRU < $cruiseStartDateTime) {
                                        $monitorStateSet = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $faultCountArray[$lruName] = $faultCount;
        }
        
        // insert the fault count to flight_score table
        $faultInsertQuery = "INSERT INTO $this->mainDB.flight_score(ac_id,flight_date,flightleg_id,total_cruise_time,DSU_fault_count,LAIC_fault_count,ICMT_fault_count,ADBG_fault_count,QSEB_fault_count,SDB_fault_count,SVDU_fault_count,TPMU_fault_count,TPCU_fault_count) VALUES($this->aircraftId,'$this->dateFlightLeg',$this->flightLegId,$this->cruiseTime,".
        $faultCountArray['DSU'] . "," . $faultCountArray['LAIC'] . "," . $faultCountArray['ICMT'] . "," . $faultCountArray['ADBG'] . "," . $faultCountArray['QSEB'] . "," . $faultCountArray['SDB'] . "," . $faultCountArray['SVDU'] . "," . $faultCountArray['TPMU'] . "," . $faultCountArray['TPCU'] . ")";
        
        $faultInsertResult = mysqli_query($this->dbConnection, $faultInsertQuery);
        mysqli_commit($this->dbConnection);
    }
    
    function echoLine($msg) {
        echo $msg . "<br/>";
    }
}
?>