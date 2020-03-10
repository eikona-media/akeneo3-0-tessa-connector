<?php
/**
 * TessaAttributeHydrator.php
 *
 * @author      Timo Müller <t.mueller@eikona-media.de>
 * @copyright   2019 EIKONA Media (https://eikona-media.de)
 */

namespace Eikona\Tessa\ReferenceDataAttributeBundle\Attribute;

use Akeneo\ReferenceEntity\Domain\Model\Attribute\AbstractAttribute;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeAllowedExtensions;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeCode;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeIdentifier;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeIsRequired;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeOrder;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeValuePerChannel;
use Akeneo\ReferenceEntity\Domain\Model\Attribute\AttributeValuePerLocale;
use Akeneo\ReferenceEntity\Domain\Model\LabelCollection;
use Akeneo\ReferenceEntity\Domain\Model\ReferenceEntity\ReferenceEntityIdentifier;
use Akeneo\ReferenceEntity\Infrastructure\Persistence\Sql\Attribute\Hydrator\AbstractAttributeHydrator;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Eikona\Tessa\ReferenceDataAttributeBundle\Attribute\Property\MaxAssets\AttributeTessaMaxAssets;

class TessaAttributeHydrator extends AbstractAttributeHydrator
{

    protected function getExpectedProperties(): array
    {
        return [
            'identifier',
            'reference_entity_identifier',
            'code',
            'labels',
            'attribute_order',
            'is_required',
            'value_per_locale',
            'value_per_channel',
            'attribute_type',
            'max_assets',
            'allowed_extensions',
        ];
    }

    protected function convertAdditionalProperties(AbstractPlatform $platform, array $row): array
    {
        $row['max_assets'] = Type::getType(Type::INTEGER)->convertToPhpValue(
            $row['additional_properties']['max_assets'], $platform
        );

        $row['allowed_extensions'] = $row['additional_properties']['allowed_extensions'];

        return $row;
    }

    protected function hydrateAttribute(array $row): AbstractAttribute
    {
        $maxAssets = AttributeTessaMaxAssets::NO_LIMIT === $row['max_assets']
            ? AttributeTessaMaxAssets::noLimit()
            : AttributeTessaMaxAssets::fromInteger($row['max_assets']);

        return TessaAttribute::createTessa(
            AttributeIdentifier::fromString($row['identifier']),
            ReferenceEntityIdentifier::fromString($row['reference_entity_identifier']),
            AttributeCode::fromString($row['code']),
            LabelCollection::fromArray($row['labels']),
            AttributeOrder::fromInteger($row['attribute_order']),
            AttributeIsRequired::fromBoolean($row['is_required']),
            AttributeValuePerChannel::fromBoolean($row['value_per_channel']),
            AttributeValuePerLocale::fromBoolean($row['value_per_locale']),
            $maxAssets,
            AttributeAllowedExtensions::fromList($row['allowed_extensions'])
        );
    }

    public function supports(array $result): bool
    {
        return isset($result['attribute_type']) && TessaAttribute::ATTRIBUTE_TYPE === $result['attribute_type'];
    }
}
