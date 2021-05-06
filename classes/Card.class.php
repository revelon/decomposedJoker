<?php

class Card {
	public $value = null;
	public $type = null;
	private $id = null;
	const SPADES = 'spades';
	const CLUBS = 'clubs';
	const HEARTS = 'hearts';
	const DIAMONDS = 'diamonds';
	const WILD = 'wildcard';

	function __construct(int $value, string $type) {
		$this->value = $value;
		$this->type = $type;
		$this->id = bin2hex(random_bytes(8));
	}

	public function getId() {
		return $this->id;
	}

  	public function __toString() {
    	return $this->id;
  	}
}
