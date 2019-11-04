<?php

/**
 * Class RelationSearch
 * @package eBoekhouden\ValueObjects
 */
class RelationSearch {

    // Private variables
    private $relationSearch;

    /**
     * RelationSearch constructor.
     * @param $accountLedgerCode
     * @throws \Exception
     */
    public function __construct($relationSearch = null)
    {
        if(strlen($relationSearch) < 255 && strlen($relationSearch) > 0) {
            $this->relationSearch = $relationSearch;
            return;
        }

        throw new \Exception("Relation search query may not exceed the length of 255 characters", 111);
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        if($this->relationSearch === null) {
            return null;
        }
        return $this->relationSearch;
    }
}