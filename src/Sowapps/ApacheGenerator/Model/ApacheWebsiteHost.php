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
	 * @param string $slug
	 * @param stdClass $redirection
	 * @throws ApacheConfigurationException
	 */
	public function __construct($slug, $redirection) {
		parent::__construct($slug, $redirection);
		
		if(empty($redirection->path)) {
			throw new ApacheConfigurationException('Missing path in website host configuration');
		}
		$this->path = $redirection->path;
	}
	
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
		/*
		DocumentRoot /home/cartman/www/anek/hosts/dev/sources
		<Directory /home/cartman/www/anek/hosts/dev/sources/>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                Allow from all
                AuthType Basic
                AuthName Authentification
                AuthUserFile /home/cartman/.htauth/.htusers
                AuthGroupFile /home/cartman/.htauth/.htgroups
                Require user cartman
		</Directory>
		*/
	}
	
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
