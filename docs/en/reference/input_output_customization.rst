Input - Output Customization
============================

Behind the scenes Doctrine Migration uses ``\Symfony\Component\Console\Input\ArgvInput``
to capture and parse values from ``$_SERVER['argv']``.

You can customize the input like the following:

.. code-block:: php

    require 'vendor/autoload.php'

    $input = new \Symfony\Component\Console\Input\ArgvInput;
    $input->setArgument('verbose', true);

    return $input;

And the output similarly:

.. code-block:: php

    require 'vendor/autoload.php'

    // This is what Doctrine Migrations uses by default
    $output = new \Symfony\Component\Console\Output\ConsoleOutput;

    // Enable styling for HTML tags, which would otherwise throw errors
    $htmlTags = array('p', 'ul', 'li', 'ol', 'dl', 'dt', 'dd', 'b', 'i', 'strong', 'em', 'hr', 'br');
    foreach ($htmlTags as $tag) {
        $output->setStyle($tag);    // Each tag gets default styling
    }

    return $output;


If you are using the phar it is still possible to customize the input and output but you
need to require the autoloader that's in the phar.

.. code-block:: php

    require 'phar://migrations.phar/vendor/autoload.php';
