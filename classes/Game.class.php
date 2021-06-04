<?php

class Game {
	private ?string $id = 'store/';
	private array $players = [];
	private array $allCardIds = [];	
	private ?Cards $deck = null;
	private ?Cards $fullDeck = null; // temporary solution, solve in better way later!!
	private ?Table $table = null;
	private string $activePlayer = '';
	public string $status = 'inactive'; // inactive | playing | finished
	public int $turns = 0;

	function __construct(string $gameId) {
		$this->id = self::getGameFileName($gameId);
	}

	public static function getGameFileName(string $gameId) {
		return 'store/' . md5(strtoupper($gameId));
	}

	public function startNewGame() {
		$this->deck = $this->createDeck();
		$this->fullDeck = clone $this->deck;
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
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());

		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());

		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());
		$p->addCardToHand($this->deck->popCard());

		$this->players[$p->getId()] = $p;
		dbg("player set", $p);
		return $p->getId();
	}

	public function getCardsInPlayersHands() : array {
		$ret = [];
		foreach ($this->players as $p) {
			$ret = array_merge($ret, $p->getHand()->getCards());
		}
		dbg("players have these cards already", $ret);
		return $ret;
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

	public function doTurnAsGetCard(string $id) : ValidationResult {
		if ($id !== $this->activePlayer) {
			return ValidationResult::get(false, 'not-active-player-cannot-get-a-card');
		}
		$this->players[$id]->addCardToHand($this->deck->popCard());
		dbg("player's hand", $this->players[$id]->getHand());
		$this->nextPlayerTurn();
		return ValidationResult::get(true);
	}

	public function doTurnAsTableChange(string $id, Table $newTable, Cards $newHand) : ValidationResult {
		// only active player is allowed to make changes
		if ($id !== $this->activePlayer) {
			dbg("not current/active player is forbidden to play");
			return ValidationResult::get(false, 'not-active-player-is-forbidden-to-play');
		}
		// there should be at least one whole set on the table
		if (!sizeOf($newTable)) {
			dbg("empty new table");
			return ValidationResult::get(false, 'new-table-is-empty');
		}
		// we should validate that new table has all valid groups
		$validation = $newTable->areAllSetsValid();
		if (!$validation->success) {
			dbg("invalid set on the new table", $validation);
			return $validation;
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
			dbg("no cards from hand were moved to table");
			return ValidationResult::get(false, 'no-cards-from-hand-were-moved-to-table');
		}
		// there are still all cards in the game, none is missing or extra and they are the same ones
		if (sizeOf(array_diff(array_merge($newHandIds, $newTableIds, $otherPlayersHands), $this->allCardIds))) {
			dbg("set of new and old cards are different");
			return ValidationResult::get(false, 'set-of-new-and-old-cards-are-different');
		}
		// no card from previous table is left in player's hand
		if (sizeOf(array_intersect($currentTableIds, $newHandIds))) {
			dbg("card from table left in new hand");
			return ValidationResult::get(false, 'card-from-table-is-left-in-hand');
		}

		// !!!!!
		// funguje blbe uz pridelovani ID z decku, protoze uz mohou byt rozdany v ruce!!! !!!!! docasne fixnuto!
		// var_dump($this->table); die('aaaaaaa');
		// projit stary stul, vytahnout vsechny nahrady jokeru a pokud neco z toho nesedi v novem, kricet!!
		foreach ($this->table as $set) {
			if (sizeOf($set->sameTypeJokerReplacements)) { // there were some jokers in previous set
				$jokers = $set->getCardsByType(Card::WILD, 0); // get them
				foreach ($newTable as $newSet) {
					if ($set->id === $newSet->id) { // find if the set still exists on new table
						foreach ($jokers as $j) { // check presence of the same previous jokers by id
							if (!in_array($j->getId(), $newSet->getCardIds())) {
								// and if not there, try to find its replacements elsewhere on the new table
								if (sizeOf($set->sameTypeJokerReplacements) === 1) {
									// only one card mising from four of the type
									if ($newTable->isCardPresent($set->sameTypeJokerReplacements[0]->cards[0]->getId()) || 
									$newTable->isCardPresent($set->sameTypeJokerReplacements[0]->cards[1]->getId())) {
										// success
									} else {
										dbg("card replacing joker from set of four of the type was not found on the new table A");
										return ValidationResult::get(false, 'card-replacing-joker-from-set-of-four-of-the-type-was-not-found-on-the-table-a', $newSet->id);
										return false;
									}
								} else if (sizeOf($set->sameTypeJokerReplacements) === 2) {
									// two cards mising from four of the type
									if (($newTable->isCardPresent($set->sameTypeJokerReplacements[0]->cards[0]->getId()) || 
									$newTable->isCardPresent($set->sameTypeJokerReplacements[0]->cards[1]->getId())) && 
									($newTable->isCardPresent($set->sameTypeJokerReplacements[1]->cards[0]->getId()) || 
									$newTable->isCardPresent($set->sameTypeJokerReplacements[1]->cards[1]->getId()))) {
										// success
									} else {
										dbg("card replacing joker from set of four of the type was not found on the new table B");
										return ValidationResult::get(false, 'card-replacing-joker-from-set-of-four-of-the-type-was-not-found-on-the-table-b', $newSet->id);
									}
								}
							}
						}
					}
				}
			} else if (sizeOf($set->lineJokerReplacements)) { // there were some jokers in previous set II
				// TBD implement and beware which joker you are solving
				$jokers = $set->getCardsByType(Card::WILD, 0); // get them all from old set

				foreach ($newTable as $newSet) {
					if ($set->id === $newSet->id) { // find if the set still exists on new table
						foreach ($jokers as $j) { // check presence of the same previous joker by id
							if (!in_array($j->getId(), $newSet->getCardIds())) {
								// and if not there, try to find its replacements elsewhere on the new table
								if ($newTable->isCardPresent($set->lineJokerReplacements[$j->getId()]->cards[0]->getId()) || 
								$newTable->isCardPresent($set->lineJokerReplacements[$j->getId()]->cards[1]->getId())) {
									// success
								} else {
									dbg("card replacing joker from line set was not found on the new table");
									return ValidationResult::get(false, 'card-replacing-joker-from-line-was-not-found-on-the-table', $newSet->id);
								}
							}
						}
					}
				}
			}
		}


		$this->table = $newTable;
		$this->players[$this->activePlayer]->setNewHand($newHand);
		$this->players[$this->activePlayer]->canAddCards = true; // allow player to add cards, after finishing his first valid set on the table

		// end of the game or next players turn
		if (sizeOf($newHand) === 0 || $newHand->areOnlyJokersPresent()) {
			$this->nextPlayerTurn();
			$this->gameOver($id);
		} else {
			$this->nextPlayerTurn();
		}
		dbg("finishing valid turn");
		return ValidationResult::get(true);
	}

	// todo: probably needs some refactoring
	private function nextPlayerTurn() : string {
		$next = null;
		$found = false;
		$this->turns++;		
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
		dbg("Player " . $this->players[$id]->getName() . " wins!!!!");
	}

	public function getAllPlayersHands() : array {
		$ret = [];
		if ($this->status === 'finished') {
			foreach($this->players as $player) {
				$ret[$player->getName()] = $player->getHand()->getCardIds();
			}
		}
		return $ret;
	}

	public function save() : bool {
		$me = file_put_contents($this->id, serialize($this));
		dbg('saving', $me);
		return (bool) $me;
	}

	public static function load(string $gameId) : ?Game {
		dbg('loading', $gameId);
		if (!file_exists(self::getGameFileName($gameId))) {
			return null;
		}
		return unserialize(file_get_contents(self::getGameFileName($gameId)));
	}

	public function getTable() : Table {
		return $this->table;
	}

	public function getDeck($full = false) : Cards {
		return ($full) ? $this->fullDeck : $this->deck;
	}

	private function createDeck() : Cards {
		$deck = new Cards(
			new Card(0, Card::WILD),
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),

			new Card(0, Card::WILD),  // boost some extra jokers, for more fun, remove later !!!!
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),

			new Card(0, Card::WILD),  // boost some extra jokers, for more fun, remove later !!!!
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),
			new Card(0,	Card::WILD),

			new Card(0, Card::WILD),  // boost some extra jokers, for more fun, remove later !!!!
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
