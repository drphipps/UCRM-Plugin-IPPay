<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\AccountStatementFilterData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AccountStatementFilterType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'dateFilter',
            ChoiceType::class,
            [
                'choices' => $this->getDateFilterChoices(),
                'required' => true,
                'empty_data' => AccountStatementFilterData::DATE_CHOICE_ALL,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => AccountStatementFilterData::class,
                'translation_domain' => false,
            ]
        );
    }

    private function getDateFilterChoices(): array
    {
        $choices = [];
        foreach (AccountStatementFilterData::DATE_CHOICES as $id => $name) {
            $choices[$this->translator->trans($name)] = $id;
        }
        $year = date('Y');
        $choices[$year] = (string) $year;
        --$year;
        $choices[$year] = (string) $year;

        return $choices;
    }
}
