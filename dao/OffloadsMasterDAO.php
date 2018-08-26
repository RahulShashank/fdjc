<?php
require_once "../database/connecti_database.php";

class OffloadsMasterDAO {
    public $dbConnection;
    public $mainDB = '';
    
    public function __construct() {
        $this->dbConnection = $GLOBALS['dbConnection'];
        $this->mainDB = $GLOBALS['mainDB'];
    }

    /**
     * update the offloads_master table
     *
     * @param unknown $fileName
     * @param unknown $respArray
     */
    function update($airlineId, $fileName, $fileSize, $status, $tailsignInFile, $tailsignFound, $flightNumber, $depTime, $arrTime, $depAirport, $arrAirport,
        $offloadDate, $oppFound, $failureReason, $remarks, $flightLegIds, $source) {
        error_log("OffloadsMasterDAO => update() entered");
            
        $fileExists = self::isFileNameAvailable($airlineId, $fileName, $fileSize);
        if($fileExists) {
            return false;
        }
        
        $query = "INSERT INTO offloads_master(airlineId,fileName,fileSize,status,tailsignInFile,tailsignFound,flightNumber,depTime,arrTime,depAirport,arrAirport,offloadDate,oppFound,failureReason,remarks,flightLegIds,source) VALUES";
        $query .= "($airlineId,'$fileName',$fileSize,'$status','$tailsignInFile','$tailsign','$flightNumber','$depTime','$arrTime','$depAirport','$arrAirport','$offloadDate','$oppFound','$failureReason','$remarks','$flightLegIds','$source')";

        $result = mysqli_query($this->dbConnection, $query);
        if (! $result) {
            error_log("Error in update(): " . mysqli_error($this->dbConnection));
            return false;
        }
        
        if(!mysqli_commit($this->dbConnection)){
            error_log("Error in commiting update offloads_master");
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks if the file name with the given size is already available in offloads_master
     * 
     * @param unknown $airlineId
     * @param unknown $fileName
     * @param unknown $fileSize
     * @return boolean
     */
    public function isFileNameAvailable($airlineId, $fileName, $fileSize) {
        $query = "select fileSize from $this->mainDB.offloads_master where fileName = '$fileName' and airlineId=$airlineId";
        $result = mysqli_query($this->dbConnection,$query);
        
        if($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            
            if($fileSize == $row['fileSize']) {
                return true;
            }
        }
        
        return false;
    }
    
}
?>