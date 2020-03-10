<?php
/**
 * TessaType.php
 *
 * @author    Matthias Mahler <m.mahler@eikona.de>
 * @copyright 2017 Eikona AG (http://www.eikona.de)
 */

namespace Eikona\Tessa\ReferenceDataBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TessaType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars = array_merge($view->vars, [
            'allowedExtensions' => $options['allowedExtensions'],
            'maximumCount' => $options['maximumCount'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allowedExtensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif'],
            'maximumCount' => 1,
        ]);
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'eikona_form_tessa';
    }
}
