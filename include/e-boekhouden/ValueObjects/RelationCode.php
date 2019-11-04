<?php

/**
 * Class RelationCode
 * @package eBoekhouden\ValueObjects
 */
class RelationCode {

    // Private variables
    private $relationCode;

    /**
     * RelationCode constructor.
     * @param $relationCode
     * @throws \Exception
     */
    public function __construct($relationCode)
    {
        if(strlen($relationCode) < 16 || $relationCode === null) {
            $this->relationCode = $relationCode;
            return;
        }
        throw new \Exception("Relation code may have a string length of maximal 15 characters", 105);
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        if($this->relationCode == '') {
            return null;
        }
        return $this->relationCode;
    }
}