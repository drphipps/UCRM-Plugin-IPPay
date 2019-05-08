<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Csv\EntityCsvFactory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Entity\Client;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Repository\ClientRepository;
use AppBundle\Util\Formatter;

class ClientCsvFactory
{
    private const CUSTOM_ATTRIBUTE_SUFFIX = ' (custom attribute)';

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function create(array $clients, array $customAttributes): string
    {
        $builder = new CsvBuilder();

        foreach ($clients as $row) {
            /** @var Client $client */
            $client = $row[0];

            $emails = $client->getEmails();
            $phones = $client->getPhones();

            $clientAttributeValue = [];
            foreach ($client->getAttributes() as $clientAttribute) {
                $clientAttributeValue[$clientAttribute->getAttribute()->getId()] = $clientAttribute->getValue();
            }

            $attributeColumns = [];
            /** @var CustomAttribute $customAttribute */
            foreach ($customAttributes as $customAttribute) {
                $attributeColumns[$customAttribute->getName() . self::CUSTOM_ATTRIBUTE_SUFFIX] = $clientAttributeValue[$customAttribute->getId()] ?? '';
            }

            $builder->addData(
                array_merge(
                    [
                        'Id' => $client->getId(),
                        'Name' => $client->getNameForView(),
                        'Emails' => implode(', ', $emails),
                        'Phones' => implode(', ', $phones),
                        'Address' => $client->getAddressString(),
                        'Balance' => $this->formatter->formatCurrency($row['c_balance'], $row['currencyCode']),
                        'Service plans' => $row['tariffs'],
                        'Connected to' => str_replace(
                            ClientRepository::SITE_DEVICE_DELIMITER,
                            ', ',
                            str_replace(ClientRepository::SITE_DEVICE_SEPARATOR, ' - ', $row['devices'])
                        ),
                        'Street 1' => $client->getStreet1(),
                        'Street 2' => $client->getStreet2(),
                        'City' => $client->getCity(),
                        'Country' => $client->getCountry() ? $client->getCountry()->getName() : '',
                        'State' => $client->getState() ? $client->getState()->getName() : '',
                        'ZIP code' => $client->getZipCode(),
                        'Invoice Street 1' => $client->getInvoiceStreet1(),
                        'Invoice Street 2' => $client->getInvoiceStreet2(),
                        'Invoice City' => $client->getInvoiceCity(),
                        'Invoice Country' => $client->getInvoiceCountry() ? $client->getInvoiceCountry()->getName() : '',
                        'Invoice State' => $client->getInvoiceState() ? $client->getInvoiceState()->getName() : '',
                        'Invoice ZIP code' => $client->getInvoiceZipCode(),
                        'Invoice address same as contact' => $client->getInvoiceAddressSameAsContact(),
                        'Note' => $client->getNote(),
                        'Registration date' => $this->formatter->formatDate($client->getRegistrationDate(), Formatter::DEFAULT, Formatter::SHORT),
                        'First name' => $client->getFirstName(),
                        'Last name' => $client->getLastName(),
                        'Company name' => $client->getCompanyName(),
                        'Company registration number' => $client->getCompanyRegistrationNumber(),
                        'Company tax ID' => $client->getCompanyTaxId(),
                        'Company website' => $client->getCompanyWebsite(),
                        'Custom ID' => $client->getUserIdent(),
                    ],
                    $attributeColumns
                )
            );
        }

        return $builder->getCsv();
    }
}
