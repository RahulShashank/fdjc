<?php

require_once "../database/connecti_database.php";
require_once("../common/validateUser.php");

$approvedRoles = [$roles["admin"], $roles["engineer"]];
$auth->checkPermission($hash, $approvedRoles);

// $tsArray = unserialize($_REQUEST['tsArray']);
// $tsArray = array();

// $values = $_POST;
// $values=array_filter($values);
error_log(" ================= inside UpdateTailSignMaster.php =======================");

foreach ($_POST as $key => $value) {
    error_log("Tail Sign to be udpated $key = $value");
    if(is_array($value)){ //If $value is an array, print it as well!
        foreach ($value as $k => $v){
            echo "$k => $v";
        }
        
        error_log(print_r($value));
    }
}

$tsArray = $_POST["ts"];

error_log("TS : ". print_r($tsArray));
// foreach ($tsArray as $ts) {
//     error_log("TS $ts");
// }


// printArray($_POST);

// $tsArray = $_REQUEST['tsArray'];

// foreach ($values as $v) {
//     error_log("Tail Sign to be udpated ############ $v");
// }


/**
 * Fetches the Host Name and Serial Number data from the individual databases and 
 * updates the tailsign_info table along with Tail Sign and Airline Id.
 * 
 * Before we run this file, we need to update the database related details 
 * specific to the environment we urn.
 * 
 * @author 
 */
class UpdateTailSignMaster {

    public $dbConnection;
    public $username = 'root';
    public $password = 'root';
    public $hostname = 'localhost';
    public $mainDB = 'banalytics';

    /**
    * Constructor which creates the database connectivity
    */
    public function __construct() {
        // connection to mysql Server
        $this->dbConnection = mysqli_connect($this->hostname, $this->username, $this->password, $this->mainDB) or die("Unable to connect to MySQL");;
        
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        // Set autocommit to off
        mysqli_autocommit($this->dbConnection, FALSE);

        $this->echoline("Connection to the database $this->mainDB is created");
    }// end __consruct

    /**
    * Main method called to start the process
    */
    public function main() {
        $this->echoline("Update process started");
        $this->processData();
        $this->echoline("Update process completed");
    }// end main

    /**
    * Connects to different databases and update the master table
    */
    public function processData() {
        $tailsign = "";
        $airlineId = 0;
        $databaseName = "";

        $query = "select tailsign, airlineId, databaseName from $this->mainDB.aircrafts order by airlineId;";
        $result = mysqli_query($this->dbConnection, $query);

        if ($result) {
            while ($row = mysqli_fetch_array($result)) {
                $row = mysqli_fetch_array($result);
                $tailsign = $row['tailsign'];
                $airlineId = $row['airlineId'];
                $databaseName = $row['databaseName'];
                $hostName = "";
                $serialNumber = "";
                $lastUpdate = "";
                $prevHostName = "";
                $prevSerialNo = "";
                $prevLastUpdate = "";

                // $bitlruQuery = "select hostName, serialNumber, lastUpdate from $databaseName.bit_lru order by hostName, lastUpdate desc";
                // $bitlruQuery = "select * from ( select hostName,serialNumber, max(lastUpdate) from $databaseName.bit_lru a, ( select x.hostName as lruName from  (select hostName, count(hostName) as count from $databaseName.bit_lru group by hostName) x where x.count>1 ) b where a.hostName=b.lruName and a.serialNumber <> ''  group by a.hostName order by a.hostName) t1 union  select * from (select hostName,serialNumber, lastUpdate from $databaseName.bit_lru a, ( select x.hostName as lruName from  (select hostName, count(hostName) as count from $databaseName.bit_lru group by hostName) x where x.count=1 ) b where a.hostName=b.lruName) t2 order by hostName";
                $bitlruQuery = "select * from ( select a.hostName, a.serialNumber, max(a.lastUpdate) as lastUpdate from $databaseName.bit_lru a, (select hostName, count(hostName) as count from $databaseName.bit_lru group by hostName) b where b.count>1 and a.hostName=b.hostName and a.serialNumber <> ''  group by a.hostName order by a.hostName) t1 union  select * from (select a.hostName,a.serialNumber, a.lastUpdate from $databaseName.bit_lru a, (select hostName, count(hostName) as count from $databaseName.bit_lru group by hostName) b where b.count=1 and a.hostName=b.hostName) t2  order by hostName";

                $bitlruResult = mysqli_query($this->dbConnection, $bitlruQuery);

                if ($bitlruResult) {
                    $count = 0;
                    while ($bitlruRow = mysqli_fetch_array($bitlruResult)) {
                        $hostName = $bitlruRow['hostName'];
                        $serialNumber = $bitlruRow['serialNumber'];
                        $lastUpdate = $bitlruRow['lastUpdate'];

                        $queryInsert = "insert into $this->mainDB.tailsign_info (host_name, serial_number, tailsign, airline_id, last_updated_time) values ('$hostName', '$serialNumber', '$tailsign', $airlineId, '$lastUpdate')";
                        $resultInsert = mysqli_query($this->dbConnection,$queryInsert);

                        if(!$resultInsert) {
                            $this->echoline("Error inserting tailsing for query: $queryInsert<br/>Error: " . mysqli_error($this->dbConnection));
                        }
                    }
                }
            }
        }

        // Commit the transaction
        mysqli_commit($this->dbConnection);

        // Close DB connection
        mysqli_close($this->dbConnection);        
    }

    /**
    * Checks if the input string is empty or null
    */
    function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }

    /**
    * Prints the message in a line
    */
    function echoline($msg) {
        echo "<br/>$msg<br/>";
    }

}

function printArray($array){
    foreach ($array as $key => $value){
        if(is_array($value)){ //If $value is an array, print it as well!
            printArray($value);
        }
        echo "$key => $value";
    }
}

// $updateTailSignMaster = new UpdateTailSignMaster();
// $updateTailSignMaster->main();
?>