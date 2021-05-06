<?php

// Group is basically stack of validated Cards with validation extension, but it is a little bit dirty...
class Group extends Cards {
	private $set = [];

	private function __construct(Cards $newSet) {
		$this->set = $newSet;
	}

	public static function createSet(Cards $newSet) {
		if (self::validate($newSet)) {
			return new Group($newSet);
		} else {
			return false;
		}
	}

	public function getCardIds() {
		$ret = [];
		foreach ($this->set as $card) {
			$ret[] = $card->getId();
		}
		return $ret;
	}

	public static function validate(Cards $newSet) {
		dbg('validating', $newSet);

		// not enough cards
		if (sizeOf($newSet)<3) return false;

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
			(sizeOf($valuesUnique) === 1 || (sizeOf($valuesUnique) === 2 && in_array(0, $valuesUnique))) && 
			sizeOf($newSet)<5) {
			return true;
		}

		// cards are not of one type only (or one type + jokers)
		if (!(sizeOf($typesUnique) === 1 || (sizeOf($typesUnique) === 2 && in_array(Card::WILD, $typesUnique)))) {
			return false;
		}

		// too many jokers for three cards
		if (in_array(0, $valuesUnique) && sizeOf($values) === 3 && sizeOf($values) !== sizeOf($valuesUnique)) {
			return false;
		}

		// iterate through set and try to say if it is meaningful
		for ($i = 1; $i < sizeOf($values); $i++) {
			$prev = $values[$i-1];
			$curr = $values[$i];

			dbg('iteration details', $values, $i, $prev, $curr);
//die("dsdsdsddsdssdxx");
			// two following jokers are never allowed
			if ($prev === 0 && $curr === 0) {
				return false;
			}

			// standard increase is always ok
			if ($prev+1 === $curr) {
				continue;
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
			return false;
		}
		return true;
	}	

	public static function tests() {

		asserts("Not enough cards", 
				Group::validate(new Cards( new Card(1, Card::SPADES), new Card(3, Card::SPADES) )), 
				false);
		asserts("Valid three of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::DIAMONDS) )), 
				true);
		asserts("Valid three of a type with Joker", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(0, Card::WILD) )), 
				true);
		asserts("Valid four of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::DIAMONDS), new Card(10, Card::HEARTS) )), 
				true);
		asserts("Invalid five of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::DIAMONDS), new Card(10, Card::HEARTS), new Card(0, Card::WILD) )), 
				false);
		asserts("Invalid three of a type", 
				Group::validate(new Cards( new Card(10, Card::CLUBS), new Card(10, Card::SPADES), new Card(10, Card::CLUBS) )), 
				false);
		asserts("Valid row of three", 
				Group::validate(new Cards( new Card(1, Card::SPADES), new Card(2, Card::SPADES), new Card(3, Card::SPADES) )), 
				true);
		asserts("Valid row of four with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::SPADES), new Card(0, Card::WILD), new Card(7, Card::SPADES) )), 
				true);
		asserts("Invalid row of four with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::CLUBS), new Card(0, Card::WILD), new Card(7, Card::SPADES) )), 
				false);
		asserts("Valid row of three with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::SPADES), new Card(0, Card::WILD) )), 
				true);
		asserts("Invalid row with two following jokers", 
				Group::validate(new Cards( new Card(3, Card::SPADES), new Card(4, Card::SPADES), new Card(0, Card::WILD), new Card(0, Card::WILD), new Card(7, Card::SPADES) )), 
				false);
		asserts("Invalid row of three with skips over the ace", 
				Group::validate(new Cards( new Card(12, Card::CLUBS), new Card(1, Card::CLUBS), new Card(3, Card::CLUBS) )), 
				false);
		asserts("Valid row of three over the ace", 
				Group::validate(new Cards( new Card(13, Card::CLUBS), new Card(1, Card::CLUBS), new Card(2, Card::CLUBS) )), 
				true);
		asserts("Valid row of three over the ace with joker", 
				Group::validate(new Cards( new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(2, Card::CLUBS) )), 
				true);
		asserts("Invalid row of four with joker", 
				Group::validate(new Cards( new Card(4, Card::SPADES), new Card(5, Card::SPADES), new Card(0, Card::WILD), new Card(6, Card::SPADES) )), 
				false);
		asserts("Valid row of three over the ace", 
				Group::validate(new Cards( new Card(12, Card::CLUBS), new Card(13, Card::CLUBS), new Card(1, Card::CLUBS) )), 
				true);
		asserts("Valid row of three over the ace with joker", 
				Group::validate(new Cards( new Card(12, Card::CLUBS), new Card(0, Card::WILD), new Card(1, Card::CLUBS) )), 
				true);
		asserts("Valid complex row of many with rewind without jokers, incomplete circle", 
				Group::validate(new Cards( new Card(11, Card::CLUBS), new Card(12, Card::CLUBS), new Card(13, Card::CLUBS), new Card(1, Card::CLUBS), new Card(2, Card::CLUBS), new Card(3, Card::CLUBS), new Card(4, Card::CLUBS), new Card(5, Card::CLUBS) )),
				true);
		asserts("Valid row of five with two odd jokers", 
				Group::validate(new Cards( new Card(9, Card::CLUBS), new Card(0, Card::WILD), new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS) )),
				true);
		asserts("Valid row of six with three odd jokers", 
				Group::validate(new Cards( new Card(9, Card::CLUBS), new Card(0, Card::WILD), new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS), new Card(0, Card::WILD) )),
				true);
		asserts("Valid row over ace rewing and odd jokers", 
				Group::validate(new Cards( new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(2, Card::CLUBS) )),
				true);
		asserts("Valid complex row of many over ace with three jokers, complete circle", 
				Group::validate(new Cards( new Card(11, Card::CLUBS), new Card(0, Card::WILD), new Card(13, Card::CLUBS), new Card(0, Card::WILD), new Card(2, Card::CLUBS), new Card(3, Card::CLUBS), new Card(4, Card::CLUBS), new Card(5, Card::CLUBS), new Card(0, Card::WILD), new Card(7, Card::CLUBS), new Card(8, Card::CLUBS), new Card(9, Card::CLUBS), new Card(10, Card::CLUBS), new Card(11, Card::CLUBS) )),
				true);
		asserts("Invalid row of three with two jokers", 
				Group::validate(new Cards( new Card(0, Card::WILD), new Card(11, Card::SPADES), new Card(0, Card::WILD) )),
				false);

	}

}
