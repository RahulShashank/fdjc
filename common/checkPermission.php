<?php
function checkAirlinePermission($airlineIds, $airlineId) {
	$permission = false;
	error_log("Airline Id: " . $airlineId);
	
	foreach ($airlineIds as $id) {
	    error_log("inside for each of checkAirlinePermission");
		if($id == - 1 || $id == $airlineId) {
		    error_log("inside if of for each in checkAirlinePermission");
			$permission = true;
			break;
		}
	}

	if (!$permission) {
	    error_log("going to be redrected to home page");
		header( 'Location: ../index.php' ) ;
	}
}

function checkAircraftPermission($dbConnection, $aircraftId) {
	$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
	
	if($airlineIds == -1) {
		// check if aircraft exists
		$query = "SELECT id FROM aircrafts WHERE id = $aircraftId";
			$result = mysqli_query($dbConnection, $query );

			if ($result && mysqli_num_rows ( $result ) > 0) {
				$permission = true;
			}
	} else {
		if($aircraftId > 0) {
			$query = "SELECT id FROM aircrafts WHERE id = $aircraftId AND airlineId IN ($airlineIds)";
			$result = mysqli_query($dbConnection, $query );

			if ($result && mysqli_num_rows ( $result ) > 0) {
				$permission = true;
			}
		}
	}
	
	if (!$permission) {
		header( 'Location: ../login.php' ) ;
	}
}
?>