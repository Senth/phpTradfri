<?php
require('defines.php');
require('config.php');

// COLOR_EBB63E = "ebb63e";
// COLOR_EFD275 = "efd275";
// COLOR_F1E0B5 = "f1E0B5";
// COLOR_F2ECCF = "f2eccf";
// COLOR_F5FAF6 = "f5faf6";
// COLOR_E78834 = "e78834";
// COLOR_E491AF = "e491af";
// COLOR_E8BEDD = "e8bedd";
// COLOR_D6E44B = "d6e44b";
// COLOR_EAF6FB = "eaf6fb";
// COLOR_E57345 = "e57345";
// COLOR_D9337C = "d9337c";
// COLOR_C984BB = "c984bb";
// COLOR_A9D62B = "a9d62b";

class Effect {
	private $transitions = array();

	public function add_transition(Transition $transition) {
		$this->transitions[] = $transition;
	}

	public function generate_command($light_id) {
		$combined_command = "";
		foreach ($this->transitions as $transition) {
			$combined_command .= $transition->generate_command($light_id) . " && ";	
		}

		// Remove last "& " so we end in " &" and it is executed in the background
		return substr($combined_command, 0, -2);
	}
}

interface Transition {
	public function generate_payload();
	public function generate_command($light_id);
}

abstract class LightTransition implements Transition {
	public function generate_command($light_id) {
		global $gw_user;
		global $gw_key;
		global $gw_address;

		$path = DEVICES . '/' . $light_id;
		return "coap-client -m put -u '$gw_user' -k '$gw_key' -e '" . $this->generate_payload() . "' 'coaps://$gw_address/$path'";
	}
}

// Transition time in 1/10th of a second
class SetColor extends LightTransition {
	public $x;
	public $y;
	public $transition_time;

	public function __construct($x, $y, $transition_time=0) {
		$this->x = $x;
		$this->y = $y;
		$this->transition_time = $transition_time;
	}

	public function generate_payload() {
		$payload = '{"' . LIGHT . '": [{"' . LIGHT_COLOR_X . '":' . $this->x . ', "' . LIGHT_COLOR_Y . '":' . $this->y;
		if ($this->transition_time > 0) {
			$payload .= ', "' . TRANSITION_TIME . '":' . $this->transition_time;
		}
		$payload .= '}]}';
	   return $payload;	
	}
}

class SetBrightness extends LightTransition {
	public $brightness;
	public $transition_time;

	public function __construct($brightness, $transition_time=0) {
		$this->brightness = $brightness;
		$this->transition_time = $transition_time;
	}

	public function generate_payload() {
		$payload = '{"' . LIGHT . '": [{"' . DIMMER . '":' . $this->brightness;
		if ($this->transition_time > 0) {
			$payload .= ', "' . TRANSITION_TIME . '":' . $this->transition_time;
		}
		$payload .= '}]}';
		return $payload;
	}
}

class Wait implements Transition {
	public $seconds;

	public function __construct($seconds) {
		$this->seconds = $seconds;
	}

	public function generate_payload() {
	}
	
	public function generate_command($light_id) {
		return "sleep $this->seconds";
	}
}


// Create sunrise effect
$effect_sunrise = new Effect();
$effect_sunrise->add_transition(new SetBrightness(1));
$effect_sunrise->add_transition(new SetColor(46000, 19650));
$effect_sunrise->add_transition(new Wait(5));

// Split into 80 parts (40 color + 40 brightness parts)
$x_start = 46000;
$x_stop = 32908;
$y_start = 19650;
$y_stop = 29591;
$steps = 10;
$brightness_start = 1;
$brightness_stop = 70;
$x_increment = ($x_stop - $x_start) / $steps;
$y_increment = ($y_stop - $y_start) / $steps;
$brightness_increment = ($brightness_stop - $brightness_start) / $steps;
$wait_time = 13;
$transition_time = 100;
for ($i = 1; $i <= $steps; ++$i) {
	$x = intval($x_start + $x_increment * $i);
	$y = intval($y_start + $y_increment * $i);
	$brightness = intval($brightness_start + $brightness_increment * $i);

	$effect_sunrise->add_transition(new SetBrightness($brightness, $transition_time));
	$effect_sunrise->add_transition(new Wait($wait_time));
	$effect_sunrise->add_transition(new SetColor($x, $y, $transition_time));
	$effect_sunrise->add_transition(new Wait($wait_time));
}

$x_start = $x_stop;
$x_stop = 21150;
$y_start = $y_stop;
$y_stop = 21150;
$brightness_start = 70;
$brightness_stop = 254;
$x_increment = ($x_stop - $x_start) / $steps;
$y_increment = ($y_stop - $y_start) / $steps;
$brightness_increment = ($brightness_stop - $brightness_start) / $steps;
for ($i = 1; $i <= $steps; ++$i) {
	$x = intval($x_start + $x_increment * $i);
	$y = intval($y_start + $y_increment * $i);
	$brightness = intval($brightness_start + $brightness_increment * $i);

	$effect_sunrise->add_transition(new SetBrightness($brightness, $transition_time));
	$effect_sunrise->add_transition(new Wait($wait_time));
	$effect_sunrise->add_transition(new SetColor($x, $y, $transition_time));
	$effect_sunrise->add_transition(new Wait($wait_time));
}
