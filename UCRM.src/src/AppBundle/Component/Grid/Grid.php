<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid;

use AppBundle\Component\Annotation\Persistent;
use AppBundle\Component\BaseComponent;
use AppBundle\Component\Elastic\Search;
use AppBundle\Component\Grid\Button\ActionButton;
use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Column\SortableColumnInterface;
use AppBundle\Component\Grid\Component\MultiAction;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Filter\BoolFilterField;
use AppBundle\Component\Grid\Filter\DateFilterField;
use AppBundle\Component\Grid\Filter\ElasticFilterField;
use AppBundle\Component\Grid\Filter\MultipleSelectFilterField;
use AppBundle\Component\Grid\Filter\NullBoolFilterField;
use AppBundle\Component\Grid\Filter\NumberRangeFilter;
use AppBundle\Component\Grid\Filter\RadioFilterField;
use AppBundle\Component\Grid\Filter\RangeFilterInterface;
use AppBundle\Component\Grid\Filter\SelectFilterField;
use AppBundle\Component\Grid\Filter\TextFilterField;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Exception\ElasticsearchException;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Elastica\Exception\ConnectionException;
use Elastica\Exception\ResponseException;
use Nette\Utils\IHtmlString;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Grid extends BaseComponent
{
    use GridColumnsTrait;
    use GridFiltersTrait;

    public const GRID_TYPE_DEFAULT = 'default';
    public const GRID_TYPE_SMALL = 'small';

    public const ASC = 'ASC';
    public const DESC = 'DESC';

    public const NO_PREFIX = '_noprefix_';

    public const SORT_ARRAY = 'sort:array';
    public const SORT_DEFAULT = 'sort:default';

    public const MULTI_ACTION = 'doMultiAction';
    public const MULTI_ACTION_IDS = 'doMultiActionIds[]';
    public const MULTI_ACTION_SELECT_ALL = 'doMultiActionSelectAll';

    public const CSRF_TOKEN = 'csrfToken';

    public const ITEMS_PER_PAGE = 20;
    public const ITEMS_PER_PAGE_SMALL = 5;

    public const ITEMS_PER_PAGE_CHOICES = [
        10 => 10,
        20 => 20,
        50 => 50,
        100 => 100,
    ];
    public const ITEMS_PER_PAGE_CHOICES_SMALL = [
        5 => 5,
        10 => 10,
        15 => 15,
        30 => 30,
    ];

    public const ITEMS_PER_PAGE_KEY = 'grid-items-per-page';
    public const ITEMS_PER_PAGE_KEY_SMALL = 'grid-items-per-page-small';
    public const SORT_BY_KEY = 'grid-sort-by';

    /**
     * @var string
     */
    private $id;

    /**
     * @Persistent()
     *
     * @var string|int
     */
    protected $page = 1;

    /**
     * @Persistent()
     *
     * @var string|int|null
     */
    protected $itemsPerPage;

    /**
     * @Persistent()
     *
     * @var string
     */
    protected $sortDirection = self::ASC;

    /**
     * @Persistent()
     *
     * @var string|null
     */
    protected $sortBy;

    /**
     * @Persistent()
     *
     * @var array
     */
    protected $filter = [];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var QueryBuilder
     */
    private $model;

    /**
     * @var callable|null
     */
    private $postFetchCallback;

    /**
     * @var QueryBuilder
     */
    private $exportModel;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var array
     */
    private $identifier = [];

    /**
     * @var array|ActionButton[]
     */
    private $actionButtons = [];

    /**
     * @var string
     */
    private $actionsColumnCssClass = '';

    /**
     * @var Paginator
     */
    private $paginator;

    /**
     * @var string|array|null
     */
    private $defaultSortBy;

    /**
     * @var string
     */
    private $defaultSortDirection = self::ASC;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $routerUrlParams = [];

    /**
     * @var array
     */
    public $hiddenInputRouterUrlParams = [];

    /**
     * @var string|null
     */
    private $routerUrlSuffix;

    /**
     * @var bool
     */
    private $ajaxEnabled = true;

    /**
     * @var Search
     */
    private $elasticsearch;

    /**
     * @var string|callable|null
     */
    private $rowUrlRoute;

    /**
     * @var array
     */
    private $rowUrlParams = [];

    /**
     * @var bool|callable
     */
    private $rowUrlIsModal = false;

    /**
     * @Persistent
     *
     * @var string|null
     */
    protected $csrfToken;

    /**
     * @Persistent
     *
     * @var string|null
     */
    protected $doMultiAction;

    /**
     * @Persistent
     *
     * @var array|null
     */
    protected $doMultiActionIds;

    /**
     * @Persistent
     *
     * @var bool|string
     */
    protected $doMultiActionSelectAll = false;

    /**
     * @var array
     */
    private $multiActions = [];

    /**
     * @var array
     */
    private $multiActionGroups = [];

    /**
     * @var array
     */
    private $multiActionsRender = [];

    /**
     * @var string|null
     */
    private $textNoRows;

    /**
     * @var bool
     */
    private $showHeader = true;

    /**
     * @var bool
     */
    private $showFooter = true;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var string
     */
    private $type = self::GRID_TYPE_DEFAULT;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Grid
    {
        $this->id = $id;

        return $this;
    }

    public function getShowHeader(): bool
    {
        return $this->showHeader;
    }

    public function setShowHeader(bool $showHeader): void
    {
        $this->showHeader = $showHeader;
    }

    public function getShowFooter(): bool
    {
        return $this->showFooter;
    }

    public function setShowFooter(bool $showFooter): void
    {
        $this->showFooter = $showFooter;
    }

    public function setModel(QueryBuilder $model)
    {
        $this->model = $model;
        $this->exportModel = clone $this->model;
    }

    public function setPostFetchCallback(callable $postFetchCallback = null)
    {
        $this->postFetchCallback = $postFetchCallback;
    }

    public function attached(): void
    {
        parent::attached();

        $this->validateProperties();
    }

    public function getData(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $finalResult = [];
        $rows = $this->fetchRows();
        foreach ($rows as $row) {
            foreach ($this->columns as $column) {
                if ($column->getQueryIdentifier()) {
                    $row[$column->getName()] = $column->render($row[$column->getName()]);
                } else {
                    $row[$column->getName()] = $column->render($row);
                }
            }

            $finalResult[] = $row;
        }

        $this->data = $finalResult;

        return $finalResult;
    }

    private function fetchRows(): array
    {
        $this->solveSelectColumns($this->model);
        $this->solveFilters($this->model);
        $sorted = $this->solveSort($this->model);
        $this->initializePaginator();

        if ($sorted === self::SORT_ARRAY) {
            $result = $this->model
                ->getQuery()
                ->getResult();

            $sort = $this->getSort()['sortBy'];
            uasort(
                $result,
                function ($a, $b) use ($sort) {
                    $posA = array_search($a[$sort['column']], $sort['data']);
                    $posB = array_search($b[$sort['column']], $sort['data']);

                    return $posA - $posB;
                }
            );

            return array_slice($result, ($this->page - 1) * $this->itemsPerPage, $this->itemsPerPage);
        }

        $result = $this->model
            ->getQuery()
            ->setFirstResult(($this->page - 1) * $this->itemsPerPage)
            ->setMaxResults((int) $this->itemsPerPage)
            ->getResult();

        if ($this->postFetchCallback) {
            ($this->postFetchCallback)($result);
        }

        return $result;
    }

    public function getExportIds(): array
    {
        $this->solveSelectColumns($this->exportModel);
        $this->solveFilters($this->exportModel);
        $this->solveSort($this->exportModel);

        $results = $this->exportModel->getQuery()->getResult();
        $exportIds = [];

        foreach ($results as $result) {
            if (is_object($result) && method_exists($result, 'getId')) {
                $exportIds[] = $result->getId();
            } elseif (isset($result[0]) && method_exists($result[0], 'getId')) {
                $exportIds[] = $result[0]->getId();
            }
        }

        return $exportIds;
    }

    public function addIdentifier(string $alias, string $identifier)
    {
        $this->identifier[$identifier] = $alias;
    }

    /**
     * @param string|int|array $value
     */
    public function addRouterUrlParam(string $key, $value, bool $includeHiddenInput = false)
    {
        $this->routerUrlParams[self::NO_PREFIX . $key] = $value;
        if ($includeHiddenInput) {
            $this->hiddenInputRouterUrlParams[$key] = $value;
        }
    }

    public function setRouterUrlSuffix(string $suffix = null)
    {
        $this->routerUrlSuffix = $suffix;
    }

    /**
     * @param string|callable $route
     * @param string          $rootAlias
     */
    public function setRowUrl($route, string $rootAlias = null, array $routeParameters = [])
    {
        $rootAlias = $rootAlias ?? $this->model->getRootAliases()[0];
        $params = $this->getRouteParams($rootAlias, $routeParameters);

        $this->rowUrlRoute = $route;
        $this->rowUrlParams = $params;
    }

    /**
     * @return string|null
     */
    public function generateRowUrl(array $row)
    {
        $routeName = $this->rowUrlRoute;
        if (is_callable($routeName)) {
            $routeName = $routeName($row);
        }

        if (null === $routeName) {
            return null;
        }

        $routeParameters = [];
        foreach ($this->rowUrlParams as $key => $rowIdentifier) {
            $routeParameters[$key] = $row[$rowIdentifier];
        }

        return $this->router->generate($routeName, $routeParameters);
    }

    /**
     * @param bool|callable $isModal
     */
    public function setRowUrlIsModal($isModal = true)
    {
        $this->rowUrlIsModal = $isModal;
    }

    public function isRowUrlModal(array $row = null): bool
    {
        $isModal = $this->rowUrlIsModal;
        if (is_callable($isModal)) {
            $isModal = $isModal($row);
        }

        return (bool) $isModal;
    }

    public function getRowId(): string
    {
        return sprintf('%s_id', $this->model->getRootAliases()[0]);
    }

    public function createView(): string
    {
        if (! $this->isAttached) {
            throw new \LogicException('Grid is not attached. Call Grid::attached() in grid factory.');
        }
        $this->prepareFilters();

        if (is_numeric($this->itemsPerPage) && $this->itemsPerPage > 0) {
            $this->itemsPerPage = (int) $this->itemsPerPage;
            if (in_array($this->itemsPerPage, $this->getItemsPerPageChoices(), true)) {
                $sessionKey = $this->getType() === self::GRID_TYPE_SMALL
                    ? self::ITEMS_PER_PAGE_KEY_SMALL
                    : self::ITEMS_PER_PAGE_KEY;
                $this->session->set($sessionKey, $this->itemsPerPage);
            }
        } else {
            $this->itemsPerPage = $this->getDefaultItemsPerPage();
        }

        return $this->twig->render(
            'components/grid.html.twig',
            [
                'grid' => $this,
                'ajaxEnabled' => $this->ajaxEnabled,
            ]
        );
    }

    public function getDefaultItemsPerPage(): int
    {
        $sessionKey = $this->getType() === self::GRID_TYPE_SMALL
            ? self::ITEMS_PER_PAGE_KEY_SMALL
            : self::ITEMS_PER_PAGE_KEY;

        $defaultItemsPerPage = $this->session->get($sessionKey)
            ?? ($this->getType() === self::GRID_TYPE_SMALL ? self::ITEMS_PER_PAGE_SMALL : self::ITEMS_PER_PAGE);

        if (is_int($this->itemsPerPage) && $defaultItemsPerPage !== $this->itemsPerPage) {
            if (in_array($this->itemsPerPage, $this->getItemsPerPageChoices(), true)) {
                $this->session->set($sessionKey, $this->itemsPerPage);
            }

            return $this->itemsPerPage;
        }

        return $defaultItemsPerPage;
    }

    public function setItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    public function getItemsPerPageChoices(): array
    {
        return $this->getType() === self::GRID_TYPE_SMALL
            ? self::ITEMS_PER_PAGE_CHOICES_SMALL
            : self::ITEMS_PER_PAGE_CHOICES;
    }

    /**
     * @return array|ActionButton[]
     */
    public function getActionButtons(): array
    {
        return $this->actionButtons;
    }

    public function getActionsColumnCssClass(): string
    {
        return $this->actionsColumnCssClass;
    }

    public function setActionsColumnCssClass(string $actionsColumnCssClass): void
    {
        $this->actionsColumnCssClass = $actionsColumnCssClass;
    }

    public function generateActionButtonUrl(ActionButton $button, array $row): string
    {
        $routeParameters = [];
        foreach ($button->getRouteParameters() as $key => $rowIdentifier) {
            if (is_string($rowIdentifier) && array_key_exists($rowIdentifier, $row)) {
                $routeParameters[$key] = $row[$rowIdentifier];
            } else {
                $routeParameters[$key] = $rowIdentifier;
            }
        }

        return $this->router->generate($button->getRoute(), $routeParameters);
    }

    /**
     * @return int|null
     */
    public function getDataId(ActionButton $button, array $row)
    {
        foreach ($button->getRouteParameters() as $key => $rowIdentifier) {
            if ($key === 'id' && array_key_exists($key, $row)) {
                return $row[$rowIdentifier];
            }
        }

        return null;
    }

    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    public function generatePaginatorUrl(int $page): string
    {
        $parameters = [
            'page' => $page,
        ];

        if ($this->itemsPerPage === $this->getDefaultItemsPerPage()) {
            $parameters['itemsPerPage'] = null;
        }

        return $this->generateUrl($parameters);
    }

    public function generateSortColumnUrl(BaseColumn $column): string
    {
        $sort = $this->getSort();
        $sortDirection = ($sort['sortBy'] ?? null === $column->getName())
            ? $this->reverseSortDirection($sort['sortDirection'])
            : $this->defaultSortDirection;

        return $this->generateUrl(
            [
                'sortDirection' => $sortDirection,
                'sortBy' => $column->getName(),
            ]
        );
    }

    public function getReversedSortForColumn(BaseColumn $column): string
    {
        return $this->sortBy == $column->getName()
            ? $direction = $this->reverseSortDirection($this->sortDirection)
            : $direction = $this->reverseSortDirection($this->defaultSortDirection);
    }

    /**
     * @param string|array|null $sortBy
     */
    public function setDefaultSort($sortBy, string $direction = self::ASC)
    {
        $this->defaultSortBy = $sortBy;
        $this->defaultSortDirection = $direction;
    }

    /**
     * @param string $permissionName
     * @param string $permissionLevel
     */
    public function addActionButton(
        string $route,
        array $routeParameters = [],
        string $permissionName = null,
        string $permissionLevel = null
    ): ActionButton {
        $rootAlias = $this->model->getRootAliases()[0];
        $routeParameters = $this->getRouteParams($rootAlias, $routeParameters);
        $button = new ActionButton($route, $routeParameters);

        if ($permissionName && $permissionName) {
            $button->addRenderCondition(
                function () use ($permissionName, $permissionLevel) {
                    return $this->permissionGrantedChecker->isGranted(
                        $permissionLevel,
                        $permissionName
                    );
                }
            );
        }

        return $this->actionButtons[] = $button;
    }

    /**
     * @param string $permissionName
     */
    public function addEditActionButton(
        string $route,
        array $routeParameters = [],
        string $permissionName = null,
        bool $isModal = false
    ): ActionButton {
        $button = $this->addActionButton($route, $routeParameters, $permissionName, Permission::EDIT);
        $button->setIcon('ucrm-icon--edit');
        $button->setIsModal($isModal);

        return $button;
    }

    /**
     * @param string $permissionName
     */
    public function addDeleteActionButton(
        string $route,
        array $routeParameters = [],
        string $permissionName = null,
        array $cssClasses = []
    ): ActionButton {
        $button = $this->addActionButton($route, $routeParameters, $permissionName, Permission::EDIT);
        $button->setIcon('ucrm-icon--trash');
        $button->setCssClasses(
            array_merge(
                $cssClasses,
                [
                    'button--danger',
                ]
            )
        );
        $button->setData(
            [
                'confirm' => $this->translator->trans('Do you really want to delete this row?'),
            ]
        );

        return $button;
    }

    public function getModel(): QueryBuilder
    {
        return $this->model;
    }

    /**
     * @param string $sortDirection
     */
    public function reverseSortDirection($sortDirection = self::ASC): string
    {
        return $sortDirection == self::ASC ? self::DESC : self::ASC;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setTwig(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function setCsrfTokenManager(CsrfTokenManagerInterface $csrfTokenManager): void
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @param string $columnName
     */
    public function isColumnSorted($columnName): bool
    {
        $sort = $this->getSort();
        if (count($sort)) {
            return $columnName == $sort['sortBy'];
        }

        return false;
    }

    public function getSort(): array
    {
        $sort = [];
        $column = null;
        if ($this->sortBy) {
            $column = $this->getColumn($this->sortBy);
        }

        $defaultSorts = $this->session->get(self::SORT_BY_KEY) ?? [];
        $defaultSort = $defaultSorts[$this->id] ?? null;

        if ($column instanceof SortableColumnInterface && $column->isSortable()) {
            $sort['sortBy'] = $this->sortBy;
            $sort['sortDirection'] = $this->sortDirection;
            $sort['sortCallback'] = $column->getOrderByCallback();

            $defaultSorts[$this->id] = [
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
            ];
            $this->session->set(self::SORT_BY_KEY, $defaultSorts);
        } elseif ($defaultSort && ($column = $this->getColumn($defaultSort['sortBy']))) {
            $sort['sortBy'] = $defaultSort['sortBy'];
            $sort['sortDirection'] = $defaultSort['sortDirection'];

            if ($column instanceof SortableColumnInterface && $column->isSortable()) {
                $sort['sortCallback'] = $column->getOrderByCallback();
            }
        } elseif ($this->defaultSortBy) {
            $sort['sortBy'] = $this->defaultSortBy;
            $sort['sortDirection'] = $this->defaultSortDirection;

            $columnName = $this->defaultSortBy;
            if (is_array($columnName)) {
                $columnName = $columnName['column'];
            }

            $column = $this->getColumn($columnName);
            if ($column instanceof SortableColumnInterface && $column->isSortable()) {
                $sort['sortCallback'] = $column->getOrderByCallback();
            }
        }

        return $sort;
    }

    public function isFilterActive(): bool
    {
        return (bool) count((array) $this->filter);
    }

    public function generateFilterAction(): string
    {
        return $this->generateUrl(
            [
                'do' => 'filter',
            ]
        );
    }

    public function generateAjaxFilterAction(): string
    {
        return $this->generateUrl(
            [
                'do' => 'filter',
            ],
            false
        );
    }

    public function generateAjaxPureAction(): string
    {
        return $this->generateUrl(
            [
                'do' => null,
            ],
            false
        );
    }

    /**
     * Adds right columns to query builder.
     */
    private function solveSelectColumns(QueryBuilder $model)
    {
        $columns = [];
        foreach ($this->columns as $column) {
            // Custom column doesn't have query identifier
            if ($column->getQueryIdentifier()) {
                $columns[] = sprintf('%s as %s', $column->getQueryIdentifier(), $column->getName());
            }
        }

        foreach ($this->identifier as $alias => $identifier) {
            $columns[] = sprintf('%s as %s', $alias, $identifier);
        }

        $model->addSelect($columns);
    }

    /**
     * Sets right sort to query builder.
     */
    private function solveSort(QueryBuilder $model): string
    {
        $sort = $this->getSort();
        if ($sort) {
            if (is_array($sort['sortBy'])) {
                return self::SORT_ARRAY;
            }

            if (isset($sort['sortCallback'])) {
                $sort['sortCallback']($model, $sort['sortDirection']);
            } else {
                $model->orderBy($sort['sortBy'], $sort['sortDirection']);
            }
        }

        return self::SORT_DEFAULT;
    }

    /**
     * Sets right filter to query builder.
     */
    private function solveFilters(QueryBuilder $model)
    {
        $filterValues = [];
        foreach ($this->filters as $filter) {
            if (null !== $filter->getDefaultValue()) {
                $filterValues[$filter->getName()] = $filter->getDefaultValue();
            }
        }

        $this->filter = array_merge($filterValues, (array) $this->filter);
        if (! count($this->filter)) {
            return;
        }

        $this->prepareFilters();

        foreach ($this->filter as $name => $value) {
            if (! isset($this->filters[$name])) {
                continue;
            }
            $filter = $this->filters[$name];
            switch (get_class($filter)) {
                case ElasticFilterField::class:
                    if (empty($value)) {
                        break;
                    }

                    try {
                        $results = $this->elasticsearch->search($filter->getElasticType(), $value, true);
                    } catch (ResponseException | ConnectionException | ElasticsearchException $exception) {
                        $this->session->getFlashBag()->add(
                            'error',
                            $this->translator->trans(
                                'Could not process search because of Elasticsearch error: %error%',
                                [
                                    '%error%' => $exception->getMessage(),
                                ]
                            )
                        );

                        break;
                    }

                    $queryIdentifier = $filter->getQueryIdentifier();
                    $model->andWhere(sprintf('%s IN (:searchResults)', $queryIdentifier))
                        ->setParameter(':searchResults', $results);

                    $this->setDefaultSort(
                        [
                            'column' => array_key_exists($queryIdentifier, $this->identifier)
                                ? $this->identifier[$queryIdentifier]
                                : $queryIdentifier,
                            'data' => $results,
                        ],
                        self::DESC
                    );

                    break;
                case TextFilterField::class:
                    $model
                        ->andWhere(sprintf('%s LIKE :%s', $filter->getQueryIdentifier(), $filter->getName()))
                        ->setParameter($filter->getName(), '%' . $value . '%');

                    break;
                case DateFilterField::class:
                    if (! $value) {
                        break;
                    }

                    try {
                        if ($filter->isRange()) {
                            $from = $filter->getRangeFrom() ? new \DateTime($filter->getRangeFrom()) : null;
                            if ($filter->getRangeTo()) {
                                $to = new \DateTime($filter->getRangeTo());
                                $to->modify('+1 day');
                            } else {
                                $to = null;
                            }

                            if ($from === null && $to === null) {
                                break;
                            }
                        } else {
                            $from = new \DateTime($value);
                            $to = clone $from;
                            $to->modify('+1 day');
                        }
                    } catch (\Exception $e) {
                        break;
                    }

                    if ($from) {
                        $fromParamName = sprintf('%s_from', $filter->getName());
                        $model
                            ->andWhere(
                                sprintf('%s >= :%s', $filter->getQueryIdentifier(), $fromParamName)
                            )
                            ->setParameter($fromParamName, $from, UtcDateTimeType::NAME);
                    }

                    if ($to) {
                        $toParamName = sprintf('%s_to', $filter->getName());
                        $model
                            ->andWhere(sprintf('%s < :%s', $filter->getQueryIdentifier(), $toParamName))
                            ->setParameter($toParamName, $to, UtcDateTimeType::NAME);
                    }

                    break;
                case MultipleSelectFilterField::class:
                    if (empty($value)) {
                        break;
                    }

                    if ($filter->getFilterCallback()) {
                        $filter->getFilterCallback()($model, $value, $filter);
                    } else {
                        $model
                            ->andWhere(
                                sprintf(
                                    '%s IN (:%s)',
                                    $filter->getQueryIdentifier(),
                                    $filter->getName()
                                )
                            )
                            ->setParameter($filter->getName(), $value);
                    }

                    break;
                case SelectFilterField::class:
                    if ($value === '') {
                        break;
                    }

                    if ($filter->getFilterCallback()) {
                        $filter->getFilterCallback()($model, $value, $filter);
                    } else {
                        $model
                            ->andWhere(sprintf('%s = :%s', $filter->getQueryIdentifier(), $filter->getName()))
                            ->setParameter($filter->getName(), (int) $value);
                    }

                    break;
                case BoolFilterField::class:
                    if ($value === '') {
                        break;
                    }
                    $value = $value === 't' ? true : false;
                    $model
                        ->andWhere(sprintf('%s = :%s', $filter->getQueryIdentifier(), $filter->getName()))
                        ->setParameter($filter->getName(), $value);

                    break;
                case NullBoolFilterField::class:
                    if ($value === '') {
                        break;
                    }
                    $value = $value === 't' ? true : false;
                    if ($value) {
                        $model->andWhere(sprintf('%s IS NOT NULL', $filter->getQueryIdentifier()));
                    } else {
                        $model->andWhere(sprintf('%s IS NULL', $filter->getQueryIdentifier()));
                    }

                    break;
                case RadioFilterField::class:
                    if ($value === '') {
                        break;
                    }

                    if ($filter->isNullFilter()) {
                        if ($value) {
                            $model->andWhere(sprintf('%s IS NULL', $filter->getQueryIdentifier()));
                        } else {
                            $model->andWhere(sprintf('%s IS NOT NULL', $filter->getQueryIdentifier()));
                        }
                    } else {
                        $model
                            ->andWhere(sprintf('%s = :%s', $filter->getQueryIdentifier(), $filter->getName()))
                            ->setParameter($filter->getName(), $value);
                    }

                    break;
                case NumberRangeFilter::class:
                    $from = $filter->getRangeFrom();
                    $to = $filter->getRangeTo();

                    if ($from === null && $to === null) {
                        break;
                    }

                    if (is_numeric($from)) {
                        $fromParamName = sprintf('%s_from', $filter->getName());
                        $model
                            ->andWhere(
                                sprintf('%s >= :%s', $filter->getQueryIdentifier(), $fromParamName)
                            )
                            ->setParameter($fromParamName, $from);
                    }

                    if (is_numeric($to)) {
                        $toParamName = sprintf('%s_to', $filter->getName());
                        $model
                            ->andWhere(sprintf('%s <= :%s', $filter->getQueryIdentifier(), $toParamName))
                            ->setParameter($toParamName, $to);
                    }

                    break;
            }
        }
    }

    /**
     * Clears filter and redirects to the same page.
     */
    public function clearFilterAction()
    {
        $this->redirect(
            [
                'filter' => null,
                'do' => null,
            ]
        );
    }

    /**
     * Returns array of active filters. If a $field is specified than returns specified filter value.
     */
    public function getActiveFilter(string $field = null)
    {
        if (! empty($field)) {
            if (array_key_exists($field, (array) $this->filter)) {
                return $this->filter[$field];
            }

            return null;
        }

        return $this->filter;
    }

    /**
     * Get route params with correct ID (by root alias).
     *
     * @param string $rootAlias
     */
    private function getRouteParams($rootAlias, array $routeParameters): array
    {
        return array_merge(
            [
                'id' => sprintf('%s_id', $rootAlias),
            ],
            $routeParameters
        );
    }

    private function initializePaginator()
    {
        $this->paginator = new Paginator($this->model);
        $this->paginator->setMaxRecords($this->itemsPerPage);
        $this->page = max(1, min((int) $this->page, $this->paginator->getPageCount()));
        $this->paginator->setPage($this->page);
    }

    private function prepareFilters()
    {
        $this->filter = (array) $this->filter;
        foreach ($this->filter as $name => $value) {
            if (! isset($this->filters[$name])) {
                if (Strings::endsWith($name, '_from')) {
                    $tryName = preg_replace('/_from$/', '', $name);
                    if (isset($this->filters[$tryName]) && $this->filters[$tryName] instanceof RangeFilterInterface) {
                        /** @var RangeFilterInterface $filter */
                        $filter = $this->filters[$tryName];
                        $filter->setRangeFrom($value);
                        $this->filter[$tryName] = true;
                        $filter->refreshControlPrototype();
                    } else {
                        continue;
                    }
                } elseif (Strings::endsWith($name, '_to')) {
                    $tryName = preg_replace('/_to/', '', $name);
                    if (isset($this->filters[$tryName]) && $this->filters[$tryName] instanceof RangeFilterInterface) {
                        /** @var RangeFilterInterface $filter */
                        $filter = $this->filters[$tryName];
                        $filter->setRangeTo($value);
                        $this->filter[$tryName] = true;
                        $filter->refreshControlPrototype();
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
        }
    }

    public function setPermissionGrantedChecker(PermissionGrantedChecker $permissionGrantedChecker)
    {
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    public function isAjaxEnabled(): bool
    {
        return $this->ajaxEnabled;
    }

    public function setAjaxEnabled(bool $ajaxEnabled = true): void
    {
        $this->ajaxEnabled = $ajaxEnabled;
    }

    public function getAjaxRequestIdentifier(): string
    {
        return \AppBundle\Util\Strings::slugify($this->getId());
    }

    public function getAjaxRequestKeyData(): string
    {
        return Json::encode(
            [
                self::AJAX_REQUEST_IDENTIFIER => $this->getAjaxRequestIdentifier(),
            ]
        );
    }

    public function setElasticsearch(Search $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * @return array|string
     */
    public function getDefaultSortBy()
    {
        return $this->defaultSortBy;
    }

    public function isRowSelectEnabled(): bool
    {
        return ! empty($this->multiActions);
    }

    /**
     * @return string|null
     */
    public function getDoMultiAction()
    {
        return $this->doMultiAction;
    }

    /**
     * @return array|null
     */
    public function getDoMultiActionIds()
    {
        return $this->doMultiActionIds;
    }

    public function getMultiActionsRender(): array
    {
        return $this->multiActionsRender;
    }

    /**
     * @param IHtmlString|string|null $confirmMessage
     */
    public function addMultiAction(
        string $name,
        string $title,
        callable $callback,
        array $cssClasses = [],
        $confirmMessage = null,
        string $tooltip = null,
        string $icon = null,
        bool $allowAll = false,
        bool $addToRender = true
    ): MultiAction {
        if (array_key_exists($name, $this->multiActions)) {
            throw new \InvalidArgumentException(
                sprintf('This action (%s) is already defined.', $name)
            );
        }

        $this->multiActions[$name] = new MultiAction(
            $name,
            $title,
            $callback,
            $cssClasses,
            $confirmMessage,
            $tooltip,
            $icon,
            $allowAll
        );

        if ($addToRender) {
            $this->addMultiActionRender($name);
        }

        return $this->multiActions[$name];
    }

    public function addMultiActionRender(string $name, bool $isGroup = false)
    {
        $this->multiActionsRender[] = $isGroup ? $this->multiActionGroups[$name] : $this->multiActions[$name];
    }

    public function addMultiActionGroup(MultiActionGroup $group, bool $addToRender = true)
    {
        $this->multiActionGroups[$group->name] = $group;

        if ($addToRender) {
            $this->multiActionsRender[] = $group;
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getMultiAction(string $name): MultiAction
    {
        if (! array_key_exists($name, $this->multiActions)) {
            throw new \InvalidArgumentException(
                sprintf('This action (%s) does not exist.', $name)
            );
        }

        return $this->multiActions[$name];
    }

    /**
     * @return Response|null
     */
    public function processMultiAction()
    {
        if (! $this->doMultiAction) {
            return null;
        }

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken($this->getCsrfTokenId(), $this->csrfToken))) {
            return null;
        }

        try {
            $multiAction = $this->getMultiAction($this->doMultiAction);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        if (empty($this->doMultiActionIds) && ! $multiAction->allowAll) {
            return null;
        }

        if ($this->doMultiActionSelectAll) {
            $this->doMultiActionIds = $this->getExportIds();
        }

        return ($multiAction->callback)($this);
    }

    public function processAjaxRequest(Request $request): array
    {
        $identifier = $this->getAjaxRequestIdentifier();
        if (
            $request->isXmlHttpRequest()
            && $request->get(self::AJAX_REQUEST_IDENTIFIER, '') === $identifier
        ) {
            $urlParameters = $this->getUrlParameters($this->routerUrlParams, true, false);
            $urlParameters['suffix'] = $this->routerUrlSuffix ?? '';

            $url = $this->generateUrlDirectly(
                    $urlParameters['route'],
                    $urlParameters['parameters']
                ) . $urlParameters['suffix'];

            return [
                'templates' => [
                    $identifier => $this->createView(),
                ],
                'url' => $url,
                'shortcutParameters' => $urlParameters,
                'stateObj' => [
                    self::AJAX_REQUEST_IDENTIFIER => $this->getAjaxRequestIdentifier(),
                    'url' => $url,
                    'component' => 'grid',
                    'shortcutParameters' => $urlParameters,
                ],
            ];
        }

        return [];
    }

    public function generateUrl(
        array $parameters = [],
        bool $includePersistentParameters = true,
        bool $includeAjaxRequestIdentifier = false
    ): string {
        $parameters = array_merge($this->routerUrlParams, $parameters);

        return parent::generateUrl($parameters, $includePersistentParameters, $includeAjaxRequestIdentifier)
            . ($this->routerUrlSuffix ?? '');
    }

    public function generateMultiActionReturnUrl(): string
    {
        $this->csrfToken = null;
        $this->doMultiAction = null;
        $this->doMultiActionIds = null;

        return $this->generateUrl();
    }

    public function getCsrfToken(): CsrfToken
    {
        return $this->csrfTokenManager->getToken($this->getCsrfTokenId());
    }

    private function getCsrfTokenId(): string
    {
        return 'app-grid-' . $this->id;
    }

    public function getTextNoRows(): ?string
    {
        return $this->textNoRows;
    }

    public function setTextNoRows(?string $textNoRows): void
    {
        $this->textNoRows = $textNoRows;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    private function validateProperties(): void
    {
        if (! is_numeric($this->itemsPerPage)) {
            $this->itemsPerPage = null;
        }

        if (! is_string($this->sortBy)) {
            $this->sortBy = null;
        }

        if (! is_array($this->filter)) {
            $this->filter = [];
        }

        if (! is_string($this->csrfToken)) {
            $this->csrfToken = null;
        }

        if (! is_string($this->doMultiAction)) {
            $this->doMultiAction = null;
        }

        if (! is_array($this->doMultiActionIds)) {
            $this->doMultiActionIds = null;
        }

        // this must be always converted to bool without any checks, if true its' value is actually "on"
        // but since bool cast always works, there is no need to check for anything else
        $this->doMultiActionSelectAll = (bool) $this->doMultiActionSelectAll;

        if (! in_array($this->sortDirection, [self::ASC, self::DESC], true)) {
            $this->sortDirection = self::ASC;
        }
    }
}
