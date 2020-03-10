<?php
/**
 * TessaQueueNormalizer.php
 *
 * @author      Timo Müller <t.mueller@eikona-media.de>
 * @copyright   2019 EIKONA Media (https://eikona-media.de)
 */

namespace Eikona\Tessa\ConnectorBundle\Normalizer;

use Akeneo\Channel\Component\Model\ChannelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\GroupInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Tool\Component\Classification\Model\CategoryInterface;
use Eikona\Tessa\ConnectorBundle\Tessa;
use Eikona\Tessa\ConnectorBundle\Utilities\IdPrefixer;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TessaQueueNormalizer
{
    /** @var IdPrefixer */
    protected $idPrefixer;

    /** @var NormalizerInterface */
    protected $standardNormalizer;

    /** @var NormalizerInterface */
    protected $productNormalizer;

    /**
     * @param IdPrefixer $idPrefixer
     * @param NormalizerInterface $standardNormalizer
     * @param NormalizerInterface $productNormalizer
     */
    public function __construct(
        IdPrefixer $idPrefixer,
        NormalizerInterface $standardNormalizer,
        NormalizerInterface $productNormalizer
    )
    {
        $this->idPrefixer = $idPrefixer;
        $this->standardNormalizer = $standardNormalizer;
        $this->productNormalizer = $productNormalizer;
    }

    public function normalizeModification($entity)
    {
        if ($entity instanceof ProductInterface) {
            return $this->normalizeProductModification($entity);
        } elseif ($entity instanceof ProductModelInterface) {
            return $this->normalizeProductModelModification($entity);
        } elseif ($entity instanceof CategoryInterface) {
            return $this->normalizeCategoryModification($entity);
        } elseif ($entity instanceof ChannelInterface) {
            return $this->normalizeChannelModification($entity);
        } elseif ($entity instanceof GroupInterface) {
            return $this->normalizeGroupModification($entity);
        } else {
            throw new InvalidArgumentException('Invalid type for $entity');
        }
    }

    public function normalizeDeletion(int $id, string $identifier, string $type)
    {
        if ($type === Tessa::TYPE_PRODUCT) {
            return $this->normalizeProductDeletion($id, $identifier);
        } elseif ($type === Tessa::TYPE_PRODUCT_MODEL) {
            return $this->normalizeProductModelDeletion($id, $identifier);
        } elseif ($type === Tessa::TYPE_CATEGORY) {
            return $this->normalizeCategoryDeletion($id, $identifier);
        } elseif ($type === Tessa::TYPE_CHANNEL) {
            return $this->normalizeChannelDeletion($id, $identifier);
        } elseif ($type === Tessa::TYPE_GROUP) {
            return $this->normalizeGroupDeletion($id, $identifier);
        } else {
            throw new InvalidArgumentException('Invalid type for $entity');
        }
    }

    protected function normalizeProductModification(ProductInterface $entity)
    {
        return [
            'id' => $this->idPrefixer->getPrefixedId($entity),
            'code' => $entity->getIdentifier(),
            'type' => Tessa::TYPE_PRODUCT,
            'context' => Tessa::CONTEXT_UPDATE,
            'data' => $this->normalize($entity),
        ];
    }

    protected function normalizeProductDeletion(int $id, string $identifier)
    {
        return [
            'id' => $this->idPrefixer->prefixProductId($identifier),
            'code' => $identifier,
            'type' => Tessa::TYPE_PRODUCT,
            'context' => Tessa::CONTEXT_DELETE,
            'resourceName' => Tessa::RESOURCE_NAME_PRODUCT,
        ];
    }

    protected function normalizeProductModelModification(ProductModelInterface $entity)
    {
        return [
            'id' => $this->idPrefixer->getPrefixedId($entity),
            'code' => $entity->getCode(),
            'type' => Tessa::TYPE_PRODUCT,
            'context' => Tessa::CONTEXT_UPDATE,
            'data' => $this->normalize($entity),
        ];
    }

    protected function normalizeProductModelDeletion(int $id, string $code)
    {
        return [
            'id' => $this->idPrefixer->prefixProductModelId($id),
            'code' => $code,
            'type' => Tessa::TYPE_PRODUCT, // Tessa unterscheidet nicht zwischen Product und ProductModel
            'context' => Tessa::CONTEXT_DELETE,
            'resourceName' => Tessa::RESOURCE_NAME_PRODUCT_MODEL,
        ];
    }

    protected function normalizeCategoryModification(CategoryInterface $entity)
    {
        return [
            'id' => $entity->getId(),
            'code' => $entity->getCode(),
            'type' => Tessa::TYPE_CATEGORY,
            'context' => Tessa::CONTEXT_UPDATE,
            'data' => $this->normalize($entity),
        ];
    }

    protected function normalizeCategoryDeletion(int $id, string $code)
    {
        return [
            'id' => $id,
            'code' => $code,
            'type' => Tessa::TYPE_CATEGORY,
            'context' => Tessa::CONTEXT_DELETE,
            'resourceName' => Tessa::RESOURCE_NAME_CATEGORY,
        ];
    }

    protected function normalizeChannelModification(ChannelInterface $entity)
    {
        return [
            'id' => $entity->getId(),
            'code' => $entity->getCode(),
            'type' => Tessa::TYPE_CHANNEL,
            'context' => Tessa::CONTEXT_UPDATE,
            'data' => $this->normalize($entity),
        ];
    }

    protected function normalizeChannelDeletion(int $id, string $code)
    {
        return [
            'id' => $id,
            'code' => $code,
            'type' => Tessa::TYPE_CHANNEL,
            'context' => Tessa::CONTEXT_DELETE,
            'resourceName' => Tessa::RESOURCE_NAME_CHANNEL,
        ];
    }

    protected function normalizeGroupModification(GroupInterface $entity)
    {
        return [
            'id' => $entity->getId(),
            'code' => $entity->getCode(),
            'type' => Tessa::TYPE_GROUP,
            'context' => Tessa::CONTEXT_UPDATE,
            'data' => $this->normalize($entity),
        ];
    }

    protected function normalizeGroupDeletion(int $id, string $code)
    {
        return [
            'id' => $id,
            'code' => $code,
            'type' => Tessa::TYPE_GROUP,
            'context' => Tessa::CONTEXT_DELETE,
            'resourceName' => Tessa::RESOURCE_NAME_GROUP,
        ];
    }

    /**
     * @param $entity
     * @return array|bool|float|int|string
     */
    protected function normalize($entity)
    {
        if ($entity instanceof ProductInterface) {
            return $this->productNormalizer->normalize($entity, 'standard');
        }

        return $this->standardNormalizer->normalize($entity, 'standard');
    }
}
