<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Option;
use AppBundle\Entity\Product;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tax;
use AppBundle\Factory\Financial\ItemData\FeeItemDataFactory;
use AppBundle\Factory\Financial\ItemData\ProductItemDataFactory;
use AppBundle\Factory\Financial\ItemData\ServiceItemDataFactory;
use AppBundle\Factory\Financial\ItemData\SurchargeItemDataFactory;
use AppBundle\Form\InvoiceType;
use AppBundle\Form\QuoteType;
use AppBundle\Security\Permission;
use AppBundle\Util\DateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/client/financial")
 */
class FinancialFormItemController extends BaseController
{
    /**
     * @Route(
     *     "/get-item-product/{clientId}/{id}",
     *     name="financial_item_product",
     *     requirements={"clientId": "\d+", "id": "\d+"}
     * )
     * @Method("GET")
     * @ParamConverter("client", options={"id" = "clientId"})
     * @Permission("guest")
     */
    public function getItemProductAction(Client $client, Product $product): JsonResponse
    {
        $this->checkPermissions();

        return new JsonResponse($this->get(ProductItemDataFactory::class)->create($product, $client));
    }

    /**
     * @Route("/get-item-fee/{id}", name="financial_item_fee", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function getItemFeeAction(Fee $fee): JsonResponse
    {
        $this->checkPermissions();

        return new JsonResponse($this->get(FeeItemDataFactory::class)->create($fee));
    }

    /**
     * @Route("/get-item-service/{id}", name="financial_item_service", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function getItemServiceAction(Service $service): JsonResponse
    {
        $this->checkPermissions();

        $serviceItemData = $this->get(ServiceItemDataFactory::class)->create($service);
        if (! $serviceItemData) {
            throw $this->createNotFoundException();
        }

        return new JsonResponse($serviceItemData);
    }

    /**
     * @Route("/get-updated-item-service", name="financial_item_updated_service")
     * @Method("GET")
     * @Permission("guest")
     */
    public function getUpdatedItemServiceAction(Request $request): JsonResponse
    {
        $this->checkPermissions();

        $service = $this->em->find(Service::class, (int) $request->get('service'));
        if (! $service) {
            throw $this->createNotFoundException();
        }

        try {
            $from = DateTimeFactory::createDate($request->get('from') ?? 'invalid');
            $to = DateTimeFactory::createDate($request->get('to') ?? 'invalid');
        } catch (\Exception $exception) {
            throw $this->createNotFoundException();
        }

        return new JsonResponse(
            $this->get(ServiceItemDataFactory::class)->createUpdated(
                $service,
                $from,
                $to
            )
        );
    }

    /**
     * @Route("/get-item-surcharges/{id}", name="financial_item_surcharges", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function getItemSurchargesAction(Service $service): JsonResponse
    {
        $this->checkPermissions();

        $items = [];
        $surchargeItemDataFactory = $this->get(SurchargeItemDataFactory::class);
        foreach ($service->getServiceSurcharges() as $surcharge) {
            $items[] = $surchargeItemDataFactory->create($surcharge);
        }

        return new JsonResponse($items);
    }

    /**
     * @Route(
     *     "/invoice-collection-update/{id}",
     *     name="financial_item_invoice_collection_update",
     *     defaults={"id" = null},
     *     requirements={"id": "\d+"},
     *     options={"expose"=true}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function getInvoiceCollectionUpdate(?Invoice $invoice): JsonResponse
    {
        $this->checkPermissions();

        $invoice = $invoice ?? new Invoice();
        if (! $invoice->getId()) {
            $invoice->setPricingMode($this->getOption(Option::PRICING_MODE));
        }
        $form = $this->createForm(InvoiceType::class, $invoice)->createView();

        return new JsonResponse($this->getCollectionData($form, $invoice));
    }

    /**
     * @Route(
     *     "/quote-collection-update/{id}",
     *     name="financial_item_quote_collection_update",
     *     defaults={"id" = null},
     *     requirements={"id": "\d+"},
     *     options={"expose"=true}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function getQuoteCollectionUpdate(?Quote $quote): JsonResponse
    {
        $this->checkPermissions();

        $quote = $quote ?? new Quote();
        if (! $quote->getId()) {
            $quote->setPricingMode($this->getOption(Option::PRICING_MODE));
        }
        $form = $this->createForm(QuoteType::class, $quote)->createView();

        return new JsonResponse($this->getCollectionData($form, $quote));
    }

    private function getCollectionData(FormView $form, FinancialInterface $financial): array
    {
        return [
            'taxesData' => $this->em->getRepository(Tax::class)->getTaxesData(),
            'prototypeFee' => $this->renderView(
                'client/invoice/components/edit/item.html.twig',
                [
                    'form' => $form,
                    'type' => 'fee',
                    'financial' => $financial,
                ]
            ),
            'prototypeOther' => $this->renderView(
                'client/invoice/components/edit/item.html.twig',
                [
                    'form' => $form,
                    'type' => 'other',
                    'financial' => $financial,
                ]
            ),
            'prototypeService' => $this->renderView(
                'client/invoice/components/edit/item.html.twig',
                [
                    'form' => $form,
                    'type' => 'service',
                    'financial' => $financial,
                ]
            ),
            'prototypeSurcharge' => $this->renderView(
                'client/invoice/components/edit/item.html.twig',
                [
                    'form' => $form,
                    'type' => 'surcharge',
                    'financial' => $financial,
                ]
            ),
            'prototypeProduct' => $this->renderView(
                'client/invoice/components/edit/item.html.twig',
                [
                    'form' => $form,
                    'type' => 'product',
                    'financial' => $financial,
                ]
            ),
        ];
    }

    private function checkPermissions(): void
    {
        if (
            ! $this->isPermissionGranted(Permission::VIEW, InvoiceController::class)
            || ! $this->isPermissionGranted(Permission::VIEW, QuoteController::class)
        ) {
            throw $this->createAccessDeniedException();
        }
    }
}
