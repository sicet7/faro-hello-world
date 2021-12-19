<?php

namespace App\Database\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sicet7\Faro\ORM\Interfaces\EntityRepositoryInterface;

class TestRepository implements EntityRepositoryInterface
{

    public function getEntityManager(): EntityManagerInterface
    {
        // TODO: Implement getEntityManager() method.
    }

    public function createQueryBuilder(): QueryBuilder
    {
        // TODO: Implement createQueryBuilder() method.
    }

    public function find($id)
    {
        // TODO: Implement find() method.
    }

    public function findAll()
    {
        // TODO: Implement findAll() method.
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        // TODO: Implement findBy() method.
    }

    public function findOneBy(array $criteria)
    {
        // TODO: Implement findOneBy() method.
    }

    public function getClassName()
    {
        // TODO: Implement getClassName() method.
    }

    public function matching(Criteria $criteria)
    {
        // TODO: Implement matching() method.
    }
}
