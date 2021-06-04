<?php

class Table implements \ArrayAccess, \Iterator, \Countable
{
    private array $rows = [];
    private int $idx = 0;

    public function __construct() {
    }

    public function getGroups() : array {
        $ret = [];
        foreach ($this->rows as $set) {
             $ret[] = ['id' => $set->id, 'cards' => $set->getCardIds()];
         }
         return $ret;
    }

    public function getCardIds() : array {
        $ids = [];
        foreach ($this->rows as $set) {
            $ids = array_merge($ids, $set->getCardIds());
        }
        return $ids;
    }

    public function areAllSetsValid() : ValidationResult {
        foreach ($this->rows as $set) {
            dbg('areAllSetsValid', $set, Group::validate($set));
            $result = Group::validate($set);
            if (!$result->success) {
                $result->groupId = $set->id; // set invalid set id
                return $result;
            }
        }
        return ValidationResult::get(true);
    }

    public function isCardPresent(string $id) : bool {
        $ids = $this->getCardIds();
        return (in_array($id, $ids));
    }


    // ArrayAccess interface

    // Used when adding or updating an array value
    public function offsetSet($offset, $value)
    {
        if ($offset === null)
        {
            $this->rows[] = $value;
        }
        else if ($value instanceof Group)
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
