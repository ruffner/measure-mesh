<?php

require 'neatdb.php';

/////////////////////
// INSERTING DATA
//////////////////////
if ( isset( $_GET['values'] ) && isset( $_GET['runNumber'] ) ){
	$db = new NeatDB();
	if( !$db->connect() ) {
		exit(0);
	}
	
	$sql = "INSERT INTO feed_log (temps, mtype,stype) VALUES (" . $db->quote($_GET['values']). ", " . $db->quote($_GET['runNumber']) .", 0)";
	
	if( isset($_GET['new']) ){
		$sql = "INSERT INTO feed_log (temps, mtype,stype) VALUES (" . $db->quote($_GET['values']). ", " . $db->quote($_GET['runNumber']) .", 1)";
	}
	
	$db->query($sql);
  
	header('Content-Type: application/json');
  
	if( $db->error() == "" ) {
		echo "success";
	} else {
			echo "failure";
	}
	exit(0);
}


/////////////////////
// GRAPHING DATA
/////////////////////
if(isset($_GET['getRun'])) {
	$db = new NeatDB();
	if(!$db->connect()) {
	echo "db failure";
	exit(1);
	} 

	$data = $db->select("SELECT time,temps FROM feed_log WHERE mtype=".$db->quote($_GET['getRun']));

	$final = Array();
	
	foreach($data as $row) {
		$rv = Array();
		$temps = explode(",", $row['temps']);
		//$temps = $row['temps'];
		$rv[] = substr($row['time'], 11, 8);
		foreach($temps as $temp) {
			$rv[] = doubleval($temp);
		}
		$final[] = $rv;
	}
	
	header('Content-Type: application/json');
	echo json_encode($final);
	exit(0);
}

// main info source for page
if( isset($_GET['ids']) ){
	$db = new NeatDB();
	if(!$db->connect()) {
		exit(1);
	}
	
	// get temperature the device is trying to maintain
	$row = $db->select("SELECT val FROM params WHERE id='threshold'");
	$gt = $row[0]['val'];
	
	$row = $db->select("SELECT val FROM params WHERE id='interval'");
	$ui = $row[0]['val'];
	
	header('Content-Type: application/json');
	$data = Array("type" => 	"availableRuns", 
				  "values" => 	buildRunList($db),
				  "goalTemp" => $gt,
				  "upInt" =>	$ui);
	
	echo json_encode($data);
	exit(0);
}

// find highest mtype in db then add 1
if(isset($_GET['nextId'])) {
	$db = new NeatDB();
	if(!$db->connect()) {
		exit(1);
	}
	echo getNextRunID($db);
	exit(0);
}

// create CSV of selected run
if( isset($_GET['dl']) ) {
    if( $_GET['dl'] == '' ) {
    	echo "need a run to get";
			exit(1); 
    }

    $db = new NeatDB();
    if(!$db->connect()) {
			exit(1);
    }
    
    createCSV($db, $_GET['dl']);
    
    exit(0);
}

// remove all entries in the db with the 
if ( isset($_GET['rm']) ) {
	if( $_GET['rm'] == '' ) {
    	echo "need a run to remove";
		exit(1); 
    }

    $db = new NeatDB();
    if(!$db->connect()) {
    	exit(1);
    }
    
    $db->query("DELETE FROM feed_log WHERE mtype=".$db->quote($_GET['rm']));
    
    header('Content-Type: application/json');
    
    if( $db->error() == "" ) {
		echo json_encode(Array("type" => "deleteSuccess", "newIds" => getRunIDs($db)));
	} else {
		echo json_encode(Array("type" => "deleteFailure"));
	}
	exit(0);
}

if ( isset($_GET['getThresh']) ){
    $db = new NeatDB();
    if(!$db->connect()) {
        exit(1);
    }

    $thresh = $db->select("SELECT id,val FROM params WHERE id='threshold'");
	echo $thresh[0]['val'];
	exit(0);
}

if( isset($_GET['setThresh']) ){
    if( $_GET['setThresh'] == '' ) {
        echo "need valid value";
       	exit(1);
    }

    $db = new NeatDB();
    if(!$db->connect()) {
        exit(1);
    }

    $db->query("UPDATE params SET val=".$db->quote($_GET['setThresh'])." WHERE id='threshold'");

    header('Content-Type: application/json');

    if( $db->error() == "" ) {
    	echo json_encode(Array("type" => "threshChangeSuccess"));
    } else {
		echo json_encode(Array("type" => "threshChangeFailure"));
	}
	exit(0);
}

if( isset($_GET['getInterval']) ){
    $db = new NeatDB();
    if(!$db->connect()) {
        exit(1);
    }

    $interval = $db->select("SELECT id,val FROM params WHERE id='interval'");
	echo $interval[0]['val'];
	exit(0);
}

if( isset($_GET['setInterval']) ){
	if( $_GET['setInterval'] == '' ) {
        echo "need valid value";
       	exit(1);
    }

	$db = new NeatDB();
	if(!$db->connect()) {
        exit(1);
    }
	
	$db->query("UPDATE params SET val=".$db->quote($_GET['setInterval'])." WHERE id='interval'");

    header('Content-Type: application/json');

    if( $db->error() == "" ) {
    	echo json_encode(Array("type" => "intervalChangeSuccess"));
    } else {
		echo json_encode(Array("type" => "intervalChangeFailure"));
	}
	exit(0);
}

function getNextRunID($db) {
	$ids = $db->select("SELECT DISTINCT mtype FROM feed_log");
	$highest_id = 0;
	if( count($ids) ){
		$highest_id = $ids[0]['mtype'];
	
		foreach( $ids as $id ){
			if( intval($id['mtype']) > $highest_id ){
				$highest_id = $id['mtype'];
			}	
		}
	}
	return $highest_id + 1;
}

function getRunIDs($db) {
	$data = $db->select("SELECT DISTINCT mtype FROM feed_log");
	$vals = Array();
	foreach ( $data as $row ){
		$vals []= $row['mtype'];
	}
	return $vals;
}

function buildRunList($db) {
	$data = $db->select("SELECT time,mtype FROM feed_log WHERE stype=1 ORDER BY time ASC");
	$final = Array();
	
	foreach ( $data as $row ){
		$tm = date_create($row['time']);
		$final[] = Array("runNum" => $row['mtype'], "time" => date_format($tm, "g:ia  D n/j/Y"));
	}
	return $final;
}

function createCSV($db, $runID) {
	$data = $db->select("SELECT time,temps FROM feed_log WHERE mtype=".$db->quote($_GET['dl']));

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=run".$_GET['dl'].".csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $heading =  "Time, ";
    $temps = explode(",", $data[0]['temps']);
    $len = count($temps);
    
    for( $i=1; $i<$len+1; $i=$i+1 ) {
			$heading .= "Cup ";
			$heading .= $i;
			if( $i<$len ) {
				$heading .= ", ";
			} 
    }
    
    $heading .= "\n";
    echo $heading;

    foreach ( $data as $row ){
		$temps = explode(",", $row['temps']);
		$line = $row['time'] . ", ";
		for( $i=0; $i<$len; $i=$i+1 ){
			if( $i < $len-1 ){
				$line .= $temps[$i] . ", ";
			} else {
				$line .= $temps[$i] . "\n";
	    	}
		}
		echo $line;
    }
}

?>
