<?php

class Player {

	private $id = null;
	private $name = null;
	private $hand = null;
	public $canAddCards = false; // activated after first winished set
	public $won = false;

	function __construct(string $name) {
		$this->name = $name;
		$this->hand = new Cards();
		$this->id = bin2hex(random_bytes(8));
	}

	public function getId() : string {
		return $this->id;
	}

	public function getPlayerInfo(string $activePlayer) : array {
		return ['name' => $this->name, 'cards' => sizeOf($this->hand->getCards()), 
			'status' => ($this->won) ? 'winner' : ($this->id === $activePlayer) ? 'active' : 'idle',
			'canAddCards' => $this->canAddCards ];
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
