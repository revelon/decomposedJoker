<?php

class Game {
	private $players = null;
	private $allCardIds = [];	
	private $deck = null;
	private $table = null;
	private $activePlayer = '';
	const FILENAME = 'store/aaaaaa.data';

	function __construct() {
	}

	public function startNewGame() {
		$this->deck = $this->createDeck();
		$this->table = new Table();
		// store all card unique ids to game itself for necessary later validations
		$this->allCardIds = $this->deck->getCardIds();
	}

	public function assignPlayer(string $name) {
		$n = new Player($name);
		$n->addCardToHand($this->deck->popCard());
		$n->addCardToHand($this->deck->popCard());
		$n->addCardToHand($this->deck->popCard());
		$n->addCardToHand($this->deck->popCard());
		$n->addCardToHand($this->deck->popCard());
		$this->players[$n->getId()] = $n;
		dbg("player", $n);
		return $n->getId();
	}

	public function getCurrentTableCopy() {
		return clone $this->table;
	}

	public function setActivePlayer(string $id) {
		$this->activePlayer = $id;
	}

	public function getPlayerCopy(string $id) {
		return clone $this->players[$id];
	}

	public function doTurnAsGetCard(string $id) {
		if ($id !== $this->activePlayer) {
			return false;
		}
		$this->players[$id]->addCardToHand($this->deck->popCard());
		dbg("player's hand", $this->players[$id]->getHand());
		$this->nextPlayerTurn();
	}

	public function doTurnAsTableChange(string $id, Table $newTable, Cards $newHand) {
		// only active player is allowed to make changes
		if ($id !== $this->activePlayer) {
			echo "not current/active player is forbidden to play\n";
			return false;
		}
		// there should be at least one whole set on the table
		if (!sizeOf($newTable)) {
			echo "empty new table\n";
			return false;
		}
		// we should validate that new table has all valid groups
		if (!$newTable->areAllSetsValid()) {
			echo "invalid sets on the new table\n";
			return false;
		}
		// helper id sets
		$currentHandIds = $this->players[$this->activePlayer]->getHand()->getCardIds();
		$newHandIds = $newHand->getCardIds();
		$currentTableIds = $this->table->getCardIds();
		$newTableIds = $newTable->getCardIds();
		$handDiff = array_diff($currentHandIds, $newHandIds);
		$tableDiff = array_diff($newTableIds, $currentTableIds);

		dbg('sizes of currentHandIds newHandIds currentTableIds newTableIds handDiff tableDiff:', 
			sizeOf($currentHandIds), sizeOf($newHandIds), sizeOf($currentTableIds), 
			sizeOf($newTableIds), sizeOf($handDiff), sizeOf($tableDiff));

		// at least one card from player's hand is part of the new table
		if (!sizeOf($handDiff) || !sizeOf($tableDiff) || sizeOf(array_diff($handDiff, $tableDiff))) {
			echo "no cards from hand were moved to table\n";
			return false;
		}
		// there are still all cards in the game, none is missing or extra and they are the same ones
		if (sizeOf(array_diff(array_merge($newHandIds, $newTableIds), $this->allCardIds))) {
			echo "set of new and old cards are different\n";
			return false;
		}
		// no card from previous table is left in player's hand
		if (sizeOf(array_intersect($currentTableIds, $newHandIds))) {
			echo "card from table left in new hand\n";
			return false;
		}

		// end of the game or next players turn
		if (sizeOf($newHand) === 0) {
			$this->gameOver($id);
		} else {
			$this->table = $newTable;
			$this->players[$this->activePlayer]->setNewHand($newHand);
			$this->nextPlayerTurn();
		}
		echo "finishing valid turn\n";
		return true;
	}

	// todo: probably needs some refactoring
	private function nextPlayerTurn() {
		foreach ($this->players as $id => $player) {
			if ($id === $this->activePlayer) {
				$next = next($this->players);
				if ($next) {
					$this->activePlayer = $next->getId();
					return $this->activePlayer;
				} else {
					reset($this->players);
					$this->activePlayer = current($this->players)->getId();
					return $this->activePlayer;
				}
			}
		}
	}

	public function gameOver(string $id) {
		echo "Player " . $this->players[$id]->getName() . " wins!!";
	}

	public function save() {
		$me = file_put_contents(self::FILENAME, serialize($this));
		dbg('saving', $me);		
	}

	public static function load(string $gameId) {
		dbg('loading');
		return unserialize(file_get_contents($gameId));
	}

	public function getTable() {
		return $this->table;
	}

	// shoudl not be necessary!!! delete it later
	public function getDeck() {
		return $this->deck;
	}

	private function createDeck() {
		$deck = new Cards(
			new Card(0, Card::WILD),
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),

			new Card(1, Card::SPADES),
			new Card(2, Card::SPADES),
			new Card(3, Card::SPADES),
			new Card(4, Card::SPADES),
			new Card(5, Card::SPADES),
			new Card(6, Card::SPADES),
			new Card(7, Card::SPADES),
			new Card(8, Card::SPADES),
			new Card(9, Card::SPADES),
			new Card(10,Card::SPADES),
			new Card(11,Card::SPADES),
			new Card(12,Card::SPADES),
			new Card(13,Card::SPADES),

			new Card(1,	Card::HEARTS),
			new Card(2,	Card::HEARTS),
			new Card(3,	Card::HEARTS),
			new Card(4,	Card::HEARTS),
			new Card(5,	Card::HEARTS),
			new Card(6,	Card::HEARTS),
			new Card(7,	Card::HEARTS),
			new Card(8,	Card::HEARTS),
			new Card(9,	Card::HEARTS),
			new Card(10,Card::HEARTS),
			new Card(11,Card::HEARTS),
			new Card(12,Card::HEARTS),
			new Card(13,Card::HEARTS),

			new Card(1,	Card::CLUBS),
			new Card(2,	Card::CLUBS),
			new Card(3,	Card::CLUBS),
			new Card(4,	Card::CLUBS),
			new Card(5,	Card::CLUBS),
			new Card(6,	Card::CLUBS),
			new Card(7,	Card::CLUBS),
			new Card(8,	Card::CLUBS),
			new Card(9,	Card::CLUBS),
			new Card(10,Card::CLUBS),
			new Card(11,Card::CLUBS),
			new Card(12,Card::CLUBS),
			new Card(13,Card::CLUBS),

			new Card(1,	Card::DIAMONDS),
			new Card(2,	Card::DIAMONDS),
			new Card(3,	Card::DIAMONDS),
			new Card(4,	Card::DIAMONDS),
			new Card(5,	Card::DIAMONDS),
			new Card(6,	Card::DIAMONDS),
			new Card(7,	Card::DIAMONDS),
			new Card(8,	Card::DIAMONDS),
			new Card(9,	Card::DIAMONDS),
			new Card(10,Card::DIAMONDS),
			new Card(11,Card::DIAMONDS),
			new Card(12,Card::DIAMONDS),
			new Card(13,Card::DIAMONDS),

			new Card(1, Card::SPADES),
			new Card(2, Card::SPADES),
			new Card(3, Card::SPADES),
			new Card(4, Card::SPADES),
			new Card(5, Card::SPADES),
			new Card(6, Card::SPADES),
			new Card(7, Card::SPADES),
			new Card(8, Card::SPADES),
			new Card(9, Card::SPADES),
			new Card(10,Card::SPADES),
			new Card(11,Card::SPADES),
			new Card(12,Card::SPADES),
			new Card(13,Card::SPADES),

			new Card(1,	Card::HEARTS),
			new Card(2,	Card::HEARTS),
			new Card(3,	Card::HEARTS),
			new Card(4,	Card::HEARTS),
			new Card(5,	Card::HEARTS),
			new Card(6,	Card::HEARTS),
			new Card(7,	Card::HEARTS),
			new Card(8,	Card::HEARTS),
			new Card(9,	Card::HEARTS),
			new Card(10,Card::HEARTS),
			new Card(11,Card::HEARTS),
			new Card(12,Card::HEARTS),
			new Card(13,Card::HEARTS),

			new Card(1,	Card::CLUBS),
			new Card(2,	Card::CLUBS),
			new Card(3,	Card::CLUBS),
			new Card(4,	Card::CLUBS),
			new Card(5,	Card::CLUBS),
			new Card(6,	Card::CLUBS),
			new Card(7,	Card::CLUBS),
			new Card(8,	Card::CLUBS),
			new Card(9,	Card::CLUBS),
			new Card(10,Card::CLUBS),
			new Card(11,Card::CLUBS),
			new Card(12,Card::CLUBS),
			new Card(13,Card::CLUBS),

			new Card(1,	Card::DIAMONDS),
			new Card(2,	Card::DIAMONDS),
			new Card(3,	Card::DIAMONDS),
			new Card(4,	Card::DIAMONDS),
			new Card(5,	Card::DIAMONDS),
			new Card(6,	Card::DIAMONDS),
			new Card(7,	Card::DIAMONDS),
			new Card(8,	Card::DIAMONDS),
			new Card(9,	Card::DIAMONDS),
			new Card(10,Card::DIAMONDS),
			new Card(11,Card::DIAMONDS),
			new Card(12,Card::DIAMONDS),
			new Card(13,Card::DIAMONDS)
		);
		$deck->shuffle();
		return $deck;
	}

}
