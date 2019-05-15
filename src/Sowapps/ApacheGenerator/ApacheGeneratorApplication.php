<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator;

use Sowapps\ApacheGenerator\Exception\ApacheGeneratorException;
use Sowapps\ApacheGenerator\Model\ApacheConfiguration;

class ApacheGeneratorApplication {
	
	private $apacheConfPath;
	private $sourceConfPath;
	
	/**
	 * ApacheGeneratorApplication constructor.
	 * @param $sourceConfPath
	 * @param $apacheConfPath
	 */
	public function __construct($sourceConfPath, $apacheConfPath) {
		$this->apacheConfPath = $apacheConfPath;
		$this->sourceConfPath = $sourceConfPath;
	}
	
	/**
	 * @throws ApacheGeneratorException
	 */
	protected function preRun() {
		if(!function_exists('yaml_parse_file')) {
			throw new ApacheGeneratorException('Yaml parse function is not available, please install and enable yaml module for PHP');
		}
		if(!is_dir($this->sourceConfPath)) {
			throw new ApacheGeneratorException(sprintf('Source configuration folder %s is not a folder', $this->sourceConfPath));
		}
		if(!is_readable($this->sourceConfPath)) {
			throw new ApacheGeneratorException(sprintf('Source configuration folder %s is not readable', $this->sourceConfPath));
		}
		if(!is_dir($this->apacheConfPath)) {
			throw new ApacheGeneratorException(sprintf('Apache configuration folder %s is not a folder', $this->apacheConfPath));
		}
		if(!is_writable($this->apacheConfPath)) {
			throw new ApacheGeneratorException(sprintf('Apache configuration folder %s is not writable', $this->apacheConfPath));
		}
	}
	
	public function run() {
		$this->preRun();
		$position = 0;
		foreach(scandir($this->sourceConfPath, SCANDIR_SORT_ASCENDING) as $file) {
			$filePath = $this->sourceConfPath . '/' . $file;
			// Ignore if hidden file, not yaml file, not a file or not readable
			$extension = pathinfo($filePath, PATHINFO_EXTENSION);
			if($extension !== 'yaml' || !is_file($filePath) || !is_readable($filePath)) {
				continue;
			}
			$this->generate($filePath, $position);
			$position++;
		}
		$this->write('Finished to generate all configurations.');
	}
	
	public function generate($filePath, $position = 0) {
		echo "Generate configuration for path {$filePath}\n";
		$configuration = new ApacheConfiguration(pathinfo($filePath, PATHINFO_FILENAME), static::parseConfigurationFile($filePath));
//		if(!$configuration->name) {
//			throw new ApacheGeneratorException(sprintf('No name in source configuration %s', $filePath));
//		}
//		$configurationSlug = pathinfo($filePath, PATHINFO_FILENAME);
		$this->write(sprintf("Generate apache configuration for %s (%s) #%d from source file\n%s", $configuration->getName(), $configuration->getSlug(), $position, $filePath));
		
		$content = $configuration->generate();
		
		$outputPath = sprintf('%s/%s.conf', $this->apacheConfPath, $configuration->getSlug());
//		echo $content."\n";
//		echo $outputPath."\n";
		file_put_contents($outputPath, $content);
		$this->write(sprintf("Generated into %s\n", $outputPath));
	}
	
	public function write($text) {
		echo $text . "\n";
	}
	
	public static function parseConfigurationFile($filePath) {
		return json_decode(json_encode(yaml_parse_file($filePath), true));
	}
	
	/**
	 * @return mixed
	 */
	public function getApacheConfPath() {
		return $this->apacheConfPath;
	}
	
	/**
	 * @return mixed
	 */
	public function getSourceConfPath() {
		return $this->sourceConfPath;
	}
}


