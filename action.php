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
		if (strtolower($scene['name']) === strtolower($scene_name)) {
			return $scene['id'];
		}
	}

	return null;
}

//parse json input from body
$json = file_get_contents('php://input');
$options = json_decode($json, true);

// Special case -> kill all effects
if ($options['action'] == 'effect' && $options['value'] == 'kill') {
	exec("pgrep -f $schedule_file", $output);
	foreach ($output as $pid) {
		file_put_contents($schedule_file, "kill -15 $pid\n", FILE_APPEND);
	}	
}


//check inputs given
if(!isset($options['action'])) error('Missing action');
if(!isset($options['type'])) error('Missing type');
if(!isset($options['value'])) error('Missing value');

// Using name instead
if(!isset($options['id'])) {
	if(!isset($options['name'])) error('Missing id or name');

	// Remove 'the ' from the name if we get a call from IFTTT
	$options['name'] = str_replace('the ', '', $options['name']);

	// Find name in device or group
	if($options['type'] == 'device') {
		$search_in = &$devices;
	} else if ($options['type'] == 'group') {
		$search_in = &$groups;
	}

	if (!isset($search_in)) error('Invalid type, use device or group');

	foreach ($search_in as $object) {
		if (strtolower($object['name']) == strtolower($options['name'])) {
			$options['id'] = $object['id'];
		}
	}

	if(!isset($options['id'])) error ("Didn't find " . $options['type'] . " with the name " . $options['name'] . ".");
}


//check input content
if(!is_int($options['id'])) error('Invalid id');

$device_or_group = find_device_or_group($options['id'], $devices, $groups);
if ($device_or_group == null) error("Couldn't find device or group with the specified id");

$cmd = null;

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
} else if ($action == 'effect') {
	require('effects.php');
	
	$options['async'] = true;

	// Run the correct effect
	if ($options['value'] == 'sunrise') {
		$cmd = $effect_sunrise->generate_command($options['id']);
	}
} else {
	error('Invalid action');
}

//construct the payload depending on the type and action
if ($options['action'] != 'effect') {
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
	if(isset($options['delay']) and is_int($options['delay'])) {
		$delay = $options['delay'] * 60;
		$cmd = "sleep $delay && $cmd";
		$options['async'] = true;
	}
}

if ($cmd == null) {
	error('Something went wrong');
}

// Async command
if (isset($options['async']) and $options['async'] == TRUE) {
// 	error($cmd);
	file_put_contents($schedule_file, $cmd . "\n", FILE_APPEND);	
}
// Execture directly from the script
else {
	exec($cmd);
}

