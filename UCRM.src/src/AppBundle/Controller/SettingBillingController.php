<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Facade\TaxFacade;
use AppBundle\Form\Data\Settings\BillingData;
use AppBundle\Form\SettingBillingType;
use AppBundle\Security\Permission;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/billing/invoicing")
 */
class SettingBillingController extends BaseController
{
    /**
     * @Route("", name="setting_billing_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Invoicing",
     *     path="System -> Billing -> Invoicing",
     *     formTypes={SettingBillingType::class},
     *     extra={
     *         "Settings",
     *         "Default service parameters"
     *     }
     * )
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var BillingData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(BillingData::class);
        $oldOptions = clone $options;

        $form = $this->createForm(
            SettingBillingType::class,
            $options,
            [
                'allow_invoice_item_rounding' => $options->invoiceItemRounding === FinancialInterface::ITEM_ROUNDING_NO_ROUNDING,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->transactional(
                function () use ($options, $oldOptions, $optionsManager) {
                    // These options are currently hidden from the user and are set automatically based on pricing mode.
                    if ($options->pricingMode === Option::PRICING_MODE_WITH_TAXES) {
                        $options->pricingMultipleTaxes = false;
                        $options->invoiceTaxRounding = FinancialInterface::TAX_ROUNDING_PER_ITEM;
                        assert($options->invoiceItemRounding === FinancialInterface::ITEM_ROUNDING_STANDARD);
                        $this->get(TaxFacade::class)->restrictToOneTax();
                    } else {
                        $options->pricingMultipleTaxes = true;
                        $options->invoiceTaxRounding = FinancialInterface::TAX_ROUNDING_TOTAL;
                    }

                    // If recurring payments were enabled before and now they are not (including autopay),
                    // cancel all existing payment plans.
                    if (
                        ($oldOptions->subscriptionsEnabledCustom || $oldOptions->subscriptionsEnabledLinked)
                        && (! $options->subscriptionsEnabledCustom && ! $options->subscriptionsEnabledLinked)
                    ) {
                        $paymentPlans = $this->em->getRepository(PaymentPlan::class)->findAll();
                        $this->get(PaymentPlanFacade::class)->unsubscribeMultiple($paymentPlans);
                    }

                    $optionsManager->updateOptions($options);
                    $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_BILLING, '1');
                }
            );

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_billing_edit');
        }

        return $this->render(
            'setting/billing/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
