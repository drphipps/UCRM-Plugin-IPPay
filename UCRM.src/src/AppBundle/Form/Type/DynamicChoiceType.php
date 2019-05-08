<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_loader' => new JsChoiceLoader(),
                'validation_groups' => false,
            ]
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}

class JsChoiceList implements ChoiceListInterface
{
    public function getChoices()
    {
        return [];
    }

    public function getValues()
    {
        return [];
    }

    public function getStructuredValues()
    {
        return [];
    }

    public function getOriginalKeys()
    {
        return [];
    }

    public function getChoicesForValues(array $values)
    {
        return $values;
    }

    public function getValuesForChoices(array $choices)
    {
        return $choices;
    }
}

class JsChoiceLoader implements ChoiceLoaderInterface
{
    private $choiceList;

    public function __construct()
    {
        $this->choiceList = new JsChoiceList();
    }

    public function loadChoiceList($value = null)
    {
        return $this->choiceList;
    }

    public function loadChoicesForValues(array $values, $value = null)
    {
        return $values;
    }

    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $choices;
    }
}
