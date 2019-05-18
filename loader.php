<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

/**
 * Error Handler
 *
 * System function to handle PHP errors and convert it into exceptions.
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Implement PSR4 autoloader
spl_autoload_register(function ($class) {
	
	// Project-specific namespace prefix
	$prefix = '';
	
	// Base directory for the namespace prefix
	$base_dir = __DIR__ . '/src/';
	
	// Does the class use the namespace prefix?
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}
	
	// Get the relative class name
	$relative_class = substr($class, $len);
	
	// Replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
	
	// If the file exists, require it
	if (file_exists($file)) {
		require $file;
	}
});

function writeError($text, $exitCode = -1, $showUsage = false) {
	if($showUsage) {
		showUsage();
	}
	fwrite(STDERR, $text . PHP_EOL);
	if( $exitCode > -1 ) {
		exit($exitCode);
	}
}
