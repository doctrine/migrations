<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\DialogHelper;

trait DialogSupport
{
    /**
     * @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
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
