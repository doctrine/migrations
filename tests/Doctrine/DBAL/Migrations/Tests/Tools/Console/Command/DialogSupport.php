<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\DialogHelper;

trait DialogSupport
{
    protected $questions, $isDialogHelper;

    protected function configureDialogs(Application $app)
    {
        if (class_exists(QuestionHelper::class)) {
            $this->isDialogHelper = false;
            $this->questions = $this->createMock(QuestionHelper::class);
        } else {
            $this->isDialogHelper = true;
            $this->questions = $this->createMock(DialogHelper::class);
        }
        $app->getHelperSet()->set($this->questions, $this->isDialogHelper ? 'dialog' : 'question');
    }

    protected function willAskConfirmationAndReturn($bool)
    {
        $this->questions->expects($this->once())
            ->method($this->isDialogHelper ? 'askConfirmation' : 'ask')
            ->willReturn($bool);
    }
}
