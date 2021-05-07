<?php

class Card implements \JsonSerializable {
	public $value = null;
	public $type = null;
	private $id = null;
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

	public function getId() {
		return $this->id;
	}

  	public function __toString() {
    	return $this->id;
  	}

    public function jsonSerialize() {
        return (object) [
        	'id' => $this->id,
        	'value' => $this->value,
        	'type' => $this->type
    	] ;
    }

}
