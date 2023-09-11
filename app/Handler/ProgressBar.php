<?php

namespace App\Handler;

use App\Handler\Console;

class ProgressBar {

	private $_defaultOptions = [
		"steps" => 0,
		"length" => 60,
		"displayStatus" => true,
		"displayCount" => true,
		"displayPercent" => true
	];

	private $_currentStep = 0;

	private $_currentStatus = null;
	private $_currentStatusGroup = null;

	public $options = [];

	public function __construct(Array $options) {
		$this->console = new Console();

		$this->options = array_merge($this->_defaultOptions, $options);

		$this->updateDisplay();
	}

	public function forward(string $statusMessage = null) {
		if ($statusMessage) {
			$this->status($statusMessage);
		}

		$this->_currentStep++;

		$this->updateDisplay();
	}

	public function status(string $statusMessage) {
		$this->_currentStatus = $statusMessage;

		$this->updateDisplay();
	}

	public function statusGroup(string $statusMessage) {
		$this->_currentStatusGroup = $statusMessage;

		$this->updateDisplay();
	}

	private function updateDisplay() {
		$progress = $this->_currentStep;
		$total = $this->options["steps"];
		$barLength = $this->options["length"];

		$percent = intval(floor(($progress / $total) * 100));

		$barComplete = floor($barLength * ($percent / 100));
		$barRemaining = $barLength - $barComplete;

		$status = $this->_currentStatus ? $this->_currentStatus : "";
		$statusGroup = $this->_currentStatusGroup ? $this->_currentStatusGroup : null;

		$barChar = "=";
		$emptyBarChar = "#";

		$barText = $this->console->format(sprintf("[%'{$barChar}{$barComplete}s%'${emptyBarChar}{$barRemaining}s]", "", ""), "yellow");
		$count = $this->console->format("${progress}/${total}", "brightYellow");

		$barText = str_replace($barChar, "\xE2\x96\x88", $barText);
		$barText = str_replace($emptyBarChar, "\xE2\x96\x91", $barText);

		// First line
		echo "\033[2A\033[0G\033[2K${barText} ${count}";

		$statusText = $statusGroup ? $this->console->format("[${statusGroup}] ", "brightBlue") : "";

		$statusText .= $this->console->format($status, "yellow") . " " .  $this->console->format("(${percent}%)", "brightYellow");

		// Second line
		echo "\033[1B\033[0G\033[2K${statusText}";

		echo "\033[1B\033[0G";

	}
}