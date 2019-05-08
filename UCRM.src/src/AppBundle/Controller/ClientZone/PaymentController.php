<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Grid\ClientZone\PaymentGridFactory;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/payments")
 */
class PaymentController extends BaseController
{
    /**
     * @Route("", name="client_zone_payment_index")
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(Request $request): Response
    {
        $client = $this->getClient();
        $grid = $this->get(PaymentGridFactory::class)->create($client);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client_zone/payment/index.html.twig',
            [
                'client' => $client,
                'payments' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="client_zone_payment_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showAction(Payment $payment): Response
    {
        $this->verifyOwnership($payment);

        if ($this->getOption(Option::CLIENT_ZONE_PAYMENT_DETAILS)) {
            $details = $payment->getProvider() && $payment->getPaymentDetailsId()
                ? $this->em->find(
                    $payment->getProvider()->getPaymentDetailsClass(),
                    $payment->getPaymentDetailsId()
                )
                : null;
        }

        return $this->render(
            'client_zone/payment/show.html.twig',
            [
                'payment' => $payment,
                'details' => $details ?? null,
            ]
        );
    }
}
