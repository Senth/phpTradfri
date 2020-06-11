<?php
require('defines.php');
require('config.php');
include('list_internal.php');

$output = array(
	'groups' => $groups,
	'devices' => $devices
);

header('Content-Type: application/json');
echo json_encode($output);
