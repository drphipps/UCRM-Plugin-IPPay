<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid;

use AppBundle\Component\ComponentFactory;
use AppBundle\Component\Elastic\Search;
use AppBundle\Security\PermissionGrantedChecker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig_Environment;

class GridFactory
{
    /**
     * @var ComponentFactory
     */
    private $componentFactory;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var Search
     */
    private $elasticSearch;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    public function __construct(
        ComponentFactory $componentFactory,
        EntityManager $em,
        Twig_Environment $twig,
        RouterInterface $router,
        TranslatorInterface $translator,
        PermissionGrantedChecker $permissionGrantedChecker,
        Search $elasticSearch,
        Session $session,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->componentFactory = $componentFactory;
        $this->em = $em;
        $this->twig = $twig;
        $this->router = $router;
        $this->translator = $translator;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->elasticSearch = $elasticSearch;
        $this->session = $session;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    private $grids = [];

    public function createGrid(
        QueryBuilder $model,
        string $gridId,
        ?string $defaultSort = null,
        string $defaultSortDirection = Grid::DESC
    ): Grid {
        /** @var Grid $grid */
        $grid = $this->componentFactory->createComponent(
            Grid::class,
            $this->generateGridName(),
            false
        );
        $this->grids[] = $grid;

        $grid->setId(sprintf('%s_%s', $gridId, $grid->getName()));
        $grid->setEntityManager($this->em);
        $grid->setTwig($this->twig);
        $grid->setRouter($this->router);
        $grid->setTranslator($this->translator);
        $grid->setSession($this->session);
        $grid->setPermissionGrantedChecker($this->permissionGrantedChecker);
        $grid->setElasticsearch($this->elasticSearch);
        $grid->setCsrfTokenManager($this->csrfTokenManager);
        $grid->setModel($model);
        if ($defaultSort) {
            $grid->setDefaultSort($defaultSort, $defaultSortDirection);
        }

        return $grid;
    }

    private function generateGridName(): string
    {
        if (count($this->grids)) {
            return 'grid' . (count($this->grids) + 1);
        }

        return 'grid';
    }
}
