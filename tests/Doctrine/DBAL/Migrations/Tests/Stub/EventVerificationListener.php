<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Migrations\Events;

final class EventVerificationListener implements EventSubscriber
{
    /** @var EventArgs[] */
    public $events = [];

    /** @return string[] */
    public function getSubscribedEvents() : array
    {
        return [
            Events::onMigrationsMigrating,
            Events::onMigrationsMigrated,
            Events::onMigrationsVersionExecuting,
            Events::onMigrationsVersionExecuted,
            Events::onMigrationsVersionSkipped,
        ];
    }

    public function onMigrationsMigrating(EventArgs $args) : void
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsMigrated(EventArgs $args) : void
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsVersionExecuting(EventArgs $args) : void
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsVersionExecuted(EventArgs $args) : void
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsVersionSkipped(EventArgs $args) : void
    {
        $this->events[__FUNCTION__][] = $args;
    }
}
