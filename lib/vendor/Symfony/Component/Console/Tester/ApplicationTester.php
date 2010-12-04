<?php

namespace Symfony\Component\Console\Tester;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class ApplicationTester
{
    protected $application;
    protected $display;
    protected $input;
    protected $output;

    /**
     * Constructor.
     *
     * @param Application $application A Application instance to test.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Executes the application.
     *
     * Available options:
     *
     *  * interactive: Sets the input interactive flag
     *  * decorated:   Sets the output decorated flag
     *  * verbosity:   Sets the output verbosity flag
     *
     * @param array $input   An array of arguments and options
     * @param array $options An array of options
     */
    public function run(array $input, $options = array())
    {
        $this->input = new ArrayInput($input);
        if (isset($options['interactive'])) {
            $this->input->setInteractive($options['interactive']);
        }

        $this->output = new StreamOutput(fopen('php://memory', 'w', false));
        if (isset($options['decorated'])) {
            $this->output->setDecorated($options['decorated']);
        }
        if (isset($options['verbosity'])) {
            $this->output->setVerbosity($options['verbosity']);
        }

        $ret = $this->application->run($this->input, $this->output);

        rewind($this->output->getStream());

        return $this->display = stream_get_contents($this->output->getStream());
    }

    /**
     * Gets the display returned by the last execution of the application.
     *
     * @return string The display
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Gets the input instance used by the last execution of the application.
     *
     * @return InputInterface The current input instance
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Gets the output instance used by the last execution of the application.
     *
     * @return OutputInterface The current output instance
     */
    public function getOutput()
    {
        return $this->output;
    }
}
