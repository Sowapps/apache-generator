<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

function encapsulate($value) {
	return is_array($value) ? $value : array($value);
}

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

-h
	Show help


EOF;
}

if( posix_getpwuid(posix_geteuid())['name'] !== 'root' ) {
	showUsage();
	writeError('You must run this command as root.', 1);
}

$options = getopt('i::o::h', array('parse::', 'to::', 'help'));

mergeOption($options, 'h', 'help');
mergeOption($options, 'i', 'parse');
mergeOption($options, 'o', 'to');

// Help
if( !empty($options['help']) ) {
	echo "Here is the help:\n\n";
	showUsage();
	exit;
}

// Check input parse folder path
$inputPath = null;
// Try to get input from option
if( !empty($options['parse']) ) {
	if(count($options['parse']) > 1) {
		writeError('You must provide only one input folder path to continue.', 1, true);
	}
	$inputPath = $options['parse'][0];
} else {
	// Request new to user
	for( $i = 0; !$inputPath && $i < 3; $i++ ) {
		$inputPath = readline('YAML folder path ? ');
	}
	if( !$inputPath ) {
		writeError('You must provide a valid YAML folder path to continue.', 1);
	}
}
// Check input path is valid
if( !is_dir($inputPath) || !is_readable($inputPath) ) {
	writeError('You must provide a valid YAML folder path to continue.', 1, true);
}

// Check output folder path
$outputPath = null;
// Try to get output from option
if( !empty($options['to']) ) {
	if(count($options['to']) > 1) {
		writeError('You must provide only one output folder path to continue.', 1, true);
	}
	$outputPath = $options['to'][0];
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
	// Request new to user
	$outputPath = readline('Output folder path ? ' . ($outputPathDefault ? "[{$outputPathDefault}]" : ''));
	if( !$outputPath ) {
		if( $outputPathDefault ) {
			$outputPath = $outputPathDefault;
		} else {
			writeError('You must provide a valid output folder path to continue, no default were found.', 1);
		}
	}
}
// Check input path is valid
if( !is_dir($outputPath) || !is_writable($outputPath) ) {
	writeError('You must provide a valid output folder path to continue.', 1, true);
}
