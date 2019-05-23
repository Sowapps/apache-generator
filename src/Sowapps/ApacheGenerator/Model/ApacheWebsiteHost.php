<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

class ApacheWebsiteHost extends AbstractApacheVirtualHost {
	
	/**
	 * @var string
	 */
	private $path;
	
	/**
	 * AbstractApacheWebsiteHost constructor
	 *
	 * @param stdClass $websiteHost
	 * @throws ApacheConfigurationException
	 */
	public function __construct($websiteHost) {
		parent::__construct($websiteHost);
		
		if(empty($websiteHost->path)) {
			throw new ApacheConfigurationException(sprintf('Missing path in website host "%s" configuration', $this->slug));
		}
		$this->path = $websiteHost->path;
	}
	
	/**
	 * Render website apache2 configuration to output buffer
	 */
	public function renderContent() {
		echo "
	DocumentRoot {$this->getPath()}
	<Directory {$this->getPath()}/ >
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Require all granted";
		
		if($this->authentication) {
			$this->authentication->render();
		}
			
		echo "
		<IfModule mod_php5.c>
			php_flag magic_quotes_gpc Off
			php_flag track_vars On
			php_flag register_globals Off
			php_value include_path .
		</IfModule>
	</Directory>";
	}
	
	
	/**
	 * Get title of website host
	 *
	 * @return string
	 */
	protected function getTitle() {
		return sprintf('Website Host %s of %s', $this->getSlug(), $this->getHost());
	}
	
	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
}
