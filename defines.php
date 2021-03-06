<?php

if (!defined('DEVICES')) {
	//LWM2M uses numeric identifiers in the URL to address properties.
	//Define some constants for these. Some of these are standard (LWM2M).
	//Those that start with 9 probably indicate they're proprietary (IKEA).
	define('DEVICES', 15001);
	define('GROUPS', 15004);
	define('SCENE', 15005);
	define('NAME', 9001);
	define('LIGHT', 3311);
	define('POWER_OUTLET', 3312);
	define('LIGHT_COLOR_HEX', 5706);
	define('LIGHT_COLOR_HUE', 5707);
	define('LIGHT_COLOR_SATURATION', 5708);
	define('LIGHT_COLOR_X', 5709);
	define('LIGHT_COLOR_Y', 5710);
	define('LIGHT_MIREDS', 5711);
	define('TRANSITION_TIME', 5712);
	define('ONOFF', 5850);//3311/0/5850 = device on/off
	define('DIMMER', 5851);//3311/0/5851 = device brightness
	define('INSTANCE_ID', 9003);
	define('HS_ACCESSORY_LINK', 9018);
	define('HS_LINK', 15002);
	define('REACHABILITY_STATE', 9019);
	define('SCENE_ID', 9039);
	define('GATEWAY', 15011);
	define('CLIENT_IDENTITY_PROPOSED', 9090);
	define('AUTH_PATH', 9063);
	define('NEW_PSK_BY_GW', 9091);
	define('VERSION', 9029);
	define('DEVICE_OBJECT_INSTANCE', 3);
	define('DEVICE_BATTERY_LEVEL', 9);
	define('TYPE', 5750);
	//From my own observation on the Tradfri devices
	define('TYPE_REMOTE_CONTROL', 0);
	define('TYPE_LIGHT', 2);
	define('TYPE_POWER_OUTLET', 3);
	define('TYPE_MOTION_SENSOR', 4);

	//stream identifiers for proc_open
	define('STDIN', 0);
	define('STDOUT', 1);
	define('STDERR', 2);
}
