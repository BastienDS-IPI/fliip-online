<?php

$dbhost = "localhost";
$dbuser = "fliippin_dbuser";
$dbpass = "f00tb@LL972";
$dbname = "fliippin_dbsql";

$sql = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$sql->set_charset("utf8");

// Check connection
if ($sql->connect_error) {
    die("Connection DATABASE failed: <br/>" . $sql->connect_error);
} 

function downtext ($downnumber) {
	switch ($downnumber) {
		case 1: return "1st";
		case 2: return "2nd";
		case 3: return "3rd";
		case 4: return "4th";
		case 0: return "KickOff";
		default: return "";
	}
}


?>