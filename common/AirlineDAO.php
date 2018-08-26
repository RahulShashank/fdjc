<?php
require_once "../database/connecti_database.php";

class AirlineDAO {
    
    public $dbConnection;
    public $action = '';
    public $mainDB = '';
    public $airlineId = '';
    public $airlineIds = '';
    public $platform = '';
    public $configType = '';
    public $aircraftId = '';
    public $software = '';
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->airlineIds = $_REQUEST['airlineIds'];
        $this->platform = $_REQUEST['platform'];
        $this->configType = $_REQUEST['configType'];
        $this->software = $_REQUEST['software'];
        $this->aircraftId = $_REQUEST['aircraftId'];
        $this->tailsign = $_REQUEST['tailsign'];
    }
    
    /**
     * Main method called to start the process
     */
    public function hadleRequest() {
        if ($this->action == 'GET_AIRLINES') {
            $this->getAirlines();
        } else if ($this->action == 'GET_AIRLINES_BY_IDS') {
            $this->getAirlinesByIds();
        } elseif ($this->action == 'GET_PLATFORMS_FOR_AIRLINE') {
            $this->getPlatformsForAirline();
        } elseif ($this->action == 'GET_CONFIG_TYPE_FOR_AIRLINE_PLATFORM') {
            $this->getConfigTypesForAirlineAndPlatform();
        } elseif ($this->action == 'GET_SW_FOR_AIRLINE_PLATFORM_CNFG') {
            $this->getSoftwaresForAirlinePlatformConfig();
        } elseif ($this->action == 'GET_TS_FOR_AIRLINE_PLTFRM_CNFG_SW') {
            $this->getTailsignForAirlinePlatformConfSw();
        } elseif($this->action == 'GET_TS_AND_ID_FOR_AIRLINE_PLTFRM_CNFG_SW'){
        	$this->getTailsignAndIdForAirlinePlatformConfSw();
        } elseif($this->action == 'GET_TS_AND_ID_FOR_AIRLINE_PLTFRM_CNFG_SW_ACTIVE'){
        	$this->getTailsignsForAirlinePlatformConfSwActive();
        } elseif ($this->action == 'GET_AIRCRAFTID_FOR_TS') {
            $this->findAircraftIdByTailsign();
        } elseif ($this->action == 'GET_TS_FOR_PLATFORM_CONFIG'){
        	$this->getTailsignForAirlinePlatformConf();
        } elseif ($this->action == 'GET_PLATFORMS_FOR_AIRLINE_ARRAY'){        	
        	$this->getPlatformsForAirlineArray();        	
        } elseif ($this->action == 'GET_CONFIG_FOR_AIRLINE_ARRAY_PLATFORM'){        	
        	$this->getConfigTypesForAirlineArrayAndPlatform();        	
        } elseif ($this->action == 'GET_SOFTWARE_FOR_AIRLINE_ARRAY_PLATFORM_CONFIG'){        	
        	$this->getSoftwaresForAirlineArrayAndPlatformAndConfig();        	
        } elseif ($this->action == 'GET_TS_FOR_AIRLINEARRAY_PLTFRM_CNFG_SW') {
            $this->getTailsignForAirlineArrayPlatformConfSw();
        } else if ($this->action == 'GET_AIRLINES_BY_IDS_ISP') {
            $this->getAirlinesByIdsAndIsp();
        } elseif ($this->action == 'GET_TS_FOR_AIRLINEARRAY_AND_ISP') {
            $this->getTailsignForAirlineArray();
        } elseif ($this->action == 'GET_TS_ARRAY_FOR_AIRLINEARRAY_AND_ISP') {
            $this->getTailsignListForAirlineArray();
        } 
    }

    public function getAirlines() {
        $query = "SELECT id,name FROM $this->mainDB.airlines ORDER BY name";
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }  

    
    /**
     * Retrieves distinct platform for an airline
     */
    public function getPlatformsForAirline() {
        $query = "select distinct platform from aircrafts";
        
        if(!$this->isNullOrEmptyString($this->airlineId)) {
            $query .= " where airlineID=$this->airlineId";
        }
        $query .= " order by platform";
        $result = mysqli_query($this->dbConnection, $query);
        
        error_log("Platform Query: ".$query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
	/**
     * Retrieves distinct platform for an airline Array
     */
    public function getPlatformsForAirlineArray() {    	 
        $query = "select distinct platform from aircrafts";       
       error_log('AirlineId : '.$this->airlineId);
        if(is_array($this->airlineId)) {
            $query .= " where airlineID in(";
            foreach ($this->airlineId as $airline) {
                $query .=  $airline . ",";
            }
            $query = rtrim($query, ",") . ")";
        } 
        $query .= " order by platform";
        $result = mysqli_query($this->dbConnection, $query);
        
        error_log("Platform Query: ".$query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
    /**
     * Retrieves distinct Config Type for an airline and Platform
     */
    public function getConfigTypesForAirlineAndPlatform() {
        error_log('getConfigTypesForAirlineAndPlatform() entered');
        $query = "select distinct Ac_Configuration as configType from aircrafts where Ac_Configuration<>''";

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
        $query .= " order by Ac_Configuration";
        
        error_log("Config Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }

    /**
     * Retrieves the softwares for an aircraft, platform and configType combination.
     */
    public function getSoftwaresForAirlinePlatformConfig() {
        $query = "select distinct software from aircrafts where software<>''";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType'";
        
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
        $query .= " order by software";
        
        error_log("Software Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
    /**
     * Retrieves the tailsigns for an aircraft, platform, configuration and software combination.
     */
    public function getTailsignForAirlinePlatformConfSw() {
        $query = "select distinct tailsign from aircrafts where 1=1";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' and software='$this->software' order by tailsign";

        if(!$this->isNullOrEmptyString($this->airlineId)) {
            if (strpos($this->airlineId, ',') !== false) {
                $query .= " and airlineID IN ($this->airlineId)";
            } else if ($this->airlineId > 0) {
                $query .= " and airlineID=$this->airlineId";
            }
        }
        
        if(!$this->isNullOrEmptyString($this->platform)) {
            $query .= " and platform='$this->platform'";
        }
        
        if(!$this->isNullOrEmptyString($this->configType)) {
            $query .= " and Ac_Configuration='$this->configType'";
        }
        
        if(!empty($this->software)) {
            error_log("Software: " . $this->software);
            
//             $query .= " and software='$this->software'";
//             $softwareList = explode(',', $this->software);
            $query .= " and software in(";
            foreach ($this->software as $software) {
                $query .= "'" . $software . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
            
        }
        $query .= " order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
	/**
     * Retrieves distinct Config Type for an Airline Array and Platform
     */
    public function getConfigTypesForAirlineArrayAndPlatform() {
        error_log('getConfigTypesForAirlineAndPlatform() entered');
        $query = "select distinct Ac_Configuration as configType from aircrafts where Ac_Configuration<>''";

        if(is_array($this->airlineId)) {
            $query .= " AND airlineID in(";
            foreach ($this->airlineId as $airline) {
                $query .=  $airline . ",";
            }
            $query = rtrim($query, ",") . ")";
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
        $query .= " order by Ac_Configuration";
        
        error_log("Config Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
	/**
     * Retrieves the softwares for an Airline Array, platform and configType combination from SPNL Upload.
     */
    public function getSoftwaresForAirlineArrayAndPlatformAndConfig() {
        $query = "select distinct software_version from SPNL_upload where software_version<>''";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType'";
        
        if(is_array($this->airlineId)) {
            $query .= " and customer in (select acronym from airlines where id in (";
            foreach ($this->airlineId as $airline) {
                $query .=  $airline . ",";
            }
            $query = rtrim($query, ",") . "))";
        } 
        
        if(is_array($this->platform)) {
            $query .= " and platform in(";
            foreach ($this->platform as $platform) {
                $query .= "'" . $platform . "',";
            }
            $query = rtrim($query, ",") . ")";
        } 
        
        if(is_array($this->configType)) {
            $query .= " and aircraft_type in(";
            foreach ($this->configType as $configType) {
                $query .= "'" . $configType . "',";
            }
            $query = rtrim($query, ",") . ")";
        }
        
        $query .= " order by software_version";
        
        error_log("Software Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
	/**
     * Retrieves the tailsigns for an aircraft, platform, configuration and software combination.
     */
    public function getTailsignsForAirlinePlatformConfSwActive() {
        error_log(__CLASS__ . "->getTailsignsForAirlinePlatformConfSwActive() entered");
        
        $query = "select distinct tailsign from aircrafts where 1=1";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' and software='$this->software' order by tailsign";

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
        
        if(is_array($this->software)) {
            $query .= " and software in(";
            foreach ($this->software as $software) {
                $query .= "'" . $software . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
            
        } else if(!$this->isNullOrEmptyString($this->software)) {
			$query .= " and software='$this->software'";            
        }
        $query .= " order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
/**
     * Retrieves the tailsigns for an aircraft, platform, configuration and software combination.
     */
    public function getTailsignAndIdForAirlinePlatformConfSw() {
        $query = "select distinct tailsign,id from aircrafts where 1=1";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' and software='$this->software' order by tailsign";

        if(!$this->isNullOrEmptyString($this->airlineId)) {
            $query .= " and airlineID=$this->airlineId";
        }
        
        if(!$this->isNullOrEmptyString($this->platform)) {
            $query .= " and platform='$this->platform'";
        }
        
        if(!$this->isNullOrEmptyString($this->configType)) {
            $query .= " and Ac_Configuration='$this->configType'";
        }
        
        if(!empty($this->software)) {
            error_log("Software: " . $this->software);
            
//             $query .= " and software='$this->software'";
//             $softwareList = explode(',', $this->software);
            $query .= " and software in(";
            foreach ($this->software as $software) {
                $query .= "'" . $software . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
            
        }
        $query .= " order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
	/**
     * Retrieves the tailsigns for an Airline Array, platform, configuration and software combination.
     */
    public function getTailsignForAirlineArrayPlatformConfSw() {
        $query = "select distinct tailsign from aircrafts where 1=1";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' and software='$this->software' order by tailsign";

        if(is_array($this->airlineId)) {
            $query .= " and airlineID in(";
            foreach ($this->airlineId as $airline) {
                $query .=  $airline . ",";
            }
            $query = rtrim($query, ",") . ")";
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
        
    	if(is_array($this->software)) {
            $query .= " and software in(";
            foreach ($this->software as $software) {
                $query .= "'" . $software . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
            
        } else if(!$this->isNullOrEmptyString($this->software)) {
			$query .= " and software='$this->software'";            
        }
        
        $query .= " order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
    /**
     * Retrieves the tailsigns for an Airline Array.
     */
    public function getTailsignForAirlineArray() {
        $query = "select distinct tailsign from aircrafts where ";        
        
        if(!$this->isNullOrEmptyString($this->airlineId)) {
        	 $query .= " airlineID= $this->airlineId AND ";
        }        
        
        $query .= " isp <> '' and isp <> 'NONE' order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
    /**
     * Retrieves the tailsigns for an Airline Array.
     */
    public function getTailsignListForAirlineArray() {
        $query = "select distinct tailsign from aircrafts where ";        
        
        if($this->airlineId != '' && !empty($this->airlineId) ){
			//$query.=" AND tailsign IN ($tailsign)";
			$query .= "  airlineId IN (";
            foreach ($this->airlineId as $ts) {
                $query .=  $ts . ",";
            }
            $query = rtrim($query, ",");
            $query .= ") and";
		}
        
        $query .= " isp <> '' and isp <> 'NONE' order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
    /**
     * Retrieves all the airlines for an user 
     */
    public function getAirlinesByIds() {       
        $query = "SELECT id, name, acronym, status, lastStatusComputed FROM airlines";
        if(isset($this->airlineIds) && $this->airlineIds != -1) {
            $query .=  " WHERE id IN ($this->airlineIds)";
        }
        $query .= " order by name";
        
        $stmt = $this->dbConnection->prepare($query) ;
        $stmt->execute();
        $stmt->bind_result($id, $name, $acronym, $status, $lastStatusComputed);
        
        $airlines = array();
        $dateTimeThreshold = date_modify(new DateTime(), '-7 day');
        while ($stmt->fetch()) {
            $lastStatusComputedDateTime = new DateTime( $lastStatusComputed );
            if( $lastStatusComputedDateTime < $dateTimeThreshold ){
                $status=-1;
            }
            $airlines[] = array('id' => $id, 'name' => $name, 'acronym' => $acronym, 'status'=>$status, 'lastStatusComputed'=>$lastStatusComputed);
        }
        
        $stmt->close();
        
        # JSON-encode the response
        echo $json_response = json_encode($airlines);
    }
    
        
    /**
     * Retrieves all the airlines for an user 
     */
    public function getAirlinesByIdsAndIsp() {
        
        $query = "SELECT id, name, acronym, status, lastStatusComputed FROM airlines Where id in (select airlineId from aircrafts WHERE";
        if(isset($this->airlineIds) && $this->airlineIds != -1) {
            $query .=  " airlineId IN ($this->airlineIds) and ";
        }
        $query .= "  isp <> '' and isp <> 'NONE') order by name";
        error_log('Airline Query : '.$query);
        $stmt = $this->dbConnection->prepare($query) ;
        $stmt->execute();
        $stmt->bind_result($id, $name, $acronym, $status, $lastStatusComputed);
        
        $airlines = array();
        $dateTimeThreshold = date_modify(new DateTime(), '-7 day');
        while ($stmt->fetch()) {
            $lastStatusComputedDateTime = new DateTime( $lastStatusComputed );
            if( $lastStatusComputedDateTime < $dateTimeThreshold ){
                $status=-1;
            }
            $airlines[] = array('id' => $id, 'name' => $name, 'acronym' => $acronym, 'status'=>$status, 'lastStatusComputed'=>$lastStatusComputed);
        }
        
        $stmt->close();
        
        # JSON-encode the response
        echo $json_response = json_encode($airlines);
    }
    
/**
     * Retrieves the tailsigns for an aircraft, platform, configuration and software combination.
     */
    public function getTailsignForAirlinePlatformConf() {
        $query = "select distinct tailsign from aircrafts where 1=1";
        //airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' and software='$this->software' order by tailsign";

        if(!$this->isNullOrEmptyString($this->airlineId)) {
            if (strpos($this->airlineId, ',') !== false) {
                $query .= " and airlineID IN ($this->airlineId)";
            } else if ($this->airlineId > 0) {
                $query .= " and airlineID=$this->airlineId";
            }
        }
        
        if(!empty($this->platform)) {
            error_log("platform: " . $this->platform);
            
//             $query .= " and software='$this->software'";
//             $softwareList = explode(',', $this->software);
            $query .= " and platform in(";
            foreach ($this->platform as $platform) {
                $query .= "'" . $platform . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
            
        }
        
    	if(!empty($this->configType)) {
            error_log("configType: " . $this->configType);
            
//             $query .= " and software='$this->software'";
//             $softwareList = explode(',', $this->software);
            $query .= " and Ac_Configuration in(";
            foreach ($this->configType as $configType) {
                $query .= "'" . $configType . "',";
            }
            $query = rtrim($query, ",");
            
            $query .= ")";
            
        }
        
        $query .= " order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row;
            }
        }
        
        echo $json_response = json_encode($tailsign);
    }
    
    
    public function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }
  
    public function findAirlineByTailsign($tailsign) {
        error_log("inside findAirlineByTailsign()");
        
        $airline = array();
        //         $query = "SELECT * FROM $this->mainDB.aircrafts where upper(tailsign)=upper('$tailsign')";
        $query = "select al.* from banalytics.aircrafts ac, $this->mainDB.airlines al where ac.airlineId=al.id and upper(ac.tailsign) = upper('$tailsign')";error_log("Query: " . $query);
        $result = mysqli_query($this->dbConnection, $query);
        if($row = mysqli_fetch_assoc($result)) {
            $airline = $row;
        }
        
        return $airline;
    }
    
	public function findAircraftIdByTailsign() {
        error_log("inside findAircraftIdByTailsign()");        
        $query = "select id from banalytics.aircrafts where tailsign='$this->tailsign'";
        $result = mysqli_query($this->dbConnection, $query);
        if($row = mysqli_fetch_assoc($result)) {
            $aircraftId = $row;
        }
        
        echo $json_response = json_encode($aircraftId);
    }
    
}

$airlineDAO = new AirlineDAO($dbConnection, $mainDB);
$airlineDAO->hadleRequest();

?>