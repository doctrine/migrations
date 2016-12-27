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
        ];
    }

    public function onMigrationsMigrating(EventArgs $args)
    {
        $this->events[Events::onMigrationsMigrating][] = $args;
    }

    public function onMigrationsMigrated(EventArgs $args)
    {
        $this->events[Events::onMigrationsMigrated][] = $args;
    }
}
