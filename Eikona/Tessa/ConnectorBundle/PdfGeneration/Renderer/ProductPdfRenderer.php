<?php
/**
 * ProductPdfRenderer.php
 *
 * @author      Felix Hack <f.hack@eikona-media.de>
 * @copyright   2019 EIKONA Media (https://eikona-media.de)
 */

namespace Eikona\Tessa\ConnectorBundle\PdfGeneration\Renderer;

use Akeneo\Pim\Enrichment\Bundle\PdfGeneration\Builder\PdfBuilderInterface;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Eikona\Tessa\ConnectorBundle\Security\AuthGuard;
use Eikona\Tessa\ConnectorBundle\Tessa;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class ProductPdfRenderer extends \Akeneo\Pim\Enrichment\Bundle\PdfGeneration\Renderer\ProductPdfRenderer
{
    use ProductPdfRendererTrait;

    public function __construct(
        EngineInterface $templating,
        PdfBuilderInterface $pdfBuilder,
        DataManager $dataManager,
        CacheManager $cacheManager,
        FilterManager $filterManager,
        IdentifiableObjectRepositoryInterface $attributeRepository,
        string $template,
        string $uploadDirectory,
        string $customFont = null,
        Tessa $tessa,
        AuthGuard $authGuard
    )
    {
        $this->tessa = $tessa;
        $this->authGuard = $authGuard;

        parent::__construct($templating, $pdfBuilder, $dataManager, $cacheManager, $filterManager, $attributeRepository, $template, $uploadDirectory, $customFont);
    }
}
