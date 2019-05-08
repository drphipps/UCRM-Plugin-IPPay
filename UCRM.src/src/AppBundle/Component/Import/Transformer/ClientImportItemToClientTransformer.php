<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Component\Import\Builder\ClientErrorSummaryBuilder;
use AppBundle\Component\Import\Builder\ClientImportItemValidationErrorsBuilder;
use AppBundle\Component\Import\DataProvider\TransformerEntityData;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Factory\ClientFactory;
use Doctrine\ORM\EntityManagerInterface;

class ClientImportItemToClientTransformer extends AbstractImportItemToEntityTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    public function __construct(
        ClientFactory $clientFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->clientFactory = $clientFactory;
    }

    public function transform(
        ClientImportItem $item,
        TransformerEntityData $transformerEntityData,
        ClientImportItemValidationErrorsBuilder $validationErrorsBuilder,
        ?ClientErrorSummaryBuilder $errorSummaryBuilder = null
    ): Client {
        $client = $this->clientFactory->create($item->getImport()->getOrganization());

        $client->setUserIdent(
            $this->transformUserIdent($item, $validationErrorsBuilder)
            ?? $client->getUserIdent()
        );

        $client->getUser()->setFirstName($item->getFirstName());
        $client->getUser()->setLastName($item->getLastName());
        if ($item->getNameForView() !== null) {
            $fullName = explode(' ', $item->getNameForView(), 2);
            $client->getUser()->setFirstName($fullName[0] ?? $item->getFirstName() ?? null);
            $client->getUser()->setLastName($fullName[1] ?? $item->getLastName() ?? null);
        }

        $client->getUser()->setUsername(
            $this->transformUsername($item, $validationErrorsBuilder)
            ?? $client->getUser()->getUsername()
        );

        if ($item->getCompanyName() !== null) {
            $client->setClientType(Client::TYPE_COMPANY);
            $client->setCompanyName($item->getCompanyName());
            $client->setCompanyContactFirstName($client->getUser()->getFirstName());
            $client->setCompanyContactLastName($client->getUser()->getLastName());
            $client->setCompanyRegistrationNumber($item->getCompanyRegistrationNumber());
            $client->setCompanyTaxId($item->getCompanyTaxId());
            $client->setCompanyWebsite($item->getCompanyWebsite());
            $client->getUser()->setFirstName(null);
            $client->getUser()->setLastName(null);
        }

        $client->setIsLead($this->transformBool($item->getIsLead()) ?? $client->getIsLead());

        $client->setAddressGpsLat(
            $this->transformFloat(
                $item->getAddressGpsLat(),
                'addressGpsLat',
                'Client latitude should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $client->getAddressGpsLat()
        );
        $client->setAddressGpsLon(
            $this->transformFloat(
                $item->getAddressGpsLon(),
                'addressGpsLon',
                'Client longitude should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $client->getAddressGpsLon()
        );

        $client->setTax1(
            $this->transformTax(
                $item->getTax1(),
                'tax1',
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $client->getTax1()
        );
        $client->setTax2(
            $this->transformTax(
                $item->getTax2(),
                'tax2',
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $client->getTax2()
        );
        $client->setTax3(
            $this->transformTax(
                $item->getTax3(),
                'tax3',
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $client->getTax3()
        );

        $client->setStreet1($item->getStreet1());
        $client->setStreet2($item->getStreet2());
        $client->setCity($item->getCity());
        $client->setCountry(
            $this->transformCountry(
                $item->getCountry(),
                'country',
                $transformerEntityData,
                $validationErrorsBuilder
            )
            ?? $client->getCountry()
        );
        $client->setState(
            $this->transformState(
                $item->getState(),
                'state',
                $client->getCountry(),
                $transformerEntityData,
                $validationErrorsBuilder
            )
            ?? $client->getState()
        );
        $client->setZipCode($item->getZipCode());

        $client->setInvoiceStreet1($item->getInvoiceStreet1());
        $client->setInvoiceStreet2($item->getInvoiceStreet2());
        $client->setInvoiceCity($item->getInvoiceCity());

        $client->setInvoiceCountry(
            $this->transformCountry(
                $item->getInvoiceCountry(),
                'invoiceCountry',
                $transformerEntityData,
                $validationErrorsBuilder
            )
            ?? $client->getInvoiceCountry()
        );
        // if we have invoice state, but not invoice country, fill regular country
        if (! $client->getInvoiceCountry() && $item->getInvoiceState()) {
            $client->setInvoiceCountry($client->getCountry());
        }
        $client->setInvoiceState(
            $this->transformState(
                $item->getInvoiceState(),
                'invoiceState',
                $client->getInvoiceCountry(),
                $transformerEntityData,
                $validationErrorsBuilder
            )
            ?? $client->getInvoiceState()
        );

        $client->setInvoiceZipCode($item->getInvoiceZipCode());

        $client->setInvoiceAddressSameAsContact($this->isClientInvoiceAddressSameAsContact($client));

        $client->setRegistrationDate(
            $this->transformDate($item->getRegistrationDate(), 'registrationDate', $validationErrorsBuilder)
            ?? $client->getRegistrationDate()
        );
        $client->setNote($item->getClientNote());

        $this->fillClientContacts($client, $item);

        return $client;
    }

    /**
     * Creates saturated collection of client contacts.
     *
     * For example if we have email 1 and phone 2 filled in, but phone 1 is not filled in,
     * this method will create just one contact containing [email 1, phone 2] and so forth.
     *
     * We decided not to support creating divided contacts in CSV import, so creating two contacts
     * containing [email 1, empty phone] and [empty email, phone 2] is not possible.
     */
    private function fillClientContacts(Client $client, ClientImportItem $item): void
    {
        $emails = $this->mergeContacts($item->getEmail1(), $item->getEmail2(), $item->getEmail3(), $item->getEmails());
        $phones = $this->mergeContacts($item->getPhone1(), $item->getPhone2(), $item->getPhone3(), $item->getPhones());

        $max = max(count($emails), count($phones));
        $alreadyCreatedContactWithDefaultTypes = false;
        $countContacts = 0;
        for ($i = 0; $i < $max; ++$i) {
            $email = $emails[$i] ?? null;
            $phone = $phones[$i] ?? null;

            if (! $phone && ! $email) {
                continue;
            }
            ++$countContacts;

            $contact = new ClientContact();
            $contact->setEmail($email);
            $contact->setPhone($phone);

            // If the first contact's email corresponds with username, set the contact's `isLogin` to true
            // to correctly display "use email as username" in client's edit form
            if ($countContacts === 1 && $email && $client->getUser()->getUsername() === $email) {
                $contact->setIsLogin(true);
            }

            // only add default contact types for first contact that has an email
            if (! $alreadyCreatedContactWithDefaultTypes && $contact->getEmail()) {
                $contact->addType($this->entityManager->getReference(ContactType::class, ContactType::IS_BILLING));
                $contact->addType($this->entityManager->getReference(ContactType::class, ContactType::IS_CONTACT));
                $alreadyCreatedContactWithDefaultTypes = true;
            }

            $contact->setClient($client);
            $client->addContact($contact);
        }

        // If the client has username, but no contacts, add an empty contact with `isLogin` set to `false`
        // to prevent "use email as username" in client's edit form showing as true from the default contact.
        if (! $countContacts && $client->getUser()->getUsername() !== null) {
            $contact = new ClientContact();
            $contact->addType($this->entityManager->getReference(ContactType::class, ContactType::IS_BILLING));
            $contact->addType($this->entityManager->getReference(ContactType::class, ContactType::IS_CONTACT));
            $contact->setIsLogin(false);
            $contact->setClient($client);
            $client->addContact($contact);
        }
    }

    private function mergeContacts(?string $contact1, ?string $contact2, ?string $contact3, ?string $contacts): array
    {
        $merged = [$contact1, $contact2, $contact3];
        foreach (explode(',', $contacts ?? '') as $contact) {
            $merged[] = trim($contact);
        }

        // using array_values, because we need the arrays properly indexed (0,1,2,n without spaces)
        return array_values(array_filter($merged));
    }

    private function transformUserIdent(
        ClientImportItem $item,
        ClientImportItemValidationErrorsBuilder $validationErrorsBuilder
    ): ?string {
        if ($item->getUserIdent() === null) {
            return null;
        }

        if (! $this->entityManager->getRepository(Client::class)->isUserIdentUnique($item->getUserIdent())) {
            $validationErrorsBuilder->addTransformerViolation(
                'Client with this custom ID already exists.',
                'userIdent',
                $item->getUserIdent()
            );

            return null;
        }

        $isUniqueWithinImport = $this->entityManager->getRepository(ClientImportItem::class)
            ->isUserIdentUniqueWithinImport($item);

        if (! $isUniqueWithinImport) {
            $validationErrorsBuilder->addTransformerViolation(
                'This custom ID is used multiple times within this import.',
                'userIdent',
                $item->getUserIdent()
            );

            return null;
        }

        return $item->getUserIdent();
    }

    private function transformUsername(
        ClientImportItem $item,
        ClientImportItemValidationErrorsBuilder $validationErrorsBuilder
    ): ?string {
        if ($item->getUsername() === null) {
            return null;
        }

        $isUniqueWithinImport = $this->entityManager->getRepository(ClientImportItem::class)
            ->isUsernameUniqueWithinImport($item);

        if (! $isUniqueWithinImport) {
            $validationErrorsBuilder->addTransformerViolation(
                'This username is used multiple times within this import.',
                'username',
                $item->getUsername()
            );

            return null;
        }

        return $item->getUsername();
    }

    private function isClientInvoiceAddressSameAsContact(Client $client): bool
    {
        $contact = [
            (string) $client->getStreet1(),
            (string) $client->getStreet2(),
            (string) $client->getCity(),
            (string) ($client->getCountry() ? $client->getCountry()->getId() : ''),
            (string) ($client->getState() ? $client->getState()->getId() : ''),
            (string) $client->getZipCode(),
        ];

        $invoice = [
            (string) $client->getInvoiceStreet1(),
            (string) $client->getInvoiceStreet2(),
            (string) $client->getInvoiceCity(),
            (string) ($client->getInvoiceCountry() ? $client->getInvoiceCountry()->getId() : ''),
            (string) ($client->getInvoiceState() ? $client->getInvoiceState()->getId() : ''),
            (string) $client->getInvoiceZipCode(),
        ];

        $foundFilled = false;
        foreach ($invoice as $item) {
            if ($item !== '') {
                $foundFilled = true;
                break;
            }
        }
        // if the invoice address is empty, act as it's the same as contact
        if (! $foundFilled) {
            return true;
        }

        return ! array_diff($contact, $invoice)
            && ! array_diff($invoice, $contact);
    }
}
