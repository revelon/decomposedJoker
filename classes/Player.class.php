<?php

class Player {

	private $id = null;
	private $name = null;
	private $hand = null;

	function __construct(string $name) {
		$this->name = $name;
		$this->hand = new Cards();
		$this->id = bin2hex(random_bytes(8));
	}

	function getId() {
		return $this->id;
	}

	function getHand() {
		return $this->hand;
	}

	function setNewHand(Cards $hand) {
		// add some validations ?
		$this->hand = $hand;
	}

	function getName() {
		return $this->name;
	}

	function addCardToHand(Card $card) {
		$this->hand->pushCard($card);
		$this->hand->sortCards();
	}

	public function getCardByType(string $type, int $value) {
		return $this->hand->getCardByType($type, $value);
	}
}
