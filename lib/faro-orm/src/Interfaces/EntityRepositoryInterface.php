<?php

namespace Sicet7\Faro\ORM\Interfaces;

use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;

interface EntityRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface;

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder(): QueryBuilder;
}
