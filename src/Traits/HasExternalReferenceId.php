<?php

namespace Didww\Traits;

/**
 * Shared external_reference_id attribute for resources that support it.
 */
trait HasExternalReferenceId
{
    public function getExternalReferenceId(): ?string
    {
        return $this->attribute('external_reference_id');
    }

    public function setExternalReferenceId(?string $externalReferenceId)
    {
        $this->attributes['external_reference_id'] = $externalReferenceId;
    }
}
