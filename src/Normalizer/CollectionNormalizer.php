<?php

namespace Zangra\Component\Odoo\ORM\Normalizer;

use Zangra\Component\Odoo\DBAL\Expression\ExpressionBuilder;
use Zangra\Component\Odoo\ORM\Model\Collection;
use ProxyManager\Proxy\GhostObjectInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

class CollectionNormalizer extends AbstractNormalizer
{
    /**
     * @var ExpressionBuilder
     */
    private $expressionBuilder;

    public function __construct()
    {
        $this->expressionBuilder = new ExpressionBuilder();
    }

    /**
     * {@inheritdoc}
     *
     * @param Collection  $collection
     * @param string|null $format
     */
    public function normalize($collection, $format = null, array $context = []): array
    {
        $storedIds = $collection->getStoredIds();
        $newStoredIds = [];
        $commands = [];

        foreach ($collection as $record) {
            $recordId = $record->getId();

            if (!$recordId) {
                if ($record instanceof GhostObjectInterface && !$record->isProxyInitialized()) {
                    continue;
                }

                $commands[] = $this->expressionBuilder->createRecord($this->getRecordData($record));
                continue;
            }

            $newStoredIds[] = $recordId;

            if (in_array($recordId, $storedIds, true)) {
                if ($record instanceof GhostObjectInterface && !$record->isProxyInitialized()) {
                    continue;
                }

                $commands[] = $this->expressionBuilder->updateRecord($recordId, $this->getRecordData($record));
                continue;
            }

            $commands[] = $this->expressionBuilder->addRecord($recordId);
        }

        foreach ($storedIds as $storedRecordId) {
            if (!in_array($storedRecordId, $newStoredIds, true)) {
                $commands[] = $this->expressionBuilder->deleteRecord($storedRecordId);
            }
        }

        return $commands;
    }

    /**
     * @internal
     *
     * @param mixed $record
     *
     * @throws SerializerException when normalization failed
     */
    private function getRecordData($record): array
    {
        if (is_object($record)) {
            $record = $this->normalizeObject($record);
        }

        if (array_key_exists('id', $record)) {
            unset($record['id']);
        }

        return $record;
    }

    /**
     * @param mixed       $data
     * @param string|null $format
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof Collection;
    }

    /**
     * @param array|false $data
     * @param string      $type
     * @param string|null $format
     */
    public function denormalize($data, $type, $format = null, array $context = []): Collection
    {
        return new Collection(is_array($data) ? $data : [$data]);
    }

    /**
     * @param mixed       $data
     * @param string      $type
     * @param string|null $format
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return $this
            ->getClass($type)
            ->implementsInterface(Collection::class);
    }
}
