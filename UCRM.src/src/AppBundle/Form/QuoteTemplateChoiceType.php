<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Financial\QuoteTemplate;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteTemplateChoiceType extends AbstractType
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
                'label' => 'Quote template',
                'required' => true,
                'class' => QuoteTemplate::class,
                'choice_label' => function (QuoteTemplate $quoteTemplate) {
                    if (! $quoteTemplate->getIsValid()) {
                        return sprintf(
                            '*%s* %s',
                            $this->translator->trans('invalid'),
                            $quoteTemplate->getName()
                        );
                    }

                    return $quoteTemplate->getName();
                },
                'selectedQuoteTemplate' => null,
            ]
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $selectedQuoteTemplate = $options['selectedQuoteTemplate'];

                return function (EntityRepository $er) use ($selectedQuoteTemplate) {
                    $qb = $er->createQueryBuilder('qt')
                        ->andWhere('qt.deletedAt IS NULL')
                        ->andWhere('qt.isValid = TRUE')
                        ->addOrderBy('qt.name', 'ASC');

                    if ($selectedQuoteTemplate) {
                        $qb->orWhere('qt.id = :quoteTemplate')
                            ->setParameter('quoteTemplate', $selectedQuoteTemplate);
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
