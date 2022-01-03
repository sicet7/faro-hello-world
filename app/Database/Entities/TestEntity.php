<?php

namespace App\Database\Entities;

use App\Database\Repositories\TestRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\DBAL\Types\Types;

#[Table('tests')]
#[Entity(TestRepository::class)]
class TestEntity
{
    /**
     * @var int
     */
    #[Column('id', Types::INTEGER), Id, GeneratedValue]
    private int $id;

    /**
     * @var string
     */
    #[Column('name', Types::STRING, 255)]
    private string $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
