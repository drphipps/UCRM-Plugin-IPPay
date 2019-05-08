<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Option;
use AppBundle\Form\Data\ClientChoiceData;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This choice type does not use EntityType because it causes all the entities to be loaded to memory which triggers
 * computeChangeSet for all of them on flush. This can easily cause performance issues with large sets of entities.
 *
 * @see https://ubnt.myjetbrains.com/youtrack/issue/UCRM-3188
 */
class ClientChoiceType extends AbstractType
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(Options $options, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->options = $options;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?Client $client): ?ClientChoiceData {
                    return $client
                        ? ClientChoiceData::fromClient($client)
                        : null;
                },
                function (?ClientChoiceData $client): ?Client {
                    return $client
                        ? $this->entityManager->find(Client::class, $client->id)
                        : null;
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        /** @var int $clientIdType */
        $clientIdType = $this->options->get(Option::CLIENT_ID_TYPE);

        $resolver->setDefaults(
            [
                'class' => Client::class,
                'choice_label' => function (ClientChoiceData $client) use ($clientIdType) {
                    return $client->formatLabel($clientIdType, $this->translator);
                },
                'choice_value' => function (?ClientChoiceData $client) {
                    return $client ? $client->id : null;
                },
                'placeholder' => 'Make a choice.',
                'include_leads' => false,
                'only_clients_with_credit' => false,
                'choice_translation_domain' => false,
            ]
        );
        $resolver->setAllowedTypes('include_leads', 'bool');

        $resolver->setDefault(
            'choices',
            function (\Symfony\Component\OptionsResolver\Options $options) {
                $qb = $this->entityManager->createQueryBuilder()
                    ->addSelect('c.id')
                    ->addSelect('c.isLead')
                    ->addSelect('c.clientType')
                    ->addSelect('c.companyName')
                    ->addSelect('c.userIdent')
                    ->addSelect('c.accountStandingsRefundableCredit')
                    ->addSelect('u.firstName')
                    ->addSelect('u.lastName')
                    ->addSelect('cu.id AS currencyId')
                    ->addSelect('cu.code AS currencyCode')
                    ->addSelect('cu.symbol AS currencySymbol')
                    ->addSelect('o.locale')
                    ->from(Client::class, 'c')
                    ->join('c.user', 'u')
                    ->join('c.organization', 'o')
                    ->join('o.currency', 'cu')
                    ->andWhere('c.deletedAt IS NULL')
                    ->orderBy('u.firstName, u.lastName');

                if (! $options['include_leads']) {
                    $qb->andWhere('c.isLead = false');
                }

                if ($options['only_clients_with_credit']) {
                    $qb->andWhere('c.accountStandingsCredit > 0');
                }

                // Intentionally loading array instead of entities. See class description for details.
                $result = $qb->getQuery()->getArrayResult();

                $clientsWithBillingEmail = $this->getClientsWithBillingEmail();

                // Symfony treats array of arrays as option groups which is not desired here. So we need to create data objects.
                return array_map(
                    function (array $data) use ($clientsWithBillingEmail) {
                        $data['hasBillingEmail'] = in_array($data['id'], $clientsWithBillingEmail, true);

                        return ClientChoiceData::fromArray($data);
                    },
                    $result
                );
            }
        );

        $resolver->setDefault(
            'group_by',
            function (\Symfony\Component\OptionsResolver\Options $options) {
                if (! $options['include_leads']) {
                    return null;
                }

                return function (ClientChoiceData $client) {
                    return $client->isLead
                        ? $this->translator->trans('Client leads')
                        : $this->translator->trans('Active clients');
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        // ATTENTION: Do not use EntityType, see class description for details.
        return ChoiceType::class;
    }

    /**
     * @return int[]
     */
    private function getClientsWithBillingEmail(): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('cl.id')
            ->from(ClientContact::class, 'cc')
            ->join('cc.types', 'cct')
            ->join('cc.client', 'cl')
            ->andWhere('cct.id = :billing')
            ->setParameter('billing', ContactType::IS_BILLING)
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'id');
    }
}
