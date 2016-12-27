<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Migrations\Events;

final class EventVerificationListener implements EventSubscriber
{
    public $events = [];

    public function getSubscribedEvents()
    {
        return [
            Events::onMigrationsMigrating,
            Events::onMigrationsMigrated,
            Events::onMigrationsVersionExecuting,
            Events::onMigrationsVersionExecuted,
            Events::onMigrationsVersionSkipped,
        ];
    }

    public function onMigrationsMigrating(EventArgs $args)
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsMigrated(EventArgs $args)
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsVersionExecuting(EventArgs $args)
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsVersionExecuted(EventArgs $args)
    {
        $this->events[__FUNCTION__][] = $args;
    }

    public function onMigrationsVersionSkipped(EventArgs $args)
    {
        $this->events[__FUNCTION__][] = $args;
    }
}
