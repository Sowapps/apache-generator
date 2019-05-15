<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

abstract class AbstractApacheVirtualHost implements Renderable {
	
	const PORT_HTTP = 80;
	const PORT_HTTPS = 443;
	const DEFAULT_PORT = self::PORT_HTTP;
	
	/**
	 * @var string
	 */
	protected $slug;
	
	/**
	 * @var string
	 */
	protected $adminEmail;
	
	/**
	 * @var int
	 */
	protected $port;
	
	/**
	 * @var string
	 */
	protected $host;
	
	/**
	 * @var string[]
	 */
	protected $aliases;
	
	/**
	 * @var ApacheAuthentication|null
	 */
	protected $authentication;
	
	/**
	 * @var string
	 */
	protected $sslConfigurationPath;
	
	/**
	 * ApacheVirtualHost constructor.
	 *
	 * @param string $slug
	 * @param stdClass $virtualHost
	 * @throws ApacheConfigurationException
	 */
	public function __construct($slug, $virtualHost) {
		$this->slug = $slug;
		$this->port = isset($virtualHost->port) ? $virtualHost->port : static::DEFAULT_PORT;
		if(empty($virtualHost->host)) {
			throw new ApacheConfigurationException(sprintf('Missing host in virtual host "%s" configuration', $slug));
		}
		$this->host = $virtualHost->host;
		if(isset($virtualHost->aliases) && !is_array($virtualHost->aliases)) {
			throw new ApacheConfigurationException(sprintf('Invalid aliases in virtual host "%s" configuration', $slug));
		}
		$this->aliases = !empty($virtualHost->aliases) ? $virtualHost->aliases : array();
		$this->adminEmail = !empty($virtualHost->admin_email) ? $virtualHost->admin_email : null;
		$this->authentication = !empty($virtualHost->auth) ? new ApacheAuthentication($virtualHost->auth) : null;
		if($this->isSecureConnection()) {
			if(empty($virtualHost->ssl_config)) {
				throw new ApacheConfigurationException(sprintf('Missing ssl configuration path in host "%s" configuration', $slug));
			}
			$this->sslConfigurationPath = $virtualHost->ssl_config;
		}
	}
	
	public function render() {
		echo '# ' . $this->getTitle();
		if($this->isSecureConnection()) {
			echo "
<IfModule mod_ssl.c>";
		}
		echo "
<VirtualHost *:{$this->getPort()}>";
		
		if($this->adminEmail) {
			echo "
	ServerAdmin {$this->getAdminEmail()}";
		}
		echo "
	ServerName {$this->host}";
		if($this->aliases) {
			echo "
	ServerAlias {$this->getAliasList()}";
		}
		
		
		if($this->isSecureConnection()) {
			echo "
	Include {$this->getSslConfigurationPath()}";
		}
		
		echo "\n";
		$this->renderContent();
		
		echo "\n
	LogLevel warn
	ErrorLog \${APACHE_LOG_DIR}/{$this->getSlug()}_error.log
	CustomLog \${APACHE_LOG_DIR}/{$this->getSlug()}_access.log combined
</VirtualHost>";
		if($this->isSecureConnection()) {
			echo "
</IfModule>";
		}
		echo "\n\n";
	}
	
	protected abstract function getTitle();
	
	public abstract function renderContent();
	
	public function getAliasList() {
		return $aliases = implode(' ', $this->aliases);
	}
	
	public function getMainUrl() {
		return $this->getProtocol() . '://' . $this->host . '/';
	}
	
	public function getProtocol() {
		return $this->isSecureConnection() ? 'https' : 'http';
	}
	
	public function isSecureConnection() {
		return $this->port === self::PORT_HTTPS;
	}
	
	/**
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}
	
	/**
	 * @return string
	 */
	public function getAdminEmail() {
		return $this->adminEmail;
	}
	
	/**
	 * @return int
	 */
	public function getPort() {
		return $this->port;
	}
	
	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}
	
	/**
	 * @return \string[]
	 */
	public function getAliases() {
		return $this->aliases;
	}
	
	/**
	 * @return ApacheAuthentication
	 */
	public function getAuthentication() {
		return $this->authentication;
	}
	
	/**
	 * @param string $sslConfigurationPath
	 */
	public function setSslConfigurationPath($sslConfigurationPath) {
		$this->sslConfigurationPath = $sslConfigurationPath;
	}
	
	/**
	 * @return string
	 */
	public function getSslConfigurationPath() {
		return $this->sslConfigurationPath;
	}
	
	public static function normalize(&$virtualHost) {
		if(empty($virtualHost->auth) && !empty($virtualHost->require)) {
			$virtualHost->auth = (object) array(
				'require' => array($virtualHost->require),
			);
		} elseif(isset($virtualHost->auth) && !is_object($virtualHost->auth)) {
			$virtualHost->auth = new stdClass();
		}
	}
}
