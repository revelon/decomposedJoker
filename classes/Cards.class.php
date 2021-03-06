<?php

class Cards implements \ArrayAccess, \Iterator, \Countable
{
    protected array $rows = [];
    protected int $idx = 0;

    public function __construct(Card ...$var) {
		$this->rows = (array) $var;
    }

    public function getCardByType(string $type, int $value) : ?Card {
    	foreach ($this->rows as $card) {
    		if ($card->type === $type && $card->value === $value) {
    			return $card;
    		}
    	}
    }

    public function getCardsByType(string $type, int $value) : ?array {
        $ret = [];
        foreach ($this->rows as $card) {
            if ($card->type === $type && $card->value === $value) {
                $ret[] = $card;
            }
        }
        return $ret;
    }

    public function areOnlyJokersPresent() : bool {
        foreach($this->rows as $card) {
            if ($card->type !== Card::WILD) {
                return false;
            }
        }
        return true;
    }

    public function getCardIds() : array {
		$ids = [];
		foreach ($this->rows as $card) {
			$ids[] = $card->getId();
		}
    	return $ids;
    }

    public function shuffle() : void {
    	shuffle($this->rows);
    }

    public function popCard() : Card {
    	return array_pop($this->rows);
    }

    public function pushCard(Card $card) : void {
    	array_push($this->rows, $card);
    }

    public function unshiftCard(Card $card) : void {
        array_unshift($this->rows, $card);
    }

    // return difference between this set a given array of selected cards
    /*public function getCardDiff(Cards $someCards) : array {
    	return array_diff($this->rows, $someCards->rows);
    }*/

    public function sortCards() : void {
        usort($this->rows, function (Card $a, Card $b) : int {
            if ($a->getSortingValue() < $b->getSortingValue()) {
                return -1;
            } else if ($a->getSortingValue() > $b->getSortingValue()) {
                return 1;
            } else {
                return 0;
            }
        });
    }

    public function getCards() : array {
        return $this->rows;
    }



    // ArrayAccess interface

    // Used when adding or updating an array value
    public function offsetSet($offset, $value)
    {
        if ($offset === null)
        {
            $this->rows[] = $value;
        }
        else if ($value instanceof Card)
        {
            $this->rows[$offset] = $value;
        }
    }

    // Used when isset() is called
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }

    // Used when unset() is called
    public function offsetUnset($offset)
    {
        unset($this->rows[$offset]);
    }

    // Used to retrieve a value using indexing
    public function offsetGet($offset)
    {
        return $this->rows[$offset];
    }

    // Iterator interface

    public function rewind()
    {
        $this->idx = 0;
    }

    public function valid()
    {
        return $this->idx < count($this->rows);
    }

    public function current()
    {
        return $this->rows[$this->idx];
    }

    public function key()
    {
        return $this->idx;
    }

    public function next()
    {
        $this->idx++;
    }

    // Countable interface

    public function count()
    {
        return count($this->rows);
    }

}
