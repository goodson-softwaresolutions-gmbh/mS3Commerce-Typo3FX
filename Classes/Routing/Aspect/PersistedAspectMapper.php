<?php


namespace Ms3\Ms3CommerceFx\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\PersistedMappableAspectInterface;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

class PersistedAspectMapper implements StaticMappableAspectInterface, PersistedMappableAspectInterface
{

    /**
     * @inheritDoc
     */
    public function generate(string $value): ?string
    {
        return urlencode($value);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $value): ?string
    {
        return urldecode($value);
    }
}