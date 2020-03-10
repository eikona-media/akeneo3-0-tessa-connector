<?php
/**
 * TessaAttributeSetter.php
 *
 * @author    Matthias Mahler <m.mahler@eikona.de>
 * @copyright 2017 Eikona AG (http://www.eikona.de)
 */

namespace Eikona\Tessa\ConnectorBundle\Updater;

use Akeneo\Pim\Enrichment\Component\Product\Builder\EntityWithValuesBuilderInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Updater\Setter\AbstractAttributeSetter;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;

class TessaAttributeSetter extends AbstractAttributeSetter
{
    /**
     * @param EntityWithValuesBuilderInterface $entityWithValuesBuilder
     * @param array $supportedTypes
     */
    public function __construct(
        EntityWithValuesBuilderInterface $entityWithValuesBuilder,
        array $supportedTypes
    )
    {
        parent::__construct($entityWithValuesBuilder);
        $this->supportedTypes = $supportedTypes;
    }

    /**
     * Set attribute data
     *
     * @param EntityWithValuesInterface $entityWithValues
     * @param AttributeInterface $attribute The attribute of the product to modify
     * @param mixed $data The data to set
     * @param array $options Options passed to the setter
     */
    public function setAttributeData(
        EntityWithValuesInterface $entityWithValues,
        AttributeInterface $attribute,
        $data,
        array $options = []
    )
    {
        $options = $this->resolver->resolve($options);
        $this->setData($entityWithValues, $attribute, $data, $options['locale'], $options['scope']);
    }

    /**
     * Set the data into the product value
     *
     * @param EntityWithValuesInterface $product
     * @param AttributeInterface $attribute
     * @param mixed $data
     * @param string $locale
     * @param string $scope
     */
    protected function setData(
        EntityWithValuesInterface $product,
        AttributeInterface $attribute,
        $data,
        $locale,
        $scope
    )
    {
        $this->entityWithValuesBuilder->addOrReplaceValue(
            $product,
            $attribute,
            $locale,
            $scope,
            $data
        );
    }
}
