<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;

trait DialogSupport
{
    /** @var QuestionHelper|MockObject */
    protected $questions;

    protected function configureDialogs(Application $app)
    {
        $this->questions = $this->createMock(QuestionHelper::class);

        $app->getHelperSet()->set($this->questions, 'question');
    }

    protected function willAskConfirmationAndReturn($bool)
    {
        $this->questions->expects($this->once())
                        ->method('ask')
                        ->willReturn($bool);
    }
}
