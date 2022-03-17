<?php

namespace Sicet7\Faro\Config\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ConfigNotFoundException extends ConfigException implements NotFoundExceptionInterface
{
}
