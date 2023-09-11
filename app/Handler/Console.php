<?php

namespace App\Handler;

class Console {

	private $_colours = [
		"black" => "0;30",
		"red" => "0;31",
		"green" => "0;32",
		"yellow" => "0;33",
		"blue" => "0;34",
		"purple" => "0;35",
		"cyan" => "0;36",
		"white" => "0;37",
		"brightBlack" => "1;30",
		"brightRed" => "1;31",
		"brightGreen" => "1;32",
		"brightYellow" => "1;33",
		"brightBlue" => "1;34",
		"brightPurple" => "1;35",
		"brightCyan" => "1;36",
		"brightWhite" => "1;37"
	];

	private $_bgColours = [
		"black" => "40",
		"red" => "41",
		"green" => "42",
		"yellow" => "44",
		"blue" => "44",
		"purple" => "45",
		"cyan" => "46",
		"white" => "47"
	];

	private $_defaultColour = "brightBlue";

	public function output($string, $colour = null, $bg_colour = null, $newline = true) {
		if (!$colour) {
			$colour = $this->_defaultColour;
		}

		echo $this->format($string, $colour, $bg_colour);

		if ($newline) {
			echo $this->newline();
		}
	}

	public function newline() {
		echo "\n";
	}

	public function format($string, $colour = null, $bg_colour = null) {
		$out = "";
		$colourCode = isset($this->_colours[$colour]) ? $this->_colours[$colour] : null;
		$bgColourCode = isset($this->_bgColours[$bg_colour]) ? $this->_bgColours[$bg_colour] : null;
		$code = "";

		if ($colourCode) {
			$code =  $colourCode;
		}

		if ($bgColourCode) {
			$code .= ";${bgColourCode}";
		}

		if (strlen($code)) {
			$out .= "\e[${code}m";
		}

		$out .= "${string}\e[0m";

		return $out;
	}

}