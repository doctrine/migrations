<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;

trait DialogSupport
{
    /** @var QuestionHelper|PHPUnit_Framework_MockObject_MockObject */
    protected $questions;

    protected function configureDialogs(Application $app) : void
    {
        $this->questions = $this->createMock(QuestionHelper::class);

        $app->getHelperSet()->set($this->questions, 'question');
    }

    protected function willAskConfirmationAndReturn(bool $bool) : void
    {
        $this->questions->expects($this->once())
            ->method('ask')
            ->willReturn($bool);
    }
}
