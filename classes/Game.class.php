<?php

class Game {
	private $players = [];
	private $allCardIds = [];	
	private $deck = null;
	private $table = null;
	private $activePlayer = '';
	public $status = 'inactive'; // inactive | playing | finished
	const FILENAME = 'store/aaaaaa.data';

	function __construct() {
	}

	public function startNewGame() {
		$this->deck = $this->createDeck();
		$this->table = new Table();
	}

	public function assignPlayer(string $name) : string {
		foreach ($this->players as $cp) {
			if (strtolower($cp->getName()) === trim(strtolower($name))) {
				dbg('refusing to register - player of similar name already exists', $name);
				return '';
			}
		}
		$p = new Player(trim($name));
		$this->players[$p->getId()] = $p;
		dbg("player set", $p);
		return $p->getId();
	}

	public function givePlayersFirstHands() : void {
		foreach ($this->players as $p) {
			$p->addCardToHand($this->deck->popCard());
			$p->addCardToHand($this->deck->popCard());
			$p->addCardToHand($this->deck->popCard());
			$p->addCardToHand($this->deck->popCard());
			$p->addCardToHand($this->deck->popCard());
		}
		dbg("players have their cards", $this->players);
	}

	public function getCurrentTableCopy() : Table {
		return clone $this->table;
	}

	public function getPlayersInfo() : array {
		$ret = [];
		foreach ($this->players as $p) {
			$ret[] = $p->getPlayerInfo($this->activePlayer);
		}
		return $ret;
	}

	public function setActivePlayer(string $id) : void {
		$this->activePlayer = $id;
	}

	public function getActivePlayerId() : string {
		return $this->activePlayer;
	}

	public function getPlayerCopy(string $id) : Player {
		return clone $this->players[$id];
	}

	public function doTurnAsGetCard(string $id) : bool {
		if ($id !== $this->activePlayer) {
			return false;
		}
		$this->players[$id]->addCardToHand($this->deck->popCard());
		dbg("player's hand", $this->players[$id]->getHand());
		$this->nextPlayerTurn(); //var_dump($id, $this->activePlayer, sizeOf($deck)); die;
		return true;
	}

	public function doTurnAsTableChange(string $id, Table $newTable, Cards $newHand) : bool {
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
		$otherPlayersHands = [];
		foreach ($this->players as $p) {
			if ($p->getId() === $this->activePlayer) {
				continue;
			}
			$otherPlayersHands = array_merge($otherPlayersHands, $p->getHand()->getCardIds());
		}

		dbg('sizes of currentHandIds newHandIds currentTableIds newTableIds handDiff tableDiff:', 
			sizeOf($currentHandIds), sizeOf($newHandIds), sizeOf($currentTableIds), 
			sizeOf($newTableIds), sizeOf($handDiff), sizeOf($tableDiff));

		// at least one card from player's hand is part of the new table
		if (!sizeOf($handDiff) || !sizeOf($tableDiff) || sizeOf(array_diff($handDiff, $tableDiff))) {
			echo "no cards from hand were moved to table\n";
			return false;
		}
		// there are still all cards in the game, none is missing or extra and they are the same ones
		if (sizeOf(array_diff(array_merge($newHandIds, $newTableIds, $otherPlayersHands), $this->allCardIds))) {
			echo "set of new and old cards are different\n";
			return false;
		}
		// no card from previous table is left in player's hand
		if (sizeOf(array_intersect($currentTableIds, $newHandIds))) {
			echo "card from table left in new hand\n";
			return false;
		}

		$this->table = $newTable;
		$this->players[$this->activePlayer]->setNewHand($newHand);
		$this->players[$this->activePlayer]->canAddCards = true; // allow player to add cards, after finishing his first set

		// end of the game or next players turn
		if (sizeOf($newHand) === 0 || $newHand->areOnlyJokersPresent()) {
			$this->gameOver($id);
		} else {
			$this->nextPlayerTurn();
		}
		echo "finishing valid turn\n";
		return true;
	}

	// todo: probably needs some refactoring
	private function nextPlayerTurn() : string {
		$next = null;
		$found = false;
		foreach ($this->players as $player) {
			if ($this->activePlayer === $player->getId()) {
				$found = true;
				continue;
			} else if ($found && $this->activePlayer !== $player->getId()) {
				$next = $player->getId();
				$this->activePlayer = $next;
				return $next;
			}
		}
		if ($found && !$next) {
			foreach ($this->players as $player) {
				if ($this->activePlayer !== $player->getId()) {
					$this->activePlayer = $player->getId();
					return $this->activePlayer;
				}
			}
		}
		return $this->activePlayer; // solve one player game only case
	}

	public function gameOver(string $id) : void {
		$this->status = 'finished';
		$this->players[$this->activePlayer]->winner = true;
		echo "Player " . $this->players[$id]->getName() . " wins!!!!";
	}

	public function save() : bool {
		$me = file_put_contents(self::FILENAME, serialize($this));
		dbg('saving', $me);
		return (bool) $me;
	}

	public static function load(string $gameId) : Game {
		dbg('loading');
		return unserialize(file_get_contents($gameId));
	}

	public function getTable() : Table {
		return $this->table;
	}

	public function getDeck() : Cards {
		return $this->deck;
	}

	private function createDeck() : Cards {
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
		// store all card unique ids to game itself for necessary later validations, before shuffle
		$this->allCardIds = $deck->getCardIds();
		$deck->shuffle();
		return $deck;
	}

}
