<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

/**
 * Get item or list of item and return list of item
 *
 * @param $value
 * @return array
 */
function encapsulate($value) {
	return is_array($value) ? $value : array($value);
}

/**
 * Merge long and short options to make it work together
 * Ensure all values are arrays
 *
 * @param $options
 * @param $short
 * @param $long
 */
function mergeOption(&$options, $short, $long) {
	if(isset($options[$long])) {
		$options[$long] = encapsulate($options[$long]);
	} else {
		$options[$long] = array();
	}
	if(isset($options[$short])) {
		$options[$long] = array_merge($options[$long], encapsulate($options[$short]));
		unset($options[$short]);
	}
}

/**
 * Show usage of application to output buffer
 */
function showUsage() {
	echo <<<EOF
Usage: a2generate.sh [OPTIONS]

You must run this command as root.

-iPATH, --parse=PATH
	The folder path to find the YAML configuration files to parse
	If missing, application will ask for it

-oPATH, --to=PATH
	The folder path to write the Apache2 configuration files
	If missing, application will ask for it

-h, --help
	Show help

--install [VERSION], --update [VERSION]
	Install given version in place, replace existing source files, default is latest


EOF;
}

define('AG_USERSAVE_VERSION', '1.0');

// Check user is root
if( posix_getpwuid(posix_geteuid())['name'] !== 'root' ) {
	showUsage();
	writeError('You must run this command as root.', 1);
}

$userSavePath = getenv('HOME') ? getenv('HOME') . '/.a2generator.yaml' : null;

// Get options from command
$options = getopt('i::o::h', array('parse::', 'to::', 'help'));

// No argument, load from user save
if(empty($options) && is_file($userSavePath) && is_readable($userSavePath)) {
	$userConfig = (object) yaml_parse_file($userSavePath);
	return;
}

mergeOption($options, 'h', 'help');
mergeOption($options, 'i', 'parse');
mergeOption($options, 'o', 'to');

// Help
if( !empty($options['help']) ) {
	echo "Here is the help:\n\n";
	showUsage();
	exit;
}

// Get new configuration
$userConfig = new stdClass();
$userConfig->version = AG_USERSAVE_VERSION;
$userConfig->inputPath = null;
$userConfig->outputPath = null;

// Check input parse folder path
// Try to get input from option
if( !empty($options['parse']) ) {
	if(count($options['parse']) > 1) {
		writeError('You must provide only one input folder path to continue.', 1, true);
	}
	$userConfig->inputPath = $options['parse'][0];
} else {
	// Request input from user
	for( $i = 0; !$userConfig->inputPath && $i < 3; $i++ ) {
		$userConfig->inputPath = readline('YAML folder path ? ');
	}
	if( !$userConfig->inputPath ) {
		writeError('You must provide a valid YAML folder path to continue.', 1);
	}
}
// Check input path is valid
if( !is_dir($userConfig->inputPath) || !is_readable($userConfig->inputPath) ) {
	writeError('You must provide a valid YAML folder path to continue.', 1, true);
}

// Check output folder path
// Try to get output from option
if( !empty($options['to']) ) {
	if(count($options['to']) > 1) {
		writeError('You must provide only one output folder path to continue.', 1, true);
	}
	$userConfig->outputPath = $options['to'][0];
} else {
	// Calculate output path default
	$outputPathDefault = null;
	$outputPathKnown = array('/etc/apache2/sites-available');
	foreach( $outputPathKnown as $path ) {
		if( is_dir($path) && is_writable($path) ) {
			$outputPathDefault = $path;
			break;
		}
	}
	// Request output from user
	$userConfig->outputPath = readline('Output folder path ? ' . ($outputPathDefault ? "[{$outputPathDefault}]" : ''));
	if( !$userConfig->outputPath ) {
		if( $outputPathDefault ) {
			$userConfig->outputPath = $outputPathDefault;
		} else {
			writeError('You must provide a valid output folder path to continue, no default were found.', 1);
		}
	}
}
// Check input path is valid
if( !is_dir($userConfig->outputPath) || !is_writable($userConfig->outputPath) ) {
	writeError('You must provide a valid output folder path to continue.', 1, true);
}

// Save user configuration
if( !$userSavePath ) {
	writeError('Unable to save configuration, no home detected.');
} else
if( (is_file($userSavePath) && is_writable($userSavePath)) || (!is_file($userSavePath) && is_writable(dirname($userSavePath))) ) {
	yaml_emit_file($userSavePath, (array) $userConfig);
	echo sprintf("Saved configuration into %s\n", $userSavePath);
} else {
	writeError(sprintf('Unable to save configuration, save file %s is not writable.', $userSavePath));
}
