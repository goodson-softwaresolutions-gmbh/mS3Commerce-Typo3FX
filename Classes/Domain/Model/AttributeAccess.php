<?php


namespace Ms3\Ms3CommerceFx\Domain\Model;


use Ms3\Ms3CommerceFx\Domain\Repository\AttributeRepository;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;

class AttributeAccess implements \ArrayAccess
{
    /** @var AttributeRepository */
    private $repo;
    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== null;
    }

    public function offsetGet($offset)
    {
        return $this->repo->getAttributeBySaneName(GeneralUtilities::sanitizeFluidAccessName($offset));
    }

    public function offsetSet($offset, $value)
    {
        // not implemented
    }

    public function offsetUnset($offset)
    {
        // not implemented
    }
}