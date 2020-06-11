<?php

require('config.php');

date_default_timezone_set('Europe/Stockholm');

function error($msg) {
	die("{\"error\": \"$msg\"}");
}

$json = file_get_contents('php://input');
$options = json_decode($json, true);

$value = '';

if (isset($options['category'])) {
	$category = $options['category'];
} else {
	error('No category supplied');
}

if (isset($options['value'])) {
	$value = $options['value'];
}

$currentDate = date('Y-m-d H:i', time());

$outputString = "$currentDate,$category,$value\n";

// Write to file
$fp = fopen($stat_file_path, 'a');
fwrite($fp, $outputString);
fclose($fp);