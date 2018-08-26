<?php
/**
 * Fetches the Host Name and Serial Number data from the individual databases and 
 * updates the serialnumber_info table along with Tail Sign and Airline Id.
 * 
 * Before we run this file, we need to update the database related details 
 * specific to the environment we urn.
 * 
 * @author 
 */
class UpdateTailSignMaster {

    public $dbConnection;
    public $username = 'root';
//     public $password = 'root00';
//     public $password = "IsE@BiTe379";
//     public $password = 'root';
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

        $query = "select tailsign, airlineId, databaseName from $this->mainDB.aircrafts order by airlineId, tailsign";
        $result = mysqli_query($this->dbConnection, $query);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign = $row['tailsign'];
                $airlineId = $row['airlineId'];
                $databaseName = $row['databaseName'];
                $hostName = "";
                $serialNumber = "";
                $lastUpdate = "";
                $prevHostName = "";
                $prevSerialNo = "";
                $prevLastUpdate = "";
                
                $bitlruQuery = "SELECT t1.hostName, t1.serialNumber, t1.lastUpdate FROM $databaseName.bit_lru t1 WHERE t1.lastUpdate = (SELECT MAX(t2.lastUpdate) FROM $databaseName.bit_lru t2 WHERE t2.hostName = t1.hostName) and t1.hostName  <>''";

                $bitlruResult = mysqli_query($this->dbConnection, $bitlruQuery);
                if ($bitlruResult) {
                    $this->echoline("Updating serialnumber for $tailsign");
                    
                    $count = 0;
                    while ($bitlruRow = mysqli_fetch_array($bitlruResult)) {
                        $hostName = $bitlruRow['hostName'];
                        $serialNumber = $bitlruRow['serialNumber'];
                        $lastUpdate = $bitlruRow['lastUpdate'];

                        $queryInsert = "insert into $this->mainDB.serialnumber_info (host_name, serial_number, tailsign, airline_id, last_updated_time) values ('$hostName', '$serialNumber', '$tailsign', $airlineId, '$lastUpdate')";
                        $resultInsert = mysqli_query($this->dbConnection,$queryInsert);

                        if(!$resultInsert) {
                            $this->echoline("Error inserting tailsing for query: $queryInsert<br/>Error: " . mysqli_error($this->dbConnection));
                        }
                    }
                } else {
                    mysqli_error($this->dbConnection);
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

$updateTailSignMaster = new UpdateTailSignMaster();
$updateTailSignMaster->main();
?>