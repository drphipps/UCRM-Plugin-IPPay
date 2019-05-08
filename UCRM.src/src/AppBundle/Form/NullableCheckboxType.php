<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class NullableCheckboxType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setData($options['data'] ?? null);
        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($value) {
                    if (null === $value) {
                        return null;
                    }

                    return $value ? '1' : '0';
                },
                function ($value) {
                    if (null === $value) {
                        return null;
                    }

                    if (! is_string($value)) {
                        throw new TransformationFailedException('Expected a string.');
                    }

                    return $value === '1';
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'value' => '1',
            'checked' => $form->getViewData() === '1',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CheckboxType::class;
    }
}
