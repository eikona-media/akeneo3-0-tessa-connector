<?php
/**
 * TessaNotificationListener.php.php
 *
 * @author      Timo Müller <t.mueller@eikona-media.de>
 * @copyright   2019 EIKONA Media (https://eikona-media.de)
 */

namespace Eikona\Tessa\ConnectorBundle\EventListener;

use Akeneo\Channel\Component\Model\ChannelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\GroupInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductModelRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Akeneo\Tool\Component\Classification\Model\CategoryInterface;
use Akeneo\Tool\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Doctrine\ORM\EntityManager;
use Eikona\Tessa\ConnectorBundle\Services\TessaNotificationQueueService;
use Eikona\Tessa\ConnectorBundle\Tessa;
use Symfony\Component\EventDispatcher\GenericEvent;

class TessaNotificationListener
{
    /** @var Tessa */
    protected $tessa;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ProductModelRepositoryInterface */
    protected $productModelRepository;

    /** @var array */
    protected $productModelStore = [];

    /** @var TessaNotificationQueueService */
    protected $tessaNotificationQueueService;

    /** @var UserContext */
    protected $userContext;

    /**
     * @param Tessa                           $tessa
     * @param EntityManager                   $entityManager
     * @param ProductRepositoryInterface      $productRepository
     * @param ProductModelRepositoryInterface $productModelRepository
     * @param TessaNotificationQueueService   $tessaNotificationQueueService
     * @param UserContext                     $userContext
     */
    public function __construct(
        Tessa $tessa,
        EntityManager $entityManager,
        ProductRepositoryInterface $productRepository,
        ProductModelRepositoryInterface $productModelRepository,
        TessaNotificationQueueService $tessaNotificationQueueService,
        UserContext $userContext
    )
    {
        $this->tessa = $tessa;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->productModelRepository = $productModelRepository;
        $this->tessaNotificationQueueService = $tessaNotificationQueueService;
        $this->userContext = $userContext;
    }

    /**
     * @param GenericEvent $event
     */
    public function onPostSave(GenericEvent $event)
    {
        if (!$this->shouldNotify()) {
            return;
        }

        $subject = $event->getSubject();

        if ($subject instanceof CategoryInterface || $subject instanceof ChannelInterface || $subject instanceof GroupInterface) {
            $this->tessa->notifySingleModification($subject);
            return;
        }

        if ($subject instanceof ProductInterface) {
            if ($this->tessa->isBackgroundSyncActive()) {
                $this->tessaNotificationQueueService->addToQueue(
                    $subject->getIdentifier(),
                    TessaNotificationQueueService::TYPE_PRODUCT,
                    TessaNotificationQueueService::ACTION_MODIFIED
                );
            } else {
                $this->tessa->notifySingleModification($subject);
            }
            return;
        }

        if ($subject instanceof ProductModelInterface) {
            if ($this->tessa->isBackgroundSyncActive()) {
                $this->tessaNotificationQueueService->addToQueue(
                    $subject->getCode(),
                    TessaNotificationQueueService::TYPE_PRODUCT_MODEL,
                    TessaNotificationQueueService::ACTION_MODIFIED
                );
            } else {
                $this->tessa->notifySingleModification($subject);
            }

            // Varianten und Produkte ebenfalls beachten

            /** @var ProductInterface $product */
            foreach ($subject->getProducts() as $product) {
                if ($this->tessa->isBackgroundSyncActive()) {
                    $this->tessaNotificationQueueService->addToQueue(
                        $product->getIdentifier(),
                        TessaNotificationQueueService::TYPE_PRODUCT,
                        TessaNotificationQueueService::ACTION_MODIFIED
                    );
                } else {
                    $this->tessa->notifySingleModification($product);
                }
            }

            /** @var ProductModelInterface $variant */
            foreach ($subject->getProductModels() as $variant) {
                if ($this->tessa->isBackgroundSyncActive()) {
                    $this->tessaNotificationQueueService->addToQueue(
                        $variant->getCode(),
                        TessaNotificationQueueService::TYPE_PRODUCT_MODEL,
                        TessaNotificationQueueService::ACTION_MODIFIED
                    );
                } else {
                    $this->tessa->notifySingleModification($variant);
                }
                /** @var ProductInterface $product */
                foreach ($subject->getProducts() as $product) {
                    if ($this->tessa->isBackgroundSyncActive()) {
                        $this->tessaNotificationQueueService->addToQueue(
                            $product->getIdentifier(),
                            TessaNotificationQueueService::TYPE_PRODUCT,
                            TessaNotificationQueueService::ACTION_MODIFIED
                        );
                    } else {
                        $this->tessa->notifySingleModification($product);
                    }
                }
            }
            return;
        }
    }

    /**
     * In PreRemove werden Informationen für PostRemove bezogen, da diese
     * im PostRemove nicht mehr verfügbar sind (z.B. Produkte eines Produktmodells)
     *
     * @param RemoveEvent $event
     */
    public function onPreRemove(RemoveEvent $event)
    {
        if (!$this->shouldNotify()) {
            return;
        }

        $id = $event->getSubjectId();
        $subject = $event->getSubject();

        if ($subject instanceof ProductModelInterface) {
            $variants = [];
            $products = [];

            /** @var ProductModelInterface $variant */
            foreach ($subject->getProductModels() as $variant) {
                $variants[] = [
                    'id' => $variant->getId(),
                    'code' => $variant->getCode()
                ];

                /** @var ProductInterface $product */
                foreach ($variant->getProducts() as $product) {
                    $products[] = [
                        'id' => $product->getId(),
                        'identifier' => $product->getIdentifier()
                    ];
                }
            }

            /** @var ProductInterface $product */
            foreach ($subject->getProducts() as $product) {
                $products[] = [
                    'id' => $product->getId(),
                    'identifier' => $product->getIdentifier()
                ];
            }

            $this->productModelStore[$id] = [
                'products' => $products,
                'variants' => $variants,
            ];
        }
    }

    /**
     * @param RemoveEvent $event
     */
    public function onPostRemove(RemoveEvent $event)
    {
        if (!$this->shouldNotify()) {
            return;
        }

        $id = $event->getSubjectId();
        $subject = $event->getSubject();

        if ($subject instanceof CategoryInterface) {
            $this->tessa->notifySingleDeletion($id, $subject->getCode(), Tessa::TYPE_CATEGORY);
        } elseif ($subject instanceof ChannelInterface) {
            $this->tessa->notifySingleDeletion($id, $subject->getCode(), Tessa::TYPE_CHANNEL);
        } elseif ($subject instanceof ProductInterface) {
            $this->tessa->notifySingleDeletion($id, $subject->getIdentifier(), Tessa::TYPE_PRODUCT);
        } elseif ($subject instanceof GroupInterface) {
            $this->tessa->notifySingleDeletion($id, $subject->getCode(), Tessa::TYPE_GROUP);
        } elseif ($subject instanceof ProductModelInterface) {
            $this->tessa->notifySingleDeletion($id, $subject->getCode(), Tessa::TYPE_PRODUCT_MODEL);

            // Varianten und Produkte ebenfalls beachten
            if (array_key_exists($id, $this->productModelStore)) {
                $products = $this->productModelStore[$id]['products'];
                $variants = $this->productModelStore[$id]['variants'];

                /** @var array $product */
                foreach ($products as $product) {
                    $this->tessa->notifySingleDeletion($product['id'], $product['identifier'], Tessa::TYPE_PRODUCT);
                }

                /** @var array $variant */
                foreach ($variants as $variant) {
                    $this->tessa->notifySingleDeletion($variant['id'], $variant['code'], Tessa::TYPE_PRODUCT_MODEL);
                }

                unset($this->productModelStore[$id]);
            }
        }
    }

    /**
     * @return bool
     */
    protected function shouldNotify(): bool
    {
        $user = $this->userContext->getUser();
        if ($user !== null
            && $user->getUsername() === $this->tessa->getUserUsedByTessa()) {
            return false;
        }
        return true;
    }
}
