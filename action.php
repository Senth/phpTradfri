<?php
require('defines.php');
require('config.php');
require('list_internal.php');

function error($msg) {
	die("{\"error\": \"$msg\"}");
}

function find_device_or_group($id, &$devices, &$groups) {
	foreach ($devices as $device) {
		if ($device['id'] == $id) {
			return $device;
		}
	}

	foreach($groups as $group) {
		if ($group['id'] == $id) {
			return $group;
		}
	}

	return null;
}

function find_scene_id($group, $scene_name) {
	foreach ($group['scenes'] as $scene) {
		if ($scene['name'] === $scene_name) {
			return $scene['id'];
		}
	}

	return null;
}

//parse json input from body
$json = file_get_contents('php://input');
$options = json_decode($json, true);

//check inputs given
if(!isset($options['action'])) error('Missing action');
if(!isset($options['type'])) error('Missing type');
if(!isset($options['value'])) error('Missing value');

// Using name instead
if(!isset($options['id'])) {
	if(!isset($options['name'])) error('Missing id or name');

	// Find name in device or group
	if($options['type'] == 'device') {
		$search_in = &$devices;
	} else if ($options['type'] == 'group') {
		$search_in = &$groups;
	}

	if (!isset($search_in)) error('Invalid type, use device or group');

	foreach ($search_in as $object) {
		if ($object['name'] == $options['name']) {
			$options['id'] = $object['id'];
		}
	}

	if(!isset($options['id'])) error ("Didn't find " . $options['type'] . " with the name " . $options['name'] . ".");
}


//check input content
if(!is_int($options['id'])) error('Invalid id');

$device_or_group = find_device_or_group($options['id'], $devices, $groups);
if ($device_or_group == null) error("Couldn't find device or group with the specified id");

$action = isset($options['action']) ? $options['action'] : null;
//var_dump($options);
if($action == 'dim') {
	if(!is_int($options['value']) or $options['value'] < 0 or $options['value'] > 255) error('Invalid value');
} else if($action == 'power') {
	// Use toggle
	if ($options['value'] === 'toggle') {
		$options['value'] = $device_or_group['status'] ? 0 : 1;
	}

	if(!is_int($options['value']) or $options['value'] < 0 or $options['value'] > 1) error('Invalid value');
} else if ($action == 'scene') {
	// Find scene id
	if (!is_int($options['value'])) {
		$scene_id = find_scene_id($device_or_group, $options['value']);
		if ($scene_id === null) error("Couldn't find scene '" . $options['value'] . "' in group '" . $device_or_group['name'] . "'");
	} else {
		$scene_id = $options['value'];
	}
} else {
	error('Invalid action');
}

//construct the payload depending on the type and action
$payload = null;
if($options['type'] == 'group') {
	$path = GROUPS . "/{$options['id']}";//id of the group
	if($action == 'power') {
		$payload = '{ "' . ONOFF ."\" : {$options['value']} }";//value == 0/1
	} else if($action == 'dim') {
		$payload = '{ "' . DIMMER ."\" : {$options['value']} }";//value == 0..255
	} else if ($action == 'scene') {
		$payload = '{ "' . ONOFF .'" : 1, "' . SCENE_ID . "\" : {$scene_id} }";//value == scene ID
	}
} else if($options['type'] == 'device') {
	$path = DEVICES ."/{$options['id']}";//id of the device
	if($action == 'power') {
		$payload = '{ "' . LIGHT . '": [{ "' . ONOFF ."\": {$options['value']} }] }";//value == 0/1
	} else if($action == 'dim') {
		$payload = '{ "' . LIGHT . '": [{ "' . DIMMER ."\": {$options['value']} }] }";//value == 0..255
	}
} else {
	error('Invalid type');
}

$cmd = "coap-client -m put -u '$gw_user' -k '$gw_key' -e '$payload' 'coaps://$gw_address:5684/$path'";

// Delay the command - write to file
if(isset($options['delay']) and $options['delay'] > 0) {
	$delay = $options['delay'];
	$cmd = "sleep $delay && $cmd";
	$schedule_content = strtotime("+$delay seconds") . " " . $cmd . "\n";

	file_put_contents($schedule_file, $schedule_content, FILE_APPEND);
} else {
	exec($cmd);
}
