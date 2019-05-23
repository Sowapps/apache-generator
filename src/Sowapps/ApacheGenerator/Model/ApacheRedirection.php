<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

class ApacheRedirection extends AbstractApacheVirtualHost {
	
	const DEFAULT_PATH = '/';
	
	/**
	 * @var string
	 */
	private $target;
	
	/**
	 * @var string
	 */
	private $path;
	
	/**
	 * AbstractApacheWebsiteHost constructor
	 *
	 * @param stdClass $redirection
	 * @throws ApacheConfigurationException
	 */
	public function __construct($redirection) {
		parent::__construct($redirection);
		
		if(empty($redirection->target)) {
			throw new ApacheConfigurationException('Missing target in redirection configuration');
		}
		$this->target = $redirection->target;
		$this->path = !empty($redirection->path) ? $redirection->path : self::DEFAULT_PATH;
	}
	
	/**
	 * Render redirection apache2 configuration to output buffer
	 */
	public function renderContent() {
		echo "
	RedirectPermanent {$this->getPath()} {$this->getTarget()}";
	}
	
	
	/**
	 * Get title of redirection host
	 *
	 * @return string
	 */
	protected function getTitle() {
		return sprintf('Redirection %s of %s', $this->getSlug(), $this->getHost());
	}
	
	/**
	 * @return string
	 */
	public function getTarget() {
		return $this->target;
	}
	
	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
}
