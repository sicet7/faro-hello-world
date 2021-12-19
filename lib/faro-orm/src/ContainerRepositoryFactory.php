<?php

namespace Sicet7\Faro\ORM;

use DI\FactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\ORM\Interfaces\EntityRepositoryInterface;

class ContainerRepositoryFactory implements RepositoryFactory
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @var string[]
     */
    private array $repositoryLookups = [];

    /**
     * @param ContainerInterface $container
     * @param FactoryInterface $factory
     */
    public function __construct(
        ContainerInterface $container,
        FactoryInterface $factory
    ) {
        $this->container = $container;
        $this->factory = $factory;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param string $entityName
     * @return EntityRepositoryInterface|ObjectRepository
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getRepository(
        EntityManagerInterface $entityManager,
        $entityName
    ): EntityRepositoryInterface|ObjectRepository {
        $metadata = $entityManager->getClassMetadata($entityName);
        $repositoryHash = $metadata->getName() . spl_object_id($entityManager);
        if (array_key_exists($repositoryHash, $this->repositoryLookups)) {
            $classData = $this->repositoryLookups[$repositoryHash];
            if ($classData instanceof ObjectRepository) {
                return $classData;
            }
            return $this->container->get($classData);
        }

        if (isset($metadata->customRepositoryClassName)) {
            return $this->container->get(
                $this->repositoryLookups[$repositoryHash] = $metadata->customRepositoryClassName
            );
        }

        return $this->repositoryLookups[$repositoryHash] = $this->factory->make(
            $entityManager->getConfiguration()->getDefaultRepositoryClassName(),
            [
                'em' => $entityManager,
                'class' => $metadata,
            ]
        );
    }
}
