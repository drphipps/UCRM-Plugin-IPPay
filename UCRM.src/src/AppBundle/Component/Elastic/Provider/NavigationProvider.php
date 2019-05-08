<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\Provider;

use AppBundle\Component\Elastic\Annotation\Searchable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use Elastica\Document;
use FOS\ElasticaBundle\Provider\PagerfantaPager;
use FOS\ElasticaBundle\Provider\PagerInterface;
use FOS\ElasticaBundle\Provider\PagerProviderInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class NavigationProvider implements PagerProviderInterface
{
    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var FormRegistryInterface
     */
    private $formRegistry;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $locale;

    public function __construct(
        array $paths,
        FormRegistryInterface $formRegistry,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        string $locale
    ) {
        $this->paths = $paths;
        $this->formRegistry = $formRegistry;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function provide(array $options = []): PagerInterface
    {
        return new PagerfantaPager(
            new Pagerfanta(
                new ArrayAdapter($this->getDocuments())
            )
        );
    }

    /**
     * @return Document[]
     */
    private function getDocuments(): array
    {
        $documents = [];

        // get all controllers in configured paths
        $driver = new PHPDriver(new SymfonyFileLocator($this->paths, '.php'));
        $controllers = $driver->getAllClassNames();
        $reader = new AnnotationReader();
        $annotationReader = new CachedReader($reader, new ApcuCache());
        foreach ($controllers as $controller) {
            // we're only interested in controllers
            $reflection = new \ReflectionClass($controller);
            if (! $reflection->isSubclassOf(Controller::class)) {
                continue;
            }

            // go through all public methods (excluding parent classes)
            // and filter only those with Searchable and Route annotations
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class !== $controller) {
                    continue;
                }

                /** @var Searchable|null $searchable */
                $searchable = $annotationReader->getMethodAnnotation($method, Searchable::class);
                if (! $searchable) {
                    continue;
                }

                /** @var Route|null $route */
                $route = $annotationReader->getMethodAnnotation($method, Route::class);
                if (! $route) {
                    continue;
                }

                $documents[] = $this->createDocument($searchable, $route);
            }
        }

        return $documents;
    }

    private function createDocument(Searchable $searchable, Route $route): Document
    {
        $labels = [];
        foreach ($searchable->formTypes ?? [] as $formType) {
            $labels = array_merge($labels, $this->getFormTypeLabels($formType));
        }
        foreach ($searchable->extra ?? [] as $extra) {
            $labels[] = htmlspecialchars_decode($this->trans($extra));
        }
        $labels = array_unique($labels);
        $labelsData = [];
        foreach ($labels as $label) {
            $labelData = new \stdClass();
            $labelData->label = $label;
            $labelsData[] = $labelData;
        }

        $path = $searchable->path ?? '';
        $path = explode(' -> ', $path);
        $path = array_map(
            function ($part) {
                return htmlspecialchars_decode($this->trans($part));
            },
            $path
        );
        $path = implode(' → ', $path);

        return new Document(
            $route->getName(),
            [
                'id' => $route->getName(),
                'heading' => htmlspecialchars_decode($searchable->heading ? $this->trans($searchable->heading) : ''),
                'path' => $path,
                'labels' => $labelsData,
            ]
        );
    }

    private function getFormTypeLabels(string $formType): array
    {
        $type = $this->formRegistry->getType($formType);
        $builder = $type->createBuilder($this->formFactory, 'formType');
        $type->buildForm($builder, $builder->getOptions());
        $labels = [];
        foreach ($builder->getForm()->all() as $input) {
            if (! $input->getConfig()->getOption('label')) {
                continue;
            }

            $labels[] = htmlspecialchars_decode($this->trans($input->getConfig()->getOption('label')));
        }

        return $labels;
    }

    private function trans(string $message): string
    {
        return $this->translator->trans($message, [], null, $this->locale);
    }
}
