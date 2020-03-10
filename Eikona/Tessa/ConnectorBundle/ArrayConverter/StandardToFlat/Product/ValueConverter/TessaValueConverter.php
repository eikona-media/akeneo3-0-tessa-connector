<?php
    /**
     * TessaValueConverter.php
     *
     * @author    Matthias Mahler <m.mahler@eikona.de>
     * @copyright 2017 Eikona AG (http://www.eikona.de)
     */

    namespace Eikona\Tessa\ConnectorBundle\ArrayConverter\StandardToFlat\Product\ValueConverter;

    use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\StandardToFlat\Product\ValueConverter\AbstractValueConverter;
    use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\StandardToFlat\Product\ValueConverter\ValueConverterInterface;
    use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\FlatToStandard\AttributeColumnsResolver;
    use Eikona\Tessa\ConnectorBundle\Security\AuthGuard;
    use Eikona\Tessa\ConnectorBundle\Tessa;

    class TessaValueConverter extends AbstractValueConverter implements ValueConverterInterface
    {
        /**
         *
         * @var array
         */
        private $tessa;

        /**
         * @var AuthGuard
         */
        private $authGuard;

        public function __construct(
                AttributeColumnsResolver $columnsResolver,
                Tessa $tessa,
                AuthGuard $authGuard
        ) {
            $this->tessa = $tessa;
            $this->authGuard = $authGuard;
            parent::__construct($columnsResolver, ['eikona_catalog_tessa']);
        }

        /**
         * Converts a value
         *
         * @param string $attributeCode
         * @param mixed  $data
         *
         * @return array
         */
        public function convert($attributeCode, $data)
        {
            $convertedItem = [];

            foreach ($data as $value) {
                $flatName = $this->columnsResolver->resolveFlatAttributeName(
                        $attributeCode,
                        $value['locale'],
                        $value['scope']
                );

                $convertedItem[$flatName] = $this->convertAssetIdsToUrls(
                        $value['data'],
                        $value['scope']
                );
            }

            return $convertedItem;
        }

        /**
         * @param string $data
         * @param string $scope
         *
         * @return string
         */
        private function convertAssetIdsToUrls($data, $scope)
        {
            if (trim($data) === '') {
                return '';
            }

            $apiKey = $this->tessa->getAccessToken();

            $arr_exploded = explode(',', $data);
            $arr_exploded = array_map(
                    function ($assetId) use ($apiKey, $scope) {
                        return $this->tessa->getBaseUrl(
                                )."/ui/download.php?asset_system_id=$assetId&kanal=$scope&key={$this->authGuard->getDownloadAuthToken($assetId, 'download')}";
                    },
                    $arr_exploded
            );

            return implode(';', $arr_exploded);
        }
    }
