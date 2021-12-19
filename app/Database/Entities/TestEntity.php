<?php

namespace App\Database\Entities;

use App\Database\Repositories\TestRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table('tests')]
#[Entity(TestRepository::class)]
class TestEntity
{

    #[Id]
    private int $id;

}
