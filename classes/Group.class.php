<?php

// Group is basically stack of validated Cards with validation extension, but it is a little bit dirty...
class Group extends Cards {

	public string $id = '';

	// array could include one or more arrays with pairs of replaceable cards
	public array $sameTypeJokerReplacements = []; // special case, if group is of the same value with joker, we must during disassemble/modification of the group be sure, that specific card has been added to the table; OR mode
	public array $lineJokerReplacements = []; // AND mode

	private function __construct() {
	}

	public static function createSet(Cards $newSet, string $id) : object {
		$res = self::validate($newSet);
		if ($res->success) {
			$set = new Group();
			$set->rows = $newSet->getCards();
			$set->id = $id;
			return $set;
		} else {
			$res->groupId = $id;
			return $res;
		}
	}

	public function fillJokerReplacements(Cards $deck) : void {
		dbg('fillJokerReplacements');

		$types = [];
		$values = [];
		foreach ($this->rows as $card) {
			$types[] = $card->type;
			$values[] = $card->value;
		}
		$typesUnique = array_unique($types);
		$valuesUnique = array_unique($values);

		// there are no jokers
		if (!in_array(0, $valuesUnique)) {
			return;
		}

		// three or four cards of the same value but different types, with jokers
		if ((sizeOf($typesUnique) === sizeOf($this->rows)) && 
			(sizeOf($valuesUnique) === 2 && in_array(0, $valuesUnique)) && 
			sizeOf($this->rows)<5) {

			$value = array_diff($valuesUnique, [Card::WILD]);
			$missingTypes = array_diff([Card::SPADES, Card::CLUBS, CARD::HEARTS, Card::DIAMONDS, Card::WILD], $typesUnique); // WILD will be sorted out always, in this case
			$jokerId = $this->getCardByType(Card::WILD, 0)->getId();
			foreach($missingTypes as $type) {
				$this->sameTypeJokerReplacements[] = new Replacement($jokerId, $deck->getCardsByType($type, $value[0])); // getCardsByType return usually array of two cards from full deck, like two spades tens etc.
			}

		} else { // we are looking for next/previous value replacement exactly in line of cards

			foreach ($this->rows as $i => $card) {
				if ($i === 0 && $card->type === Card::WILD) { // based on next card we will know the one we are looking for
					if (($this->rows[$i+1]->value - 1) === 0) { // special rewind case => so pick King/13 instead
						$this->lineJokerReplacements[$card->getId()] = new Replacement($card->getId(), $deck->getCardsByType($this->rows[$i+1]->type, 13));
					} else {
						$this->lineJokerReplacements[$card->getId()] = new Replacement($card->getId(), $deck->getCardsByType($this->rows[$i+1]->type, $this->rows[$i+1]->value - 1));
					}
				} else if ($i > 0 && $card->type === Card::WILD) { // we will decide on previous card base
					if (($this->rows[$i-1]->value + 1) === 0) { // special rewind case => so pick Ace/1 instead
						$this->lineJokerReplacements[$card->getId()] = new Replacement($card->getId(), $deck->getCardsByType($this->rows[$i-1]->type, 1));
					} else {
						$this->lineJokerReplacements[$card->getId()] = new Replacement($card->getId(), $deck->getCardsByType($this->rows[$i-1]->type, $this->rows[$i-1]->value + 1));
					}
				}
			}
		}
	}

	public static function validate(Cards $newSet) : ValidationResult {
		dbg('validating', $newSet);

		// not enough cards
		if (sizeOf($newSet)<3) {
			return ValidationResult::get(false, 'not-enough-cards-in-set');
		}

		$types = [];
		$values = [];
		foreach ($newSet as $card) {
			$types[] = $card->type;
			$values[] = $card->value;
		}
		$typesUnique = array_unique($types);
		$valuesUnique = array_unique($values);

		//dbg('values typesUnique valuesUnique', $values, $typesUnique, $valuesUnique);

		// three or four cards of the same value but different types, with jokers
		if ((sizeOf($typesUnique) === sizeOf($newSet)) && 
			(sizeOf($valuesUnique) === 1 || ((sizeOf($valuesUnique) === 2 && in_array(0, $valuesUnique)))) && 
			sizeOf($newSet)<5) {
			return ValidationResult::get(true);
		}

		// cards are not of one type only (or one type + jokers)
		if (!(sizeOf($typesUnique) === 1 || ((sizeOf($typesUnique) === 2 && in_array(Card::WILD, $typesUnique))))) {
			return ValidationResult::get(false, 'cards-of-the-same-value-are-not-of-one-type-only');
		}

		// too many jokers for three cards
		if (in_array(0, $valuesUnique) && sizeOf($values) === 3 && sizeOf($values) !== sizeOf($valuesUnique)) {
			return ValidationResult::get(false, 'too-many-jokers-for-three-cards');
		}

		// iterate through set and try to say if it is meaningful
		for ($i = 1; $i < sizeOf($values); $i++) {
			$prev = $values[$i-1];
			$curr = $values[$i];

			dbg('iteration details', $values, 'i', $i, 'prev', $prev, 'curr', $curr);

			// two following jokers are never allowed
			if ($prev === 0 && $curr === 0) {
				return ValidationResult::get(false, 'two-following-jokers-present');
			}

			// standard increase is always ok
			if ($prev+1 === $curr) {
				continue;
			}

			// solve joker between king and ace invalid case
			if ($prev === 13 && $curr === 0 && isset($values[$i+1]) && $values[$i+1] === 1) {
				return ValidationResult::get(false, 'joker-between-king-and-ace');
			}

			// joker combination is valid if line is starting only
			if ($i < 3 && ($prev === 0 || $curr === 0)) {
				continue;
			}

			// joker combination is valid on the line rewind only sometimes
			if ($i > 2 && $prev === 0 && 	(
					($curr-2 === $values[$i-2]) || // generic case
					($values[$i-2] === 12 && $curr = 1) || // king case
					($values[$i-2] === 13 && $curr = 2) // ace case
											)
			) {
				continue;
			}

			// odd jokers combination is valid, as well as individual joker in a row
			if ($i > 2 && $curr === 0 && ($values[$i-2] === 0 || $values[$i-2] === $prev-1)) {
				continue;
			}

			// solve rewind case
			if ($prev === 13 && $curr === 1) {
				continue;
			}

			// no valid case found, reject it as invalid case
			return ValidationResult::get(false, 'group-is-invalid');
		}
		return ValidationResult::get(true);
	}	

	public static function tests() : void {
		asserts("Not enough cards", 
				Group::validate(new Cards( new Card(1, Card::SPADES), new Card(3, Card::SPADES) ))->success, 
				false);
		asserts("Valid three of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::DIAMONDS) ))->success, 
				true);
		asserts("Valid three of a type with Joker", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(0, Card::WILD) ))->success, 
				true);
		asserts("Valid another three of a type with Joker", 
				Group::validate(new Cards( new Card(2, Card::HEARTS), new Card(0, Card::WILD), new Card(4, Card::HEARTS) ))->success, 
				true);
		asserts("Valid four of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::DIAMONDS), new Card(10, Card::HEARTS) ))->success, 
				true);
		asserts("Invalid five of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::DIAMONDS), new Card(10, Card::HEARTS), new Card(0, Card::WILD) ))->success, 
				false);
		asserts("Invalid three of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::CLUBS) ))->success, 
				false);
		asserts("Valid row of three", 
				Group::validate(new Cards( new Card(1, Card::SPADES), new Card(2, Card::SPADES), new Card(3, Card::SPADES) ))->success, 
				true);
		asserts("Valid row of four with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::SPADES), new Card(0, Card::WILD), new Card(7, Card::SPADES) ))->success, 
				true);
		asserts("Invalid row of four with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::CLUBS), new Card(0, Card::WILD), new Card(7, Card::SPADES) ))->success, 
				false);
		asserts("Valid row of three with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::SPADES), new Card(0, Card::WILD) ))->success, 
				true);
		asserts("Invalid row with two following jokers", 
				Group::validate(new Cards( new Card(3, Card::SPADES), new Card(4, Card::SPADES), new Card(0, Card::WILD), new Card(0, Card::WILD), new Card(7, Card::SPADES) ))->success, 
				false);
		asserts("Invalid row of three with skips over the ace", 
				Group::validate(new Cards( new Card(12, Card::CLUBS), new Card(1, Card::CLUBS), new Card(3, Card::CLUBS) ))->success, 
				false);
		asserts("Valid row of three over the ace", 
				Group::validate(new Cards( new Card(13, Card::CLUBS), new Card(1, Card::CLUBS), new Card(2, Card::CLUBS) ))->success, 
				true);
		asserts("Valid row of three over the ace with joker", 
				Group::validate(new Cards( new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(2, Card::CLUBS) ))->success, 
				true);
		asserts("Invalid row of four with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::SPADES), new Card(0, Card::WILD), new Card(6, Card::SPADES) ))->success, 
				false);
		asserts("Valid row of three over the ace", 
				Group::validate(new Cards( new Card(12, Card::CLUBS), new Card(13, Card::CLUBS), new Card(1, Card::CLUBS) ))->success, 
				true);
		asserts("Valid row of three over the ace with joker", 
				Group::validate(new Cards( new Card(12, Card::CLUBS), new Card(0, Card::WILD), new Card(1, Card::CLUBS) ))->success, 
				true);
		asserts("Valid complex row of many with rewind without jokers, incomplete circle", 
				Group::validate(new Cards( new Card(11, Card::CLUBS), new Card(12, Card::CLUBS), new Card(13, Card::CLUBS), new Card(1, Card::CLUBS), new Card(2, Card::CLUBS), new Card(3, Card::CLUBS), new Card(4, Card::CLUBS), new Card(5, Card::CLUBS) ))->success,
				true);
		asserts("Valid row of five with two odd jokers", 
				Group::validate(new Cards( new Card(9, Card::CLUBS), new Card(0, Card::WILD), new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS) ))->success,
				true);
		asserts("Valid row of six with three odd jokers", 
				Group::validate(new Cards( new Card(9, Card::CLUBS), new Card(0, Card::WILD), new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS), new Card(0, Card::WILD) ))->success,
				true);
		asserts("Valid row over ace rewing and odd jokers", 
				Group::validate(new Cards( new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(2, Card::CLUBS) ))->success,
				true);
		asserts("Valid complex row of many over ace with three jokers, complete circle", 
				Group::validate(new Cards( new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(2, Card::CLUBS), new Card(3, Card::CLUBS), new Card(4, Card::CLUBS), new Card(5, Card::CLUBS), new Card(0, Card::WILD), new Card(7, Card::CLUBS), new Card(8, Card::CLUBS), new Card(9, Card::CLUBS), new Card(10, Card::CLUBS), new Card(11, Card::CLUBS) ))->success,
				true);
		asserts("Invalid row of three with two jokers", 
				Group::validate(new Cards( new Card(0, Card::WILD), new Card(11, Card::SPADES), new Card(0, Card::WILD) ))->success,
				false);
		asserts("Invalid row over ace rewing and joker", 
				Group::validate(new Cards( new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(1, Card::CLUBS) ))->success,
				false);
	}

}
