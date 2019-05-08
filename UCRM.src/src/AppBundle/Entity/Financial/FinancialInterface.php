<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\State;
use Doctrine\Common\Collections\Collection;

interface FinancialInterface
{
    // discount type
    public const DISCOUNT_NONE = 0;
    public const DISCOUNT_PERCENTAGE = 1;
    public const DISCOUNT_FIXED = 2;

    // item rounding
    public const ITEM_ROUNDING_STANDARD = 0;
    public const ITEM_ROUNDING_NO_ROUNDING = 1;
    public const ITEM_ROUNDINGS = [
        self::ITEM_ROUNDING_STANDARD => 'Standard rounding',
        self::ITEM_ROUNDING_NO_ROUNDING => 'Precise non-rounded item totals',
    ];
    public const POSSIBLE_ITEM_ROUNDING = [
        self::ITEM_ROUNDING_STANDARD,
        self::ITEM_ROUNDING_NO_ROUNDING,
    ];

    // tax rounding
    public const TAX_ROUNDING_TOTAL = 0;
    public const TAX_ROUNDING_PER_ITEM = 1;
    public const TAX_ROUNDINGS = [
        self::TAX_ROUNDING_TOTAL => 'Round only tax total',
        self::TAX_ROUNDING_PER_ITEM => 'Round tax for each item separately',
    ];
    public const POSSIBLE_TAX_ROUNDING = [
        self::TAX_ROUNDING_TOTAL,
        self::TAX_ROUNDING_PER_ITEM,
    ];

    const VALIDATION_GROUP_DEFAULT = 'Default';
    const VALIDATION_GROUP_API = 'Api';

    public function getId(): ?int;

    public function setId(int $id): void;

    public function setTotal(float $total): void;

    public function getTotal(): ?float;

    public function getTotalUntaxed(): ?float;

    public function setTotalUntaxed(float $totalUntaxed): void;

    public function getSubtotal(): ?float;

    public function setSubtotal(float $subtotal): void;

    public function getTotalDiscount(): ?float;

    public function setTotalDiscount(float $totalDiscount): void;

    public function getTotalTaxAmount(): ?float;

    public function setTotalTaxAmount(float $totalTaxAmount): void;

    public function getTotalTaxes(): ?array;

    public function setTotalTaxes(array $totalTaxes): void;

    public function setDiscountType(int $discountType): void;

    public function getDiscountType(): int;

    public function setDiscountValue(?float $discountValue): void;

    public function getDiscountValue(): ?float;

    public function setDiscountInvoiceLabel(?string $discountInvoiceLabel): void;

    public function getDiscountInvoiceLabel(): ?string;

    public function setCreatedDate(?\DateTime $createdDate): void;

    public function getCreatedDate(): ?\DateTime;

    public function setClient(Client $client): void;

    public function getClient(): ?Client;

    public function setPdfPath(?string $pdfPath): void;

    public function getPdfPath(): ?string;

    public function setClientFirstName(?string $clientFirstName): void;

    public function getClientFirstName(): ?string;

    public function setClientLastName(?string $clientLastName): void;

    public function getClientLastName(): ?string;

    public function setClientCompanyName(?string $clientCompanyName): void;

    public function getClientCompanyName(): ?string;

    public function setClientStreet1(?string $clientStreet1): void;

    public function getClientStreet1(): ?string;

    public function setClientStreet2(?string $clientStreet2): void;

    public function getClientStreet2(): ?string;

    public function setClientCity(?string $clientCity): void;

    public function getClientCity(): ?string;

    public function setClientZipCode(?string $clientZipCode): void;

    public function getClientZipCode(): ?string;

    public function setClientCompanyRegistrationNumber(?string $clientCompanyRegistrationNumber): void;

    public function getClientCompanyRegistrationNumber(): ?string;

    public function setClientCompanyTaxId(?string $clientCompanyTaxId): void;

    public function getClientCompanyTaxId(): ?string;

    public function setClientPhone(?string $clientPhone): void;

    public function getClientPhone(): ?string;

    public function setClientEmail(?string $clientEmail): void;

    public function getClientEmail(): ?string;

    public function setClientInvoiceStreet1(?string $clientInvoiceStreet1): void;

    public function getClientInvoiceStreet1(): ?string;

    public function setClientInvoiceStreet2(?string $clientInvoiceStreet2): void;

    public function getClientInvoiceStreet2(): ?string;

    public function setClientInvoiceCity(?string $clientInvoiceCity): void;

    public function getClientInvoiceCity(): ?string;

    public function setClientInvoiceZipCode(?string $clientInvoiceZipCode): void;

    public function getClientInvoiceZipCode(): ?string;

    public function setClientInvoiceAddressSameAsContact(?bool $clientInvoiceAddressSameAsContact): void;

    public function getClientInvoiceAddressSameAsContact(): ?bool;

    public function setOrganizationName(?string $organizationName): void;

    public function getOrganizationName(): ?string;

    public function setOrganizationRegistrationNumber(?string $organizationRegistrationNumber): void;

    public function getOrganizationRegistrationNumber(): ?string;

    public function setOrganizationTaxId(?string $organizationTaxId): void;

    public function getOrganizationTaxId(): ?string;

    public function setOrganizationEmail(?string $organizationEmail): void;

    public function getOrganizationEmail(): ?string;

    public function setOrganizationPhone(?string $organizationPhone): void;

    public function getOrganizationPhone(): ?string;

    public function setOrganizationWebsite(?string $organizationWebsite): void;

    public function getOrganizationWebsite(): ?string;

    public function setOrganizationStreet1(?string $organizationStreet1): void;

    public function getOrganizationStreet1(): ?string;

    public function setOrganizationStreet2(?string $organizationStreet2): void;

    public function getOrganizationStreet2(): ?string;

    public function setOrganizationCity(?string $organizationCity): void;

    public function getOrganizationCity(): ?string;

    public function setOrganizationZipCode(?string $organizationZipCode): void;

    public function getOrganizationZipCode(): ?string;

    public function setOrganizationState(?State $organizationState): void;

    public function getOrganizationState(): ?State;

    public function setOrganizationCountry(?Country $organizationCountry): void;

    public function getOrganizationCountry(): ?Country;

    public function setOrganizationBankAccountField1(?string $organizationBankAccountField1): void;

    public function getOrganizationBankAccountField1(): ?string;

    public function setOrganizationBankAccountField2(?string $organizationBankAccountField2): void;

    public function getOrganizationBankAccountField2(): ?string;

    public function setOrganizationBankAccountName(?string $organizationBankAccountName): void;

    public function getOrganizationBankAccountName(): ?string;

    public function setOrganizationLogoPath(?string $organizationLogoPath): void;

    public function getOrganizationLogoPath(): ?string;

    public function setOrganizationStampPath(?string $organizationStampPath): void;

    public function getOrganizationStampPath(): ?string;

    public function setClientInvoiceCountry(?Country $clientInvoiceCountry): void;

    public function getClientInvoiceCountry(): ?Country;

    public function setClientState(?State $clientState): void;

    public function getClientState(): ?State;

    public function setClientCountry(?Country $clientCountry): void;

    public function getClientCountry(): ?Country;

    public function setClientInvoiceState(?State $clientInvoiceState): void;

    public function getClientInvoiceState(): ?State;

    public function setCurrency(Currency $currency): void;

    public function getCurrency(): ?Currency;

    public function setOrganization(Organization $organization): void;

    public function getOrganization(): ?Organization;

    public function getItemRounding(): ?int;

    public function setItemRounding(int $itemRounding): void;

    public function getTaxRounding(): ?int;

    public function setTaxRounding(int $taxRounding): void;

    public function getPricingMode(): ?int;

    public function setPricingMode(int $pricingMode): void;

    public function getTaxCoefficientPrecision(): ?int;

    public function setTaxCoefficientPrecision(?int $taxCoefficientPrecision): void;

    public function getClientAttributes(): array;

    public function setClientAttributes(array $clientAttributes): void;

    public function setComment(?string $comment): void;

    public function getComment(): ?string;

    public function setNotes(?string $notes): void;

    public function getNotes(): ?string;

    public function getTemplateIncludeBankAccount(): bool;

    public function setTemplateIncludeBankAccount(bool $templateIncludeBankAccount): void;

    public function getTemplateIncludeTaxInformation(): bool;

    public function setTemplateIncludeTaxInformation(bool $templateIncludeTaxInformation): void;

    public function getEmailSentDate(): ?\DateTime;

    public function setEmailSentDate(?\DateTime $emailSentDate): void;

    public function addItem(FinancialItemInterface $item): void;

    public function removeItem(FinancialItemInterface $item): void;

    /**
     * @return Collection|FinancialItemInterface[]
     */
    public function getItems(): Collection;

    /**
     * @return Collection|FinancialItemInterface[]
     */
    public function getItemsSorted(): Collection;

    public function getClientNameForView(): string;

    public function getOrganizationBankAccountFieldsForView(): string;

    public function hasCustomTotalRounding(): bool;

    public function getTotalRoundingDifference(): float;

    public function setTotalRoundingDifference(float $totalRoundingDifference): void;

    public function getTotalRoundingPrecision(): ?int;

    public function setTotalRoundingPrecision(?int $totalRoundingPrecision): void;

    public function getTotalRoundingMode(): int;

    public function setTotalRoundingMode(int $totalRoundingMode): void;
}
