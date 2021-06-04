<?php

class Replacement {

	public string $jokerId;
	public array $cards;

	function __construct(string $jokerId, array $replacements) {
		$this->jokerId = $jokerId;
		$this->cards = $replacements;
	}
}
