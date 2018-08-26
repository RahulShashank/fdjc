<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../admin/checkAdminPermission.php');

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );
//$myfile = fopen("manoj.txt", "w") or die("Unable to open file!");
if($request['action'] == 'add')
{
	$entryLevel = $request ['entryLevel'];
	$lruNumber = $request ['lruNumber'];
	$title = $request ['title'];
	$description = $request ['description'];
	$sb = $request ['sb'];
	$mod = $request ['mod'];
	$rev = $request ['rev'];
	$eoNumber = $request ['eoNumber'];
	$Mandatory = $request ['Mandatory'];
	$datef = $request ['datef'];
	
	
	$findme   = ' ';
	$pos = strpos($lruNumber, $findme);
	$lruNumber = substr($lruNumber, 0, $pos);
	
	//	Status Return Format
	$status = array('state' => 0,
					'message' => "Successfully Added LRU details"
					);
					
	//echo json_encode($status);exit();

	// Server Side Validation of User Input.
	if(!preg_match('/^[A-Za-z0-9-]+$/',$lruNumber)){
		$status['state'] = -1;
		$status['message'] = "Invalid LRU number";
		echo json_encode($status);
		//fwrite($myfile, "Invalid LRU number");
		exit ();
		/*
	}else if(!preg_match('/^[A-Za-z0-9 .,-]+$/',$description)){
		$status['state'] = -1;
		$status['message'] = "Invalid description";
		echo json_encode($status);
		//fwrite($myfile, "Invalid description");
		exit ();
		
	}elseif(!preg_match('/^[A-Za-z0-9-]+$/',$sb)){
		$status['state'] = -1;
		$status['message'] = "Invalid SB";
		echo json_encode($status);
		//fwrite($myfile, "Invalid SB");
		exit ();
		
	}elseif(!preg_match('/^[\d]+(,[\d]+)*$/',$mod)){
		$status['state'] = -1;
		$status['message'] = "Invalid mod value";
		echo json_encode($status);
		//fwrite($myfile, "Invalid mod value");
		exit ();
	}elseif(!preg_match('/^[A-Za-z0-9]+$/',$rev)){
		$status['state'] = -1;
		$status['message'] = "Invalid revision";
		echo json_encode($status);
		//fwrite($myfile, "Invalid revision");
		exit ();
	
	}
	elseif(!preg_match('/^[0-9]*$/',$eoNumber)){
		$status['state'] = -1;
		$status['message'] = "Invalid EO number";
		echo json_encode($status);
		//fwrite($myfile, "Invalid EO number");
		exit ();
	*/
	}elseif(!preg_match('/^[A-Za-z]+$/',$Mandatory)){
		$status['state'] = -1;
		$status['message'] = "Invalid Madatory";
		echo json_encode($status);
		//fwrite($myfile, "Invalid Madatory");
		exit ();
	}


	//Escape the user input before using in mysql query.
	$lruNumber = mysqli_real_escape_string($dbConnection, $lruNumber);
	$title = mysqli_real_escape_string($dbConnection, $title);
	$description = mysqli_real_escape_string($dbConnection, $description);
	$sb = mysqli_real_escape_string($dbConnection, $sb);
	$mod = mysqli_real_escape_string($dbConnection, $mod);
	$rev = mysqli_real_escape_string($dbConnection, $rev);
	$eoNumber = mysqli_real_escape_string($dbConnection, $eoNumber);
	$Mandatory = mysqli_real_escape_string($dbConnection, $Mandatory);
	
	
	

		
	if ($entryLevel == 'single')
	{
		$sql = "INSERT INTO lru_failures (title, description, SB,  EO, Mandatory, releaseDate)
							  VALUES ('$title', '$description','$sb', '$eoNumber', '$Mandatory', '$datef')";
							  
		
		//fwrite($myfile, $sql);
		
							  
		if (! mysqli_query ( $dbConnection,$sql )) {
			$status['state'] = -1;
			$status['message'] = mysqli_error ( $dbConnection );
			echo json_encode($status);
			exit ();
		}
	}
	
	//fwrite($myfile, $lruNumber);
	$query = "SELECT id from lru_table where partNumber='$lruNumber'";
	//fwrite($myfile, $query);
	$result = mysqli_query($dbConnection, $query);
	$lruId = 0;
	if($result){
		while ($row = mysqli_fetch_array($result)) {
			$lruId = $row['id'];
			//fwrite($myfile,$row['id']);
				
		}
	}
	$query1 = "SELECT max(id) as id from lru_failures";
	$result1 = mysqli_query($dbConnection, $query1);
	$failureId = 0;
	if($result1){
		while ($row = mysqli_fetch_array($result1)) {
			$failureId = $row['id'];
			//fwrite($myfile,$failureId);
		}
	
	}
		
	$sql1 = "INSERT INTO partnumber_failures (lruId, failureId,  modVal, rev)
						  VALUES ('$lruId','$failureId', '$mod', '$rev')";
	if (! mysqli_query ( $dbConnection,$sql1 )) {
		$status['state'] = -1;
		$status['message'] = mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	if (! mysqli_commit ( $dbConnection )) {
		$status['state'] = -1;
		$status['message'] = mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}
	
	echo json_encode($status);
	exit();
}
elseif($request['action']=='delete'){

	$lruFixIds = $request ['lruFixIds'];
	//fwrite($myfile, "lruFixIds=$lruFixIds\n");
	
	$status = array('state' => 0,
					'message' => "Successfully Deleted HW Rev Mods"
					);
	$lruFixIdsStringArray = explode(',', trim($lruFixIds));
	$arrayCount = count($lruFixIdsStringArray);
	
	for ($i = 0; $i<$arrayCount;$i++)
	{
		
		$lruFixIdsString = explode('_', trim($lruFixIdsStringArray[$i]));
		
		$sql = "DELETE FROM partnumber_failures where failureId ='$lruFixIdsString[0]' and lruId='$lruFixIdsString[1]' ";
		mysqli_query ( $dbConnection,$sql );
		
		
		$sql1 = "SELECT count(*) count FROM partnumber_failures
					WHERE failureId ='$lruFixIdsString[0]'";
		$count = 0;
		
		$result = mysqli_query($dbConnection, $sql1);
		if($result){
			while ($row = mysqli_fetch_array($result)) {
				$count = $row['count'];
			}
		}
		
		
		if ($count == 0)
		{
			$sql = "DELETE FROM lru_failures WHERE id ='$lruFixIdsString[0]';";
			mysqli_query ( $dbConnection,$sql );
		}
	}
	//fclose($myfile); 
	/*
	if (!) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}
	*/
	if (! mysqli_commit ( $dbConnection )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	echo json_encode($status);
	
	exit();
}
elseif($request['action']=='update'){

	
	$lruNumber = $request ['lruNumber'];
	$title = $request ['title'];
	$description = $request ['description'];
	$sb = $request ['sb'];
	$lruId = $request ['lruId'];
	$failureId = $request ['failureId'];
	$mod = $request ['mod'];
	$rev = $request ['rev'];
	$eoNumber = $request ['eoNumber'];
	$Mandatory = $request ['Mandatory'];
	$releaseDate = $request ['releaseDate'];
	
	$newLRUId = 0;
	
	$findme   = ' ';
	$pos = strpos($lruNumber, $findme);
	$lruNumber = substr($lruNumber, 0, $pos);
	
	$status = array('state' => 0,
					'message' => "Successfully Deleted HW Rev Mods"
					);
	
	
	$sql1 = "SELECT id FROM lru_table
				WHERE partNumber='$lruNumber'";
	//fwrite($myfile, $sql1);
	$result = mysqli_query($dbConnection, $sql1);
	if($result){
		while ($row = mysqli_fetch_array($result)) {
			$newLRUId = $row['id'];
		}
	}
				
				
	$sql2 = "UPDATE partnumber_failures SET
							lruId=$newLRUId,
							modVal='$mod',
							rev='$rev'
			WHERE lruId=$lruId and failureId=$failureId";
	//fwrite($myfile, $sql2);
	$result = mysqli_query($dbConnection, $sql2);
	
	$sql3 = "UPDATE lru_failures SET
				title='$title',
				description='$description',
				SB='$sb',
				EO='$eoNumber',
				Mandatory='$Mandatory',
				releaseDate='$releaseDate'
			WHERE id=$failureId";
	//fwrite($myfile, $sql3);
	//fclose($myfile); 
	
	if (! mysqli_query ( $dbConnection,$sql3 )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	if (! mysqli_commit ( $dbConnection )) {
		$status['state'] = -1;
		$status['message'] = 'Error: ' . mysqli_error ( $dbConnection );
		echo json_encode($status);
		exit ();
	}

	echo json_encode($status);

	
	exit();
}

?>
