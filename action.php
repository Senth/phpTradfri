<?php
//parse json input from body
$json = file_get_contents('php://input');
$options = json_decode($json, true);

function error($msg) {
	die("{\"error\": \"$msg\"}");
}

// Send to python
$fp = stream_socket_client('tcp://localhost:10200', $errno, $errstr, 5);
if (!$fp) {
	error("$errorstr ($errno)");
} else {
	fwrite($fp, $json);
	$returnVal = fread($fp, 4096);
	fclose($fp);

	if ($returnVal) {
		echo $returnVal;
	} else {
		echo 'all ok';
	}
}
