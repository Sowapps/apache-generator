<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

use Sowapps\ApacheGenerator\ApacheGeneratorApplication;

require 'loader.php';
require 'check-input.php';

/**
 * @var string $inputPath Path to yaml folder to parse
 * @var string $outputPath Path to apache folder to generate
 */

$generator = new ApacheGeneratorApplication($inputPath, $outputPath);

$generator->run();

/**
 * Prepare
 * mkdir -p /etc/sowapps/apache2-output
 *
 * To test
 * cp /usr/local/src/sowapps/ApacheGenerator/sample.yaml /etc/sowapps/apache2
 *
 * To install
 * ln -s /usr/local/src/sowapps/ApacheGenerator/a2generate.sh /usr/local/bin/a2generate
 *
 * To run for tests
 * php -f /usr/local/src/sowapps/ApacheGenerator/generate-apache2-configuration.php
 *
 * a2generate --parse=/etc/sowapps/apache2 --to=/etc/apache2/sites-available
 */
