<?php

class Player {

	private ?string $id = null;
	private ?string $name = null;
	private ?Cards $hand = null;
	public bool $canAddCards = false; // activated after first winished set
	public bool $won = false;

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
			'status' => ($this->won) ? 'winner' : (($this->id === $activePlayer) ? 'active' : 'idle'),
			'canAddCards' => $this->canAddCards ];
	}

	function getHand() : ?Cards {
		return $this->hand;
	}

	function setNewHand(Cards $hand) : void {
		// add some validations ?
		$this->hand = $hand;
	}

	function getName() : string {
		return $this->name;
	}

	function addCardToHand(Card $card) : void {
		$this->hand->pushCard($card);
		$this->hand->sortCards();
	}

	public function getCardByType(string $type, int $value) : ?Card {
		return $this->hand->getCardByType($type, $value);
	}
}
