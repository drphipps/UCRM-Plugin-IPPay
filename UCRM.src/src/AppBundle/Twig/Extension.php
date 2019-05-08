<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Twig;

use AppBundle\Component\ClientZone\PageMenu;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\HeaderNotification\DataProvider\HeaderNotificationsDataProvider;
use AppBundle\Component\Imagine\ImagineDataFileProvider;
use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use AppBundle\Component\Menu\Menu;
use AppBundle\Component\Shortcuts\Shortcuts;
use AppBundle\Component\UbiquitiSSO\AuthChecker;
use AppBundle\DataProvider\UcrmVersionDataProvider;
use AppBundle\Entity\DeviceOutageInterface;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\FileManager\CustomCssFileManager;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Service\BadgeFactory;
use AppBundle\Service\Encryption;
use AppBundle\Service\Invoice\InvoiceDueDateRenderer;
use AppBundle\Service\Options;
use AppBundle\Service\PersistentPathGenerator;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Service\ServiceCalculations;
use AppBundle\Util\DurationFormatter;
use AppBundle\Util\Email;
use AppBundle\Util\Formatter;
use AppBundle\Util\Helpers;
use AppBundle\Util\Json;
use AppBundle\Util\Mac;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Html;
use Nette\Utils\Strings as NStrings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Extension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * This class requires DI container, as loading the services directly would initialize them for every request
     * even when not needed.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter(
                'long2ip',
                function ($value) {
                    return long2ip($value);
                }
            ),
            new \Twig_SimpleFilter(
                'yesNo',
                function ($value) {
                    return $value ? 'Yes' : 'No';
                }
            ),
            new \Twig_SimpleFilter(
                'localizedCurrency',
                [$this, 'localizedCurrency']
            ),
            new \Twig_SimpleFilter('localizedDate', [$this, 'localizedDate']),
            new \Twig_SimpleFilter('localizedDateToday', [$this, 'localizedDateToday']),
            new \Twig_SimpleFilter('localizedNumber', [$this, 'localizedNumber']),
            new \Twig_SimpleFilter(
                'invoiceDueDate',
                [$this, 'invoiceDueDate'],
                [
                    'is_safe' => [
                        'html',
                    ],
                ]
            ),
            new \Twig_SimpleFilter('tariffPricePeriod', [$this, 'tariffPricePeriod']),
            new \Twig_SimpleFilter(
                'customFilter',
                [$this, 'customFilter'],
                [
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFilter(
                'bytesToSize',
                function ($bytes) {
                    return Helpers::bytesToSize((float) $bytes);
                }
            ),
            new \Twig_SimpleFilter('deviceOutage', [$this, 'deviceOutage']),
            new \Twig_SimpleFilter(
                'duration',
                function ($value, string $format = DurationFormatter::FULL) {
                    return $this->container->get(DurationFormatter::class)->format($value, $format);
                }
            ),
            new \Twig_SimpleFilter(
                'base64',
                function ($value) {
                    return base64_encode($value);
                }
            ),
            new \Twig_SimpleFilter(
                'encryptError',
                function ($value) {
                    return $this->container->get(Encryption::class)->encryptError($value);
                }
            ),
            new \Twig_SimpleFilter(
                'wrapAddress',
                function ($value) {
                    return Strings::wrapAddress($value);
                }
            ),
            new \Twig_SimpleFilter(
                'mailto',
                function (string $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $a = Html::el(
                            'a',
                            [
                                'href' => sprintf(
                                    'mailto:%s',
                                    htmlspecialchars($email ?? '', ENT_NOQUOTES)
                                ),
                            ]
                        );
                        $a->setText($email);

                        return (string) $a;
                    }

                    return htmlspecialchars($email ?? '', ENT_NOQUOTES);
                },
                [
                    'is_safe' => [
                        'html',
                    ],
                ]
            ),
            new \Twig_SimpleFilter(
                'invoiceStatusBadge',
                function (int $status, bool $draftHr = true) {
                    return $this->container->get(BadgeFactory::class)->createInvoiceStatusBadge(
                        $status,
                        $draftHr
                    );
                },
                [
                    'is_safe' => [
                        'html',
                    ],
                ]
            ),
            new \Twig_SimpleFilter(
                'quoteStatusBadge',
                function (int $status) {
                    return $this->container->get(BadgeFactory::class)->createQuoteStatusBadge(
                        $status
                    );
                },
                [
                    'is_safe' => [
                        'html',
                    ],
                ]
            ),
            new \Twig_SimpleFilter(
                'invoiceItemTotal',
                function (float $total, Invoice $invoice) {
                    return $invoice->getItemRounding() === FinancialInterface::ITEM_ROUNDING_STANDARD
                        ? $this->localizedCurrency($total, $invoice->getCurrency()->getCode())
                        : $this->localizedNumber($total);
                }
            ),
            new \Twig_SimpleFilter(
                'imagine_filter_data_uri',
                function (string $path, string $filter) {
                    return $this->imagineFilterDataUri($path, $filter);
                }
            ),
            new \Twig_SimpleFilter(
                'nbsp',
                function (string $str) {
                    $nbsp = html_entity_decode('&nbsp;');

                    return NStrings::replace($str, '/ /', $nbsp);
                }
            ),
            new \Twig_SimpleFilter(
                'truncate',
                function (string $s, int $maxLen = 20, string $append = "\xE2\x80\xA6") {
                    return NStrings::truncate($s, $maxLen, $append);
                }
            ),
            new \Twig_SimpleFilter(
                'mac',
                function (string $s) {
                    return Mac::formatView($s);
                }
            ),
            new \Twig_SimpleFilter(
                'linkify',
                function (string $url, ?string $target = null) {
                    $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
                    $parts = parse_url($sanitizedUrl);
                    $scheme = $parts['scheme'] ?? 'http';
                    if (! in_array(NStrings::lower($scheme), ['http', 'https'], true)) {
                        return htmlspecialchars($url, ENT_NOQUOTES);
                    }

                    if (! isset($parts['scheme'])) {
                        $sanitizedUrl = sprintf('http://%s', $sanitizedUrl);
                    }

                    if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
                        $a = Html::el(
                            'a',
                            [
                                'href' => htmlspecialchars($sanitizedUrl ?? '', ENT_NOQUOTES),
                            ]
                        );
                        if (null !== $target) {
                            $a->setAttribute('target', $target);
                            if (NStrings::lower($target) === '_blank') {
                                $a->setAttribute('rel', 'noopener noreferrer');
                            }
                        }
                        $a->setText($url);

                        return (string) $a;
                    }

                    return htmlspecialchars($url ?? '', ENT_NOQUOTES);
                },
                [
                    'is_safe' => [
                        'html',
                    ],
                ]
            ),
            new \Twig_SimpleFilter(
                'slugify',
                function (string $value, string $charList = null, bool $lower = true) {
                    return Strings::slugify($value, $charList, $lower);
                }
            ),
            new \Twig_SimpleFilter(
                'initials',
                function (string $name, string $separator = '', ?int $limit = 2) {
                    return Strings::initials($name, $separator, $limit);
                }
            ),
            new \Twig_SimpleFilter(
                'surnameInitials',
                function (string $name) {
                    return Strings::surnameInitials($name);
                }
            ),
            new \Twig_SimpleFilter(
                'emailArrayToString',
                function ($string) {
                    return Email::formatView($string);
                }
            ),
            new \Twig_SimpleFilter(
                'decodeJsonLeaveString',
                function (string $string) {
                    return Json::decodeJsonLeaveString($string);
                }
            ),
            new \Twig_SimpleFilter(
                'sanitizeFlashMessage',
                function (string $message) {
                    return Helpers::sanitizeFlashMessage($message);
                }
            ),
            new \Twig_SimpleFilter(
                'maskBankAccount',
                function ($value) {
                    return Strings::maskBankAccount($value);
                }
            ),
            new \Twig_SimpleFilter(
                'importError',
                function (array $data, string $type) {
                    return $this->container->get(ConstraintViolationTransformer::class)
                        ->toTranslatedString($data, $type);
                }
            ),
            new \Twig_SimpleFilter(
                'array_filter',
                function (array $data) {
                    return array_filter($data);
                }
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction(
                'menu',
                function () {
                    echo $this->container->get(Menu::class)->buildView();
                }
            ),
            new \Twig_SimpleFunction('clientZoneMenu', [$this->container->get(PageMenu::class), 'render']),
            new \Twig_SimpleFunction('shortcuts', [$this->container->get(Shortcuts::class), 'render']),
            new \Twig_SimpleFunction('grid', [$this, 'createGridView']),
            new \Twig_SimpleFunction('option', [$this->container->get(Options::class), 'get']),
            new \Twig_SimpleFunction('optionGeneral', [$this->container->get(Options::class), 'getGeneral']),
            new \Twig_SimpleFunction('isCurrentRoute', [$this, 'isCurrentRoute']),
            new \Twig_SimpleFunction('isCurrentController', [$this, 'isCurrentController']),
            new \Twig_SimpleFunction('tariffPricePeriod', [$this, 'tariffPricePeriod']),
            new \Twig_SimpleFunction('publicUrl', [$this, 'publicUrl']),
            new \Twig_SimpleFunction('isSpecialPermissionGranted', [$this, 'isSpecialPermissionGranted']),
            new \Twig_SimpleFunction('isViewPermissionGranted', [$this, 'isViewPermissionGranted']),
            new \Twig_SimpleFunction('isEditPermissionGranted', [$this, 'isEditPermissionGranted']),
            new \Twig_SimpleFunction(
                'isSandbox',
                function () {
                    return (bool) $this->container->get(Options::class)->getGeneral(General::SANDBOX_MODE);
                }
            ),
            new \Twig_SimpleFunction(
                'serviceTotalPrice',
                function (Service $service) {
                    return $this->container->get(ServiceCalculations::class)->getTotalPrice($service);
                }
            ),
            new \Twig_SimpleFunction(
                'serviceDiscountPrice',
                function (Service $service) {
                    return $this->container->get(ServiceCalculations::class)->getDiscountPrice($service);
                }
            ),
            new \Twig_SimpleFunction('organizationLogo', [$this, 'organizationLogo']),
            new \Twig_SimpleFunction('loginBanner', [$this, 'loginBanner']),
            new \Twig_SimpleFunction('getCurrentUcrmVersion', [$this, 'getCurrentUcrmVersion']),
            new \Twig_SimpleFunction('isUcrmUpdateAvailable', [$this, 'isUcrmUpdateAvailable']),
            new \Twig_SimpleFunction(
                'isDemo',
                function () {
                    return Helpers::isDemo();
                }
            ),
            new \Twig_SimpleFunction(
                'form_autofill_hack',
                function (\Twig_Environment $env) {
                    return $env->render('form/autofill_hack.html.twig');
                },
                [
                    'is_safe' => [
                        'html',
                    ],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'getHeaderNotificationsLastUnreadTimestamp',
                function () {
                    return $this->container->get(HeaderNotificationsDataProvider::class)->getLastUnreadTimestamp();
                }
            ),
            new \Twig_SimpleFunction(
                'getCustomCssHash',
                function () {
                    return $this->container->get(CustomCssFileManager::class)->getSanitizedHash();
                }
            ),
            new \Twig_SimpleFunction(
                'isUBNTAuthenticated',
                function () {
                    return $this->container->get(AuthChecker::class)->isUserAuthenticated();
                }
            ),
            new \Twig_SimpleFunction(
                'persistentPath',
                function (
                    array $persistentParameters,
                    string $route,
                    array $parameters = [],
                    int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
                ) {
                    return $this->container->get(PersistentPathGenerator::class)->generate(
                        $persistentParameters,
                        $route,
                        $parameters,
                        $referenceType
                    );
                }
            ),
        ];
    }

    public function getTests(): array
    {
        return [
            new \Twig_SimpleTest('instanceof', [$this, 'isInstanceOf']),
            new \Twig_SimpleTest(
                'numeric',
                function ($value) {
                    return is_numeric($value);
                }
            ),
            new \Twig_SimpleTest(
                'in_strict',
                function ($needle, $haystack) {
                    return in_array($needle, $haystack, true);
                }
            ),
            new \Twig_SimpleTest(
                'first',
                function (array $loop, int $width = null) {
                    return
                        $loop['first']
                        || (
                            $width && $loop['index'] !== 0
                            && (($loop['index'] - 1) % $width) === 0
                        );
                }
            ),
            new \Twig_SimpleTest(
                'last',
                function (array $loop, int $width = null) {
                    return $loop['last'] || ($width && ($loop['index'] % $width) === 0);
                }
            ),
            new \Twig_SimpleTest(
                'versionNewerThan',
                function (string $version1, string $version2) {
                    return version_compare($version1, $version2, '>');
                }
            ),
            new \Twig_SimpleTest(
                'today',
                function (\DateTimeInterface $dateTime) {
                    return $dateTime->format('Y-m-d') === (new \DateTime('today'))->format('Y-m-d');
                }
            ),
            new \Twig_SimpleTest(
                'tomorrow',
                function (\DateTimeInterface $dateTime) {
                    return $dateTime->format('Y-m-d') === (new \DateTime('tomorrow'))->format('Y-m-d');
                }
            ),
        ];
    }

    /**
     * @return string
     */
    public function customFilter(\Twig_Environment $env, $value, string $filter, array $parameters = [])
    {
        $twigFilter = $env->getFilter($filter);

        if (! $twigFilter) {
            throw new \InvalidArgumentException(sprintf('Unknown filter %s.', $filter));
        }

        array_unshift($parameters, $value);

        return call_user_func_array($twigFilter->getCallable(), $parameters);
    }

    public function localizedCurrency($value, $currency = null, $locale = null): string
    {
        $value = $this->container->get(Formatter::class)->formatCurrency($value, $currency, $locale);
        $minus = html_entity_decode('&minus;') . html_entity_decode('&nbsp;');
        $value = NStrings::replace($value, '/^-/', $minus);

        return $value;
    }

    public function localizedNumber($value, string $style = 'default'): string
    {
        return $this->container->get(Formatter::class)->formatNumber($value, $style);
    }

    public function localizedDate($value, $dateFormat = 'default', $timeFormat = 'medium'): string
    {
        return $this->container->get(Formatter::class)->formatDate($value, $dateFormat, $timeFormat);
    }

    public function localizedDateToday(
        \DateTimeInterface $value,
        $dateFormatToday = Formatter::DEFAULT,
        $timeFormatToday = Formatter::SHORT,
        $dateFormatOther = Formatter::DEFAULT,
        $timeFormatOther = Formatter::SHORT
    ): string {
        if ($value->format('Y-m-d') === (new \DateTime())->format('Y-m-d')) {
            return $this->container->get(Formatter::class)->formatDate($value, $dateFormatToday, $timeFormatToday);
        }

        return $this->container->get(Formatter::class)->formatDate($value, $dateFormatOther, $timeFormatOther);
    }

    public function invoiceDueDate(Invoice $invoice): string
    {
        return $this->container->get(InvoiceDueDateRenderer::class)->renderDueDate($invoice);
    }

    public function getName(): string
    {
        return 'app.twig_extension';
    }

    /**
     * Returns true if $routeName is current route otherwise false.
     *
     * @param string|array $routeName
     */
    public function isCurrentRoute($routeName): bool
    {
        $route = $this->container->get(RequestStack::class)->getCurrentRequest()->get('_route');

        return in_array($route, (array) $routeName, true);
    }

    /**
     * Returns true if $routeName is in current controller otherwise false.
     */
    public function isCurrentController(string $controller): bool
    {
        $currentController = $this->container->get(RequestStack::class)->getCurrentRequest()->get('_controller');
        $currentController = NStrings::replace($currentController, '/::.+$/', '');

        return $currentController === $controller;
    }

    public function createGridView(Grid $grid, ?bool $ajaxEnabled = null): void
    {
        if ($ajaxEnabled !== null) {
            $grid->setAjaxEnabled($ajaxEnabled);
        }
        $wrapper = Html::el(
            'div',
            [
                'id' => $grid->getAjaxRequestIdentifier(),
            ]
        );
        $wrapper->setHtml($grid->createView());

        echo $wrapper;
    }

    public function tariffPricePeriod(int $period): string
    {
        if (! array_key_exists($period, TariffPeriod::PERIOD_REPLACE_STRING)) {
            throw new \InvalidArgumentException(sprintf('Unknown period %s.', $period));
        }

        return $this->container->get(TranslatorInterface::class)->trans(TariffPeriod::PERIOD_REPLACE_STRING[$period]);
    }

    public function deviceOutage(DeviceOutageInterface $outage): string
    {
        $duration = $this->container->get(DurationFormatter::class)->format($outage->getDuration());
        $start = $this->localizedDate($outage->getOutageStart(), Formatter::DEFAULT, Formatter::SHORT);
        if ($outage->getOutageEnd()) {
            $end = $this->localizedDate($outage->getOutageEnd(), Formatter::DEFAULT, Formatter::SHORT);

            return sprintf('%s - %s (%s)', $start, $end, $duration);
        }

        return sprintf('%s (%s)', $start, $duration);
    }

    public function isInstanceOf($var, string $instance): bool
    {
        return $var instanceof $instance;
    }

    public function isSpecialPermissionGranted(string $permissionName): bool
    {
        return $this->container->get(PermissionGrantedChecker::class)->isGrantedSpecial($permissionName);
    }

    public function isViewPermissionGranted(string $permissionName): bool
    {
        return $this->container->get(PermissionGrantedChecker::class)->isGranted(Permission::VIEW, $permissionName);
    }

    public function isEditPermissionGranted(string $permissionName): bool
    {
        return $this->container->get(PermissionGrantedChecker::class)->isGranted(Permission::EDIT, $permissionName);
    }

    public function organizationLogo(): ?string
    {
        $organization = $this->container->get(EntityManagerInterface::class)
            ->getRepository(Organization::class)
            ->getSelectedOrAlone();

        return $organization && $organization->getLogo()
            ? $organization->getLogo()
            : null;
    }

    public function loginBanner(): ?string
    {
        return $this->container->get(Options::class)->getGeneral(General::APPEARANCE_LOGIN_BANNER);
    }

    public function getCurrentUcrmVersion(): string
    {
        return $this->container->get(UcrmVersionDataProvider::class)->getCurrentVersion();
    }

    public function isUcrmUpdateAvailable(): string
    {
        return $this->container->get(UcrmVersionDataProvider::class)->isUpdateAvailable();
    }

    public function imagineFilterDataUri(string $path, string $filter): string
    {
        return $this->container->get(ImagineDataFileProvider::class)->getDataUri($path, $filter);
    }

    public function publicUrl(
        string $route,
        array $parameters = [],
        bool $forceHttps = false,
        ?int $port = null
    ): string {
        try {
            return $this->container->get(PublicUrlGenerator::class)->generate($route, $parameters, $forceHttps, $port);
        } catch (PublicUrlGeneratorException $exception) {
            return '';
        }
    }
}
