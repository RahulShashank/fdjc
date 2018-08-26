<?php
// Start the session
session_start ();
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";

class HardwareRevisionsModsDAO {
    
    public $dbConnection;
    public $action = '';
    public $mainDB = '';
    public $tailsign = '';
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->platform = $_REQUEST['platform'];
        $this->configType = $_REQUEST['configType'];
        $this->tailsign = $_REQUEST['tailsign'];
    }
    
    /**
     * Main method called to handle the request and start the process
     */
    public function hadleRequest() {
        if ($this->action == 'GET_CONFIG_DETAILS') {
            $this->getConfigDetails();
        }
    }

    public function getConfigDetails() {
        error_log("HardwareRevisionsModsDAO->getConfigDetails() entered");
        $lrus = array();
        
        $dbNameArray = array();
        
        if(!empty($this->tailsign)) {
            $query = "SELECT a.tailsign, a.databaseName FROM $this->mainDB.aircrafts a WHERE 1=1" ;
            
            if(is_array($this->tailsign)) {
                $query .= " and tailsign in(";
                foreach ($this->tailsign as $tailsign) {
                    $query .= "'" . $tailsign . "',";
                }
                $query = rtrim($query, ",") . ")";
            } else if(!$this->isNullOrEmptyString($this->tailsign)) {
                $query .= " and tailsign='$this->tailsign'";
            }
            
            $result = mysqli_query($this->dbConnection,$query);
            
            if($result && mysqli_num_rows($result) > 0 ) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $dbNameArray[$row['tailsign']] = $row['databaseName'];
                }
            }
        } else {
            // Retrieve data for all the tailsign of the airline, platform, configuration
            $query = "select tailsign, databaseName FROM $this->mainDB.aircrafts where 1=1";
            
            if(!$this->isNullOrEmptyString($this->airlineId)) {
                $query .= " and airlineID=$this->airlineId";
            }
            
            if(is_array($this->platform)) {
                $query .= " and platform in(";
                foreach ($this->platform as $platform) {
                    $query .= "'" . $platform . "',";
                }
                $query = rtrim($query, ",") . ")";
            } else if(!$this->isNullOrEmptyString($this->platform)) {
                $query .= " and platform='$this->platform'";
            }
            
            if(is_array($this->configType)) {
                $query .= " and Ac_Configuration in(";
                foreach ($this->configType as $configType) {
                    $query .= "'" . $configType . "',";
                }
                $query = rtrim($query, ",") . ")";
            } else if(!$this->isNullOrEmptyString($this->configType)) {
                $query .= " and Ac_Configuration='$this->configType'";
            }
            
            $query .= " order by tailsign";
            
            $result = mysqli_query($this->dbConnection, $query);
            
            if($result && mysqli_num_rows($result) > 0 ) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $dbNameArray[$row['tailsign']] = $row['databaseName'];
                }
            }
        }
        
        foreach ($dbNameArray as $tailsign => $dbName) {
            if(mysqli_select_db($this->dbConnection, $dbName)) {
                $query = "SELECT a.hostName, hwPartNumber, serialNumber, model, totalPowerOnTime, revision, swConf
                		FROM $dbName.BIT_lru a,
                			(
                				SELECT hostName, GROUP_CONCAT(swConfInt SEPARATOR '<br/>') AS swConf
                				FROM (
                					SELECT hostName, lastUpdate, CONCAT_WS(' / ',description,partNumber) as swConfInt
                					FROM $dbName.BIT_confSw z
                					WHERE z.lastUpdate IN (
                						SELECT MAX(y.lastUpdate) AS max
                						FROM $dbName.BIT_confSw y
                						WHERE z.hostName = y.hostName
                						AND z.description = y.description
                						AND z.partNumber = y.partNumber
                						AND z.hostName<>''
                					)
                					AND z.hostName<>''
                					GROUP BY description, hostName
                				) as t
                				GROUP BY hostName
                			) AS b
                		WHERE a.hostName = b.hostName
                		AND b.hostName <> ''
                		AND a.lastUpdate = (
                			SELECT MAX(b.lastUpdate) AS max
                			FROM $dbName.BIT_lru b
                			WHERE a.hostName = b.hostName
                			AND a.hostName <>''
                		)
                		ORDER BY CASE
                			         WHEN a.hostName LIKE 'DSU%' THEN 1
                			         WHEN a.hostName LIKE 'ADBG%' THEN 2
                			         WHEN a.hostName LIKE 'LAIC%' THEN 3
                	                 ELSE 4
                                 END,
                                 LENGTH(a.hostName)";
                
                $result = mysqli_query($this->dbConnection,$query);
                
                while ($result && $row = mysqli_fetch_assoc($result)) {
                    $model = $row['model'];
                    $row['model'] = getModval($model);
                    $row['tailsign'] = $tailsign;
                    $lrus[] = $row;
                }
            }
        }
        
        # JSON-encode the response
        echo $json_response = json_encode($lrus);
    }
    
    public function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }
    
}

$hardwareRevisionsModsDAO = new HardwareRevisionsModsDAO($dbConnection, $mainDB);
$hardwareRevisionsModsDAO->hadleRequest();
?>