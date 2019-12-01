<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;

trait DialogSupport
{
    /** @var QuestionHelper|MockObject */
    protected $questions;

    protected function configureDialogs(Application $app) : void
    {
        $this->questions = $this->createMock(QuestionHelper::class);

        $app->getHelperSet()->set($this->questions, 'question');
    }

    protected function willAskConfirmationAndReturn(bool $bool) : void
    {
        $this->questions->expects(self::once())
            ->method('ask')
            ->willReturn($bool);
    }
}
