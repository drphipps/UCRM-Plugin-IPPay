<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\General;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BaseController extends Controller implements PermissionCheckedInterface
{
    use ContainerAwareTrait;

    const DEVICE_LOG = 'device-log';
    const ENTITY_LOG = 'system-log';
    const EMAIL_LOG = 'email-log';
    const CLIENT_LOG = 'client-log';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var bool
     */
    protected $sandbox;

    /**
     * Array of templates that should be sent to client (invalidated).
     *
     * @var array
     */
    private $invalidatedTemplates = [];

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->em = $container->get('doctrine.orm.default_entity_manager');
    }

    /**
     * Gets application option stored in database.
     */
    public function getOption(string $code, $default = null)
    {
        return $this->container->get(Options::class)->get($code, $default);
    }

    /**
     * Gets the default translator service and use it to translate the message.
     *
     * @param string $id         message to be translated
     * @param array  $parameters
     * @param string $domain
     * @param string $locale
     *
     * @return string
     */
    public function trans($id, $parameters = [], $domain = null, $locale = null)
    {
        return $this->get(TranslatorInterface::class)->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Gets the default translator service and use it to translate the message.
     *
     * @param string $id         message to be translated
     * @param int    $number
     * @param array  $parameters
     * @param string $domain
     * @param string $locale
     *
     * @return string
     */
    public function transChoice($id, $number, $parameters = [], $domain = null, $locale = null)
    {
        return $this->get(TranslatorInterface::class)->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Used for detection if application is in sandbox mode.
     */
    public function isSandbox(): bool
    {
        return (bool) $this->get(Options::class)->getGeneral(General::SANDBOX_MODE);
    }

    /**
     * @return bool
     */
    protected function isSpecialPermissionGranted(string $permissionName)
    {
        return $this->get(PermissionGrantedChecker::class)->isGrantedSpecial($permissionName);
    }

    /**
     * @return bool
     */
    protected function isPermissionGranted(string $permissionLevel, string $permissionName)
    {
        return $this->get(PermissionGrantedChecker::class)->isGranted($permissionLevel, $permissionName);
    }

    protected function denyAccessUnlessPermissionGranted(string $permissionLevel, string $permissionName)
    {
        $this->get(PermissionGrantedChecker::class)->denyAccessUnlessGranted($permissionLevel, $permissionName);
    }

    /**
     * Used to mark template as invalid for AJAX. If marked it's automatically included in AJAX response.
     */
    protected function invalidateTemplate(
        string $id,
        string $templatePath,
        array $parameters = [],
        bool $replace = false
    ): void {
        $this->invalidatedTemplates[$id] = [
            'template' => $templatePath,
            'parameters' => $parameters,
            'replace' => $replace,
        ];
    }

    protected function createAjaxResponse(
        array $parameters = [],
        bool $includeFlashBag = true
    ): JsonResponse {
        $parameters['templates'] = array_merge(
            $this->renderInvalidatedTemplates(),
            $parameters['templates'] ?? []
        );

        if ($includeFlashBag) {
            $parameters['flashBag'] = $this->container->get(SessionInterface::class)->getFlashBag()->all();
        }

        if (array_key_exists('url', $parameters) && is_array($parameters['url'])) {
            $parameters['shortcutParameters'] = $parameters['url'];
            $parameters['url'] = $this->generateUrl(
                $parameters['url']['route'],
                $parameters['url']['parameters']
            );
        }

        return new JsonResponse($parameters);
    }

    protected function createAjaxRedirectResponse(string $route, array $parameters = []): JsonResponse
    {
        return $this->createAjaxResponse(
            [
                'redirect' => $this->generateUrl($route, $parameters),
            ],
            false
        );
    }

    /**
     * Adds a flash message to the current session for type.
     */
    protected function addTranslatedFlash(
        $type,
        $message,
        $number = null,
        array $messageParameters = [],
        ?string $domain = null
    ): void {
        if (! in_array($type, ['success', 'info', 'warning', 'error'], true)) {
            @trigger_error(
                'Supported flash message types are: \'success\', \'info\', \'warning\' and \'error\'.',
                E_USER_DEPRECATED
            );
        }

        $message = null === $number
            ? $this->trans($message, $messageParameters, $domain)
            : $message = $this->transChoice($message, $number, $messageParameters, $domain);

        $this->addFlash($type, $message);
    }

    protected function getLogoDir(): string
    {
        return sprintf(
            '%s/../web/%s',
            $this->getRootDir(),
            ltrim($this->container->get('assets.packages')->getUrl('', 'logo'), '/')
        );
    }

    protected function getStampDir(): string
    {
        return sprintf(
            '%s/../web/%s',
            $this->getRootDir(),
            ltrim($this->container->get('assets.packages')->getUrl('', 'stamp'), '/')
        );
    }

    protected function getBackupDir(string $subdir = ''): string
    {
        return sprintf(
            '%s/data/backup/%s',
            $this->getRootDir(),
            ltrim($subdir, '/')
        );
    }

    protected function notDeleted($entity): void
    {
        if (method_exists($entity, 'isDeleted') && $entity->isDeleted()) {
            throw $this->createNotFoundException();
        }
    }

    private function renderInvalidatedTemplates(): array
    {
        $result = [];
        if (empty($this->invalidatedTemplates)) {
            return $result;
        }

        foreach ($this->invalidatedTemplates as $id => $templateSettings) {
            $result[$id] = [
                'source' => $this->container
                    ->get('twig')
                    ->render($templateSettings['template'], $templateSettings['parameters']),
                'replace' => $templateSettings['replace'] ?? false,
            ];
        }

        return $result;
    }

    private function getRootDir(): string
    {
        return rtrim($this->container->getParameter('kernel.root_dir'), '/');
    }

    /**
     * Used to prevent long running blocking AJAX requests which don't require session access
     * (http://konrness.com/php5/how-to-prevent-blocking-php-requests/).
     */
    protected function preventSessionBlocking(): void
    {
        if ($session = $this->get(SessionInterface::class)) {
            $session->save();
        }
    }
}
