<?php

namespace Eikona\Tessa\ConnectorBundle;

use Eikona\Tessa\ConnectorBundle\Normalizer\TessaQueueNormalizer;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class Tessa
{
    public const TYPE_CATEGORY = 'category';
    public const TYPE_CHANNEL = 'channel';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_PRODUCT_MODEL = 'product_model';
    public const TYPE_GROUP = 'group';

    public const RESOURCE_NAME_CATEGORY = 'Category';
    public const RESOURCE_NAME_CHANNEL = 'Channel';
    public const RESOURCE_NAME_PRODUCT = 'Product';
    public const RESOURCE_NAME_PRODUCT_MODEL = 'ProductModel';
    public const RESOURCE_NAME_GROUP = 'Group';

    public const CONTEXT_UPDATE = 'Update';
    public const CONTEXT_DELETE = 'Deletion';

    /** @var string */
    protected $baseUrl;

    /** @var string */
    protected $uiUrl;

    /** @var string */
    protected $username;

    /** @var string */
    protected $accessToken;

    /** @var Kernel */
    protected $kernel;

    /** @var Logger */
    protected $logger;

    /** @var string */
    protected $systemIdentifier;

    /** @var int */
    protected $userId;

    /** @var bool */
    protected $syncInBackground;

    /** @var int */
    protected $chunkSize;

    /** @var TessaQueueNormalizer */
    protected $tessaQueueNormalizer;

    /**
     * Tessa constructor.
     *
     * @param ConfigManager $oroGlobal
     * @param Kernel|KernelInterface $kernel
     * @param Logger $logger
     * @param TessaQueueNormalizer $tessaQueueNormalizer
     */
    public function __construct(
        ConfigManager $oroGlobal,
        KernelInterface $kernel,
        Logger $logger,
        TessaQueueNormalizer $tessaQueueNormalizer
    )
    {
        $this->baseUrl = trim($oroGlobal->get('pim_eikona_tessa_connector.base_url'), ' /');
        $this->uiUrl = trim($oroGlobal->get('pim_eikona_tessa_connector.ui_url'), ' /');
        $this->username = trim($oroGlobal->get('pim_eikona_tessa_connector.username'));
        $this->accessToken = trim($oroGlobal->get('pim_eikona_tessa_connector.api_key'));
        $this->userId = (int)substr($this->accessToken, 0, strpos($this->accessToken, ':'));
        $this->systemIdentifier = trim($oroGlobal->get('pim_eikona_tessa_connector.system_identifier'));
        $this->syncInBackground = (bool)$oroGlobal->get('pim_eikona_tessa_connector.sync_in_background');
        $this->chunkSize = (int)$oroGlobal->get('pim_eikona_tessa_connector.chunk_size');
        $this->kernel = $kernel;
        $this->logger = $logger;
        $this->tessaQueueNormalizer = $tessaQueueNormalizer;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getUiUrl()
    {
        return $this->uiUrl;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getSystemIdentifier()
    {
        return $this->systemIdentifier;
    }

    /**
     * @return bool
     */
    public function isBackgroundSyncActive()
    {
        return $this->syncInBackground;
    }

    /**
     * @return int
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (!$this->baseUrl) {
            return false;
        }

        $ch = curl_init($this->baseUrl);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpcode < 400;
    }

    /**
     * @param $entity
     */
    public function notifySingleModification($entity)
    {
        $this->sendNotificationToTessa($this->tessaQueueNormalizer->normalizeModification($entity));
    }

    /**
     * @param array $entities
     */
    public function notifyBulkModification(array $entities)
    {
        $normalizedEntities = array_map(function ($entity) {
            return $this->tessaQueueNormalizer->normalizeModification($entity);
        }, $entities);

        $chunks = array_chunk($normalizedEntities, $this->getChunkSize());
        foreach ($chunks as $chunk) {
            $this->sendNotificationToTessa($chunk, true);
        }
    }

    /**
     * @param int $id
     * @param string $identifier
     * @param string $type
     */
    public function notifySingleDeletion(int $id, string $identifier, string $type)
    {
        $this->sendNotificationToTessa($this->tessaQueueNormalizer->normalizeDeletion($id, $identifier, $type));
    }

    /**
     * @param array $payload
     * @param bool $isBulk
     */
    protected function sendNotificationToTessa(array $payload, $isBulk = false)
    {
        if (empty($this->baseUrl)) {
            return;
        }

        $payload = json_encode($payload);

        $url = $this->baseUrl . '/dienste/akeneo/warteschlange.php';
        if ($isBulk) {
            $url .= '?bulk=1';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Tessa-Api-Token: ' . $this->getAccessToken(),
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->kernel->isDebug() ? 30 : 0);
        $result = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpcode !== 200) {
            $this->logger->error(sprintf(
                'Tessa request failed (http code %i): %s',
                $httpcode,
                $result
            ));
        }

        curl_close($ch);
    }
}
