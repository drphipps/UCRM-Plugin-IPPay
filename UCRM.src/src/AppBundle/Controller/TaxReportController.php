<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\DataProvider\TaxReportDataProvider;
use AppBundle\Entity\Organization;
use AppBundle\Form\TaxReportType;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/reports/billing/taxes")
 */
class TaxReportController extends BaseController
{
    /**
     * @Route("", name="taxes_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $form = $this->createForm(TaxReportType::class);

        if ($request->get('from') === null && $request->get('to') === null && $request->get('organization') === null) {
            $from = new \DateTimeImmutable('first day of this month');
            $to = new \DateTimeImmutable('last day of this month');
            $organization = $this->em->getRepository(Organization::class)->getFirstSelected();
        } else {
            try {
                $from = new \DateTimeImmutable($request->get('from'));
            } catch (\Exception $e) {
                $from = new \DateTimeImmutable('first day of this month');
            }

            try {
                $to = new \DateTimeImmutable($request->get('to'));
            } catch (\Exception $e) {
                $to = new \DateTimeImmutable('last day of this month');
            }

            $organization = $this->em->find(Organization::class, (int) $request->get('organization'));
            if (! $organization) {
                $this->addTranslatedFlash('warning', 'Organization not found, using default.');
                $organization = $this->em->getRepository(Organization::class)->getFirstSelected();
            }
        }
        $form->get('from')->setData($from);
        $form->get('to')->setData($to);
        $form->get('organization')->setData($organization);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $organization = $data['organization'];
            $from = new \DateTimeImmutable($data['from']->format('Y-m-d'));
            $to = new \DateTimeImmutable($data['to']->format('Y-m-d'));

            return $this->redirectToRoute(
                'taxes_index',
                [
                    'from' => $from->format('Y-m-d'),
                    'to' => $to->format('Y-m-d'),
                    'organization' => $organization->getId(),
                ]
            );
        }

        return $this->render(
            'tax_report/index.html.twig',
            [
                'taxReport' => $this->get(TaxReportDataProvider::class)->getTaxReport($from, $to, $organization),
                'form' => $form->createView(),
            ]
        );
    }
}
