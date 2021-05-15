<?php

class Table implements \ArrayAccess, \Iterator, \Countable
{
    private $rows = [];
    private $idx = 0;

    public function __construct() {
    }

    public function getGroupsAsArray() : array {
        $ret = [];
        foreach ($this->rows as $set) {
             $ret[] = $set->getCardIds();
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

    public function areAllSetsValid() : bool {
        //var_dump('xxxxxx', $this);
        foreach ($this->rows as $set) {
            var_dump('areAllSetsValid', $set, Group::validate($set));
            if (!Group::validate($set)) {
                return false;
            }
        }
        return true;
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
