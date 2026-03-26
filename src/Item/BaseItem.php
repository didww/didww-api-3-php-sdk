<?php

namespace Didww\Item;

use Didww\Traits\HasEnumAttributes;
use Didww\Traits\HasSafeAttributes;

abstract class BaseItem extends \Swis\JsonApi\Client\Item
{
    use HasSafeAttributes;
    use HasEnumAttributes;

    protected array $persistedAttributes = [];

    protected array $persistedRelationships = [];

    public static function build(string $uuid, array $attributes = [])
    {
        $item = new static();
        $item->fill($attributes);
        $item->setId($uuid);

        return $item;
    }

    public static function getRepository()
    {
        $documentFactory = new \Swis\JsonApi\Client\DocumentFactory();
        $documentClient = \Didww\Configuration::getDocumentClient();
        $repository = new \Didww\Repository($documentClient, $documentFactory);
        $repository->setEndpoint(static::getEndpoint());

        return $repository;
    }

    abstract public static function getEndpoint(): string;

    public function toJsonApiArray(): array
    {
        $attributes = parent::toJsonApiArray();
        $whitelist = $this->getWhiteListAttributesKeys();
        if (is_array($whitelist) && isset($attributes['attributes'])) {
            // keep only whitelisted attributes
            $attributes['attributes'] = array_intersect_key($attributes['attributes'], array_flip($whitelist));
        }

        return $attributes;
    }

    public function toJsonApiPatchArray(): array
    {
        $current = $this->toJsonApiArray();
        $dirtyAttributes = $this->extractDirtyAttributes($current['attributes'] ?? []);
        $dirtyRelationships = $this->extractDirtyRelationships(
            $this->extractRelationshipData($current['relationships'] ?? [])
        );

        $patchData = [
            'type' => $current['type'],
        ];

        if (isset($current['id'])) {
            $patchData['id'] = $current['id'];
        }

        if ([] !== $dirtyAttributes) {
            $patchData['attributes'] = $dirtyAttributes;
        }

        if ([] !== $dirtyRelationships) {
            $patchData['relationships'] = array_map(
                static fn ($relationshipData) => ['data' => $relationshipData],
                $dirtyRelationships
            );
        }

        return $patchData;
    }

    public function syncPersistedState(): void
    {
        $current = $this->toJsonApiArray();
        $this->persistedAttributes = $current['attributes'] ?? [];
        $this->persistedRelationships = $this->extractRelationshipData($current['relationships'] ?? []);
    }

    protected function getWhiteListAttributesKeys()
    {
        return null;
    }

    private function extractDirtyAttributes(array $currentAttributes): array
    {
        $dirty = [];
        foreach ($currentAttributes as $name => $value) {
            if (!array_key_exists($name, $this->persistedAttributes) || $this->persistedAttributes[$name] !== $value) {
                $dirty[$name] = $value;
            }
        }

        return $dirty;
    }

    private function extractDirtyRelationships(array $currentRelationships): array
    {
        $dirty = [];
        foreach ($currentRelationships as $name => $value) {
            if (!array_key_exists($name, $this->persistedRelationships) || $this->persistedRelationships[$name] !== $value) {
                $dirty[$name] = $value;
            }
        }

        return $dirty;
    }

    private function extractRelationshipData(array $relationships): array
    {
        $data = [];
        foreach ($relationships as $name => $relationship) {
            if (is_array($relationship) && array_key_exists('data', $relationship)) {
                $data[$name] = $relationship['data'];
            }
        }

        return $data;
    }
}
