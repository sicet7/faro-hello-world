<?php

namespace Sicet7\Faro\Core;

use Throwable;

class BuildLock
{
    /**
     * @var bool
     */
    private bool $locked = false;

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @return void
     */
    public function lock(): void
    {
        $this->locked = true;
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     * @return void
     */
    public function throwIfLocked(Throwable $throwable): void
    {
        if ($this->isLocked()) {
            throw $throwable;
        }
    }
}
