<?php
session_start();
error_log('inside GetLRUCount.php');
require_once "../database/connecti_database.php";
require_once ('../admin/checkAdminPermission.php');

class LRUCountDAO {
    
    public $dbConnection;
    public $mainDB;
    public $action = '';
    public $id = '';
    public $airlineId = '';
    public $airlineName = '';
    public $platform = '';
    public $configName = '';
    public $dsuCount = '';
    public $laicCount = '';
    public $icmtCount = '';
    public $adbgCount = '';
    public $qsebCount = '';
    public $sdbCount = '';
    public $svduCount = '';
    public $tpmuCount = '';
    public $tpcuCount = '';
    
    
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->id = $_REQUEST['id'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->airlineName = $_REQUEST['airlineName'];
        $this->platform = $_REQUEST['platform'];
        $this->configName = $_REQUEST['configType'];
        $this->dsuCount = $_REQUEST['dsuCount'];
        $this->laicCount = $_REQUEST['laicCount'];
        $this->icmtCount = $_REQUEST['icmtCount'];
        $this->adbgCount = $_REQUEST['adbgCount'];
        $this->qsebCount = $_REQUEST['qsebCount'];
        $this->sdbCount = $_REQUEST['sdbCount'];
        $this->svduCount = $_REQUEST['svduCount'];
        $this->tpmuCount = $_REQUEST['tpmuCount'];
        $this->tpcuCount = $_REQUEST['tpcuCount'];
    }
    
    public function handleRequest() {
        if ($this->action == 'GET_LRU_COUNT') {
            $this->getLRUCount();
        } elseif ($this->action == 'ADD_LRU_COUNT') {
            $this->addLRUCount();
        } elseif ($this->action == 'UPDATE_LRU_COUNT') {
            $this->updateLRUCount();
        } elseif ($this->action == 'GET_AIRLINES') {
            $this->getAirlines();
        }
    }
    
    public function getLRUCount() {
        error_log('LRUCountDAO->getLRUCount() entered');
        $query = "select c.id, c.airlineId, a.name as airlineName, c.platform, c.configName, c.DSU_count as dsuCount, c.LAIC_count as laicCount, c.ICMT_count as icmtCount, c.ADBG_count as adbgCount, c.QSEB_count as qsebCount, c.SDB_count as sdbCount, c.SVDU_count as svduCount, c.TPMU_count as tpmuCount, c.TPCU_count as tpcuCount from Configuration c, airlines a where c.airlineId=a.id order by a.name, c.platform, c.configName";
        $result = mysqli_query($this->dbConnection, $query);
        
        $lruCount = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $lruCount[] = $row;
        }
        
        echo json_encode($lruCount);
    }
    
    public function addLRUCount() {
        error_log('LRUCountDAO->addLRUCount() entered');
        
        // check if the combination already exists
        $selectQuery = "select count(1) as count from configuration where airlineId=$this->airlineId and platform='$this->platform' and configName='$this->configName'";
        $selectResult = mysqli_query($this->dbConnection, $selectQuery);
        
        $row = mysqli_fetch_assoc($selectResult);
        if($row['count'] > 0) {
            echo "EXISTS";
        } else {
            $query = "INSERT INTO Configuration(airlineID,Platform,ConfigName,DSU_count,LAIC_count,ICMT_count,ADBG_count,QSEB_count,SDB_count,SVDU_count,TPMU_count,TPCU_count)
                        VALUES($this->airlineId,'$this->platform','$this->configName',$this->dsuCount,$this->laicCount,$this->icmtCount,$this->adbgCount,$this->qsebCount,$this->sdbCount,$this->svduCount,$this->tpmuCount,$this->tpcuCount)";
            error_log("Add LRU Count Query: $query");
            
            $result = mysqli_query($this->dbConnection, $query);
            mysqli_commit($this->dbConnection);
            
            if($result > 0) {
                echo "SUCCESS";
            } else {
                echo "ERROR";
            }
        }
    }
    
    public function updateLRUCount() {
        error_log('LRUCountDAO->updateLRUCount() entered');
        
        $query = "update $this->mainDB.configuration set DSU_count=$this->dsuCount, LAIC_count=$this->laicCount, ICMT_count=$this->icmtCount, ADBG_count=$this->adbgCount, QSEB_count=$this->qsebCount, SDB_count=$this->sdbCount, SVDU_count=$this->svduCount, TPMU_count=$this->tpmuCount, TPCU_count=$this->tpcuCount where id=$this->id";
        error_log("Update LRU Count Query: $query");
        
        $result = mysqli_query($this->dbConnection, $query);
        mysqli_commit($this->dbConnection);
        
        echo $result;
    }
    
    public function getAirlines() {
        $query = "SELECT id,name FROM $this->mainDB.airlines ORDER BY name";
        $result = mysqli_query($this->dbConnection, $query);
        
        $airlines = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $airlines[] = $row;
        }
        
        echo json_encode($airlines);
    }
}

$lruCountDAO = new LRUCountDAO($dbConnection, $mainDB);
$lruCountDAO->handleRequest();
?>