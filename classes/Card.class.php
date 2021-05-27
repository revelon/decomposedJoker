<?php

class Card implements \JsonSerializable {
	public ?int $value = null;
	public ?string $type = null;
	private ?string $id = null;
	const SPADES = 'spades';
	const CLUBS = 'clubs';
	const HEARTS = 'hearts';
	const DIAMONDS = 'diamonds';
	const WILD = 'wildcard';

	function __construct(int $value, string $type, string $id = null) {
		$this->value = $value;
		$this->type = $type;
		$this->id = $id ? $id : bin2hex(random_bytes(8));
	}

	public function getId() : ?string {
		return $this->id;
	}

  	public function __toString() : string {
    	return $this->id;
  	}

    public function jsonSerialize() : object {
        return (object) [
        	'id' => $this->id,
        	'value' => $this->value,
        	'type' => $this->type
    	] ;
    }

    public function getSortingValue() : int {
    	$offset = ['spades' => 0, 'diamonds' => 20, 'clubs' => 40, 'hearts' => 60, 'wildcard' => 80];
    	return (int) $this->value + $offset[$this->type];
    }

}
