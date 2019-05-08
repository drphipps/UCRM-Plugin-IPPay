<?php

namespace Tests\Unit;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Constraint\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;

class UnitTestCase extends TestCase
{
    /**
     * @param int $persist
     * @param int $flush
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEmMock(array $methods = [], $persist = 1, $flush = 1, $response = null)
    {
        $defaultMethods = ['persist', 'flush'];
        if (! in_array('getUnitOfWork', $methods)) {
            $defaultMethods[] = 'getUnitOfWork';
        }

        $reflection = new \ReflectionClass(EntityManager::class);

        $reflectionEm = $reflection->getProperty('metadataFactory');
        $reflectionEm->setAccessible(true);

        $emMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(array_merge($defaultMethods, $methods))
            ->getMock();

        $emMock->expects($this->exactly($persist))
            ->method('persist')
            ->willReturn(null);

        $emMock->expects($this->exactly($flush))
            ->method('flush')
            ->willReturn(null);

        if (! in_array('getUnitOfWork', $methods)) {
            $getUnitOfWork = new class() {
                private $response;

                public function setResponse($response)
                {
                    $this->response = $response;
                }

                public function tryGetById()
                {
                    return false;
                }

                public function getEntityPersister()
                {
                    $persister = new class() {
                        private $response;

                        public function setResponse($response)
                        {
                            $this->response = $response;
                        }

                        public function loadById()
                        {
                            return $this->response;
                        }
                    };

                    $persister->setResponse($this->response);

                    return $persister;
                }
            };

            $getUnitOfWork->setResponse($response);

            $emMock->expects($this->any())
                ->method('getUnitOfWork')
                ->with()
                ->willReturn($getUnitOfWork);
        }

        $metadataFactory = new class() {
            public $isIdentifierComposite = false;
            public $identifier = [0 => 0];
            public $rootEntityName = null;
            public $name = null;

            public function getMetadataFor(string $entityName)
            {
                return $this;
            }

            public function hasMetadataFor()
            {
                return false;
            }
        };

        $reflectionEm->setValue($emMock, $metadataFactory);

        return $emMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClassMetadataMock(array $methods = [])
    {
        $classMetadataMock = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();

        return $classMetadataMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContainerMock($emMock, array $parameters = [])
    {
        $doctrineMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $doctrineMock->expects($this->any())
            ->method('getManager')
            ->with()
            ->willReturn($emMock);

        $containerMock = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $containerMock->expects($this->any())
            ->method('get')
            ->with('doctrine')
            ->willReturn($doctrineMock);

        foreach ($parameters as $key => $value) {
            $containerMock->expects($this->any())
                ->method('getParameter')
                ->with($key)
                ->willReturn($value);
        }

        return $containerMock;
    }

    protected function getFormFieldMock(): FormInterface
    {
        return $this->getMockBuilder(FormInterface::class)
            ->setMethods(
                [
                    'add',
                    'addError',
                    'all',
                    'count',
                    'createView',
                    'get',
                    'getConfig',
                    'getData',
                    'getErrors',
                    'getExtraData',
                    'getName',
                    'getNormData',
                    'getParent',
                    'getPropertyPath',
                    'getRoot',
                    'getTransformationFailure',
                    'getViewData',
                    'handleRequest',
                    'has',
                    'initialize',
                    'isDisabled',
                    'isEmpty',
                    'isRequired',
                    'isRoot',
                    'isSubmitted',
                    'isSynchronized',
                    'isValid',
                    'offsetExists',
                    'offsetGet',
                    'offsetSet',
                    'offsetUnset',
                    'remove',
                    'setData',
                    'setParent',
                    'submit',
                ]
            )
            ->getMock();
    }

    protected function getLoggerMock(): LoggerInterface
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(
                [
                    'info',
                    'emergency',
                    'critical',
                    'error',
                    'warning',
                    'notice',
                    'debug',
                    'alert',
                    'log',
                ]
            )
            ->getMock();
    }

    protected function assertException(string $expectedException, callable $callable, array $arguments = [])
    {
        $exception = null;

        try {
            $callable(...$arguments);
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertThat(
            $exception,
            new Exception($expectedException)
        );
    }
}
