<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

require 'loader.php';

use Sowapps\ApacheGenerator\ApacheGeneratorApplication;

//$generator = new ApacheGeneratorApplication('/etc/sowapps/apache2', '/etc/sowapps/apache2-output');
$generator = new ApacheGeneratorApplication('/etc/sowapps/apache2', '/etc/apache2/sites-available');

$generator->run();

/**
 * Prepare
 * mkdir -p /etc/sowapps/apache2-output
 *
 * To test
 * cp /usr/local/src/sowapps/ApacheGenerator/sample.yaml /etc/sowapps/apache2
 *
 * To run for tests
 * php -f /usr/local/src/sowapps/ApacheGenerator/generate-apache2-configuration.php
 */
