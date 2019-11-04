<?php

/**
 * Class MutationId
 * @package eBoekhouden\ValueObjects
 */
class MutationId {

    // Private variables
    private $mutationId;

    /**
     * MutationId constructor.
     * @param $mutationId
     * @throws \Exception
     */
    public function __construct($mutationId = null)
    {
        if(is_int($mutationId) || $mutationId === null) {
            $this->mutationId = $mutationId;
            return;
        }
        throw new \Exception("Mutation Id must be integer or null", 109);
    }

    /**
     * @return mixed
     */
    public function toInt()
    {
        if($this->mutationId == 0) {
            return null;
        }
        return (int) $this->mutationId;
    }
}