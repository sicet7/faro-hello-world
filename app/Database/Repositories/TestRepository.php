<?php

namespace App\Database\Repositories;

use App\Database\Entities\TestEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Sicet7\Faro\ORM\Interfaces\EntityRepositoryInterface;

class TestRepository implements EntityRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder();
    }

    /**
     * @param string|int $id
     * @return TestEntity|null
     */
    public function find($id)
    {
        return $this->createQueryBuilder()
            ->select('t')
            ->from($this->getClassName(), 't')
            ->where('t.id = :identifier')
            ->setParameter('identifier', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * @return Collection
     */
    public function findAll()
    {
        return $this->createQueryBuilder()
            ->select('t')
            ->from($this->getClassName(), 't')
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Collection
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('t')
            ->from($this->getClassName(), 't');
        $i = 1;
        foreach ($criteria as $field => $value) {
            $queryBuilder->where('t.' . $field . ' = :searchValue' . $i);
            $queryBuilder->setParameter('searchValue' . $i, $value);
            $i++;
        }
        if (is_int($offset)) {
            $queryBuilder->setFirstResult($offset);
        }
        if (is_int($limit)) {
            $queryBuilder->setMaxResults($limit);
        }
        if (!empty($orderBy)) {
            $order = new OrderBy();
            foreach ($orderBy as $field => $dir) {
                $order->add($field, $dir);
            }
            $queryBuilder->orderBy($order);
        }
        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param array $criteria
     * @return TestEntity|null
     */
    public function findOneBy(array $criteria)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('t')
            ->from($this->getClassName(), 't');
        $i = 1;
        foreach ($criteria as $field => $value) {
            $queryBuilder->where('t.' . $field . ' = :searchValue' . $i);
            $queryBuilder->setParameter('searchValue' . $i, $value);
            $i++;
        }
        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return TestEntity::class;
    }

    /**
     * @param Criteria $criteria
     * @return Collection
     */
    public function matching(Criteria $criteria)
    {
        return $this->createQueryBuilder()->addCriteria($criteria)->getQuery()->execute();
    }

    /**
     * @param TestEntity $entity
     * @return TestEntity
     */
    public function save(TestEntity $entity): TestEntity
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        return $entity;
    }
}
