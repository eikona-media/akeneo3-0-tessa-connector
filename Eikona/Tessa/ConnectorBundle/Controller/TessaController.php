<?php
/**
 * TessaController.php
 *
 * @author    Timo Müller <t.mueller@eikona-media.de>
 * @copyright 2018 Eikona AG (http://www.eikona.de)
 */

namespace Eikona\Tessa\ConnectorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TessaController extends Controller
{
    /**
     * @return RedirectResponse
     */
    public function gotoTessaAction()
    {
        $url = $this->get('eikona.tessa')->getUiUrl();

        if (!$url) {
            throw $this->createNotFoundException('Tessa url not found');
        }

        return new RedirectResponse($url);
    }
}
