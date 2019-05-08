<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Client;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Service\PublicUrlGenerator;
use Doctrine\ORM\EntityManager;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Nette\Utils\Strings;

/**
 * Wrapper for Authorize.Net CIM API.
 *
 * @see http://developer.authorize.net/api/reference/features/customer_profiles.html
 * @see http://developer.authorize.net/api/reference/#customer-profiles
 */
class CustomerInformationManager extends AuthorizeNetAPIAccess
{
    const URL_PREFIX_LIVE = 'https://accept.authorize.net/customer/';
    const URL_PREFIX_TEST = 'https://test.authorize.net/customer/';

    const URL_MANAGE = 'manage';
    const URL_PAYMENT_ADD = 'addPayment';
    const URL_PAYMENT_EDIT = 'editPayment';
    const URL_SHIPPING_ADD = 'addShipping';
    const URL_SHIPPING_EDIT = 'editShipping';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    public function __construct(EntityManager $em, PublicUrlGenerator $publicUrlGenerator)
    {
        $this->em = $em;
        $this->publicUrlGenerator = $publicUrlGenerator;
    }

    /**
     * Creates a customer profile if it does exist or if Authorize.Net can't find it.
     */
    public function verifyCustomerProfileId(Client $client)
    {
        $customerProfileId = $client->getAnetCustomerProfileId();
        if (! $customerProfileId || null === $this->getCustomerProfile($customerProfileId)) {
            $customerProfileId = $this->createCustomerProfile($client);
            $client->setAnetCustomerProfileId($customerProfileId);
            $this->em->flush();
        }
    }

    /**
     * Updates Customer Payment Profile ID of Client to be up to date with Authorize.Net.
     *
     *
     * @throws AuthorizeNetException
     */
    public function updatePaymentProfile(Client $client)
    {
        if (! $client->getAnetCustomerProfileId()) {
            throw new AuthorizeNetException('Customer profile ID must be set.');
        }

        $customerProfile = $this->getCustomerProfile($client->getAnetCustomerProfileId());

        if ($customerProfile) {
            $paymentProfiles = $customerProfile->getPaymentProfiles();

            if (! empty($paymentProfiles)) {
                $paymentProfile = reset($paymentProfiles);
                $paymentProfileId = $paymentProfile->getCustomerPaymentProfileId();
            } else {
                $paymentProfileId = null;
            }

            $client->setAnetCustomerPaymentProfileId($paymentProfileId);
            $this->em->flush();
        }
    }

    public function getHostedFormParameters(Client $client, PaymentPlan $paymentPlan): array
    {
        $token = $this->getHostedProfilePageToken($client->getAnetCustomerProfileId(), $paymentPlan->getId());
        $paymentProfileId = $client->getAnetCustomerPaymentProfileId();

        return [
            'action' => sprintf(
                '%s%s',
                $this->sandbox ? self::URL_PREFIX_TEST : self::URL_PREFIX_LIVE,
                $paymentProfileId ? self::URL_PAYMENT_EDIT : self::URL_PAYMENT_ADD
            ),
            'token' => $token,
            'paymentProfileId' => $paymentProfileId,
        ];
    }

    /**
     * @return AnetAPI\CustomerProfileMaskedType|null
     *
     * @throws AuthorizeNetException
     */
    private function getCustomerProfile(string $customerProfileId)
    {
        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setCustomerProfileId($customerProfileId);

        $controller = new AnetController\GetCustomerProfileController($request);
        $response = $this->executeWithApiResponse($controller);

        if (
            $response instanceof AnetAPI\GetCustomerProfileResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_OK
        ) {
            return $response->getProfile();
        }

        $messages = $response->getMessages()->getMessage();
        foreach ($messages as $message) {
            if ($message->getCode() === self::ERROR_RECORD_NOT_FOUND) {
                return null;
            }
        }

        throw $this->createException($response);
    }

    /**
     * Creates Authorize.Net Customer Profile from Client entity.
     * If customer already exists, the existing ID is returned.
     *
     *
     *
     * @throws AuthorizeNetException
     */
    private function createCustomerProfile(Client $client): string
    {
        $customerProfile = new AnetAPI\CustomerProfileType();
        $customerProfile->setMerchantCustomerId((string) $client->getId());
        $billingEmail = $client->getFirstBillingEmail();
        if ($billingEmail) {
            $customerProfile->setEmail($billingEmail);
        }

        $request = new AnetAPI\CreateCustomerProfileRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setProfile($customerProfile);

        $controller = new AnetController\CreateCustomerProfileController($request);
        $response = $this->executeWithApiResponse($controller);

        if (
            $response instanceof AnetAPI\CreateCustomerProfileResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_OK
        ) {
            return $response->getCustomerProfileId();
        }

        $messages = $response->getMessages()->getMessage();
        foreach ($messages as $message) {
            if ($message->getCode() === self::ERROR_DUPLICATE_PROFILE) {
                $customerProfileId = Strings::match($message->getText(), '~\d+~');
                if (! $customerProfileId) {
                    throw $this->createException($response);
                }

                $customerProfileId = reset($customerProfileId);
                if (null !== $this->getCustomerProfile($customerProfileId)) {
                    return $customerProfileId;
                }
                throw $this->createException($response);
            }
        }

        throw $this->createException($response);
    }

    /**
     * @throws AuthorizeNetException
     */
    private function getHostedProfilePageToken(string $customerProfileId, int $paymentPlanId): string
    {
        $request = new AnetAPI\GetHostedProfilePageRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setCustomerProfileId($customerProfileId);

        $options = [
            'hostedProfilePageBorderVisible' => 'true',
            'hostedProfileBillingAddressRequired' => 'false',
            'hostedProfileManageOptions' => 'showPayment',
            'hostedProfileReturnUrl' => $this->publicUrlGenerator->generate(
                'anet_subscribe_profile_return',
                [
                    'id' => $paymentPlanId,
                ]
            ),
        ];

        foreach ($options as $name => $value) {
            $request->addToHostedProfileSettings(
                (new AnetAPI\SettingType())
                    ->setSettingName($name)
                    ->setSettingValue($value)
            );
        }

        $controller = new AnetController\GetHostedProfilePageController($request);
        $response = $this->executeWithApiResponse($controller);

        if (
            $response instanceof AnetAPI\GetHostedProfilePageResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_OK
        ) {
            return $response->getToken();
        }

        throw $this->createException($response);
    }
}
