<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Financial\AccountStatementTemplate;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AccountStatementTemplateChoiceType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => 'Account statement template',
                'required' => true,
                'class' => AccountStatementTemplate::class,
                'choice_label' => function (AccountStatementTemplate $accountStatementTemplate) {
                    if (! $accountStatementTemplate->getIsValid()) {
                        return sprintf(
                            '*%s* %s',
                            $this->translator->trans('invalid'),
                            $accountStatementTemplate->getName()
                        );
                    }

                    return $accountStatementTemplate->getName();
                },
                'selectedAccountStatementTemplate' => null,
            ]
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $selectedAccountStatementTemplate = $options['selectedAccountStatementTemplate'];

                return function (EntityRepository $er) use ($selectedAccountStatementTemplate) {
                    $qb = $er->createQueryBuilder('qt')
                        ->andWhere('qt.deletedAt IS NULL')
                        ->andWhere('qt.isValid = TRUE')
                        ->addOrderBy('qt.name', 'ASC');

                    if ($selectedAccountStatementTemplate) {
                        $qb->orWhere('qt.id = :accountStatementTemplate')
                            ->setParameter('accountStatementTemplate', $selectedAccountStatementTemplate);
                    }

                    return $qb;
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
