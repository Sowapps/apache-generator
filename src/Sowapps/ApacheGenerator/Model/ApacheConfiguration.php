<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

class ApacheConfiguration implements Renderable {
	
	/**
	 * @var string
	 */
	private $slug;
	
	/**
	 * @var string
	 */
	private $name;
	
	/**
	 * @var ApacheWebsiteHost[]
	 */
	private $websiteHosts;
	
	/**
	 * @var ApacheRedirection[]
	 */
	private $redirections;
	
	/**
	 * @var ApacheProxy[]
	 */
	private $proxies;
	
	/**
	 * ApacheConfiguration constructor
	 *
	 * @param string $slug
	 * @param stdClass $configuration
	 * @throws ApacheConfigurationException
	 */
	public function __construct($slug, $configuration) {
//		var_dump($slug);
//		var_dump($configuration);
		if( empty($slug) ) {
			throw new ApacheConfigurationException('Missing slug');
		}
		if( empty($configuration->name) ) {
			throw new ApacheConfigurationException('Missing name in root configuration');
		}
		$this->slug = $slug;
		$this->name = $configuration->name;
		$hostDefault = !empty($configuration->default_host) ? $configuration->default_host : new stdClass();
		$this->websiteHosts = array();
		$this->redirections = array();
		$this->proxies = array();
		$self = $this;
		// Add website hosts from website_hosts configuration
		if(!empty($configuration->website_hosts)) {
			$this->addHostsTo(
				$this->websiteHosts,
				(array) $configuration->website_hosts,
				function (&$host, $key) use ($self, $hostDefault) {
					$hostConfig = clone $host;
					ApacheWebsiteHost::normalize($hostConfig);
					$self->applyDefaults($hostConfig, $hostDefault);
					$host = new ApacheWebsiteHost($key, $hostConfig);
					// Add website host's redirect to redirections
					if( isset($hostConfig->redirect) ) {
						$redirectHost = clone $hostConfig->redirect;
						$redirectHost->target = $host->getMainUrl();
						ApacheRedirection::normalize($redirectHost);
						$self->applyDefaults($redirectHost, $hostDefault);
						$self->redirections[] = new ApacheRedirection($key . '_redirect', $redirectHost);
					}
				});
		}
		// Add redirections from redirections configuration
		if(!empty($configuration->redirections)) {
			$this->addHostsTo(
				$this->redirections,
				(array) $configuration->redirections,
				function (&$host, $key) use ($self, $hostDefault) {
					$hostConfig = clone $host;
					ApacheRedirection::normalize($hostConfig);
					$self->applyDefaults($hostConfig, $hostDefault);
					$host = new ApacheRedirection($key, $hostConfig);
				});
		}
		// Add proxies from proxies configuration
		if(!empty($configuration->proxies)) {
			$this->addHostsTo($this->proxies,
				(array) $configuration->proxies,
				function (&$host, $key) use ($self, $hostDefault) {
					$hostConfig = clone $host;
					ApacheProxy::normalize($hostConfig);
					$self->applyDefaults($hostConfig, $hostDefault);
					$host = new ApacheProxy($key, $hostConfig);
				});
		}
	}
	
	protected function addHostsTo(&$list, $addList, $callback) {
		array_walk($addList, $callback);
		$list = array_merge($list, $addList);
	}
	
	/**
	 * Apply default host configuration to given host
	 *
	 * @param $host
	 * @param $default
	 */
	protected function applyDefaults(&$host, $default) {
		// Apply defaults to virtual host configuration
		if(!empty($default->admin_email) && empty($host->admin_email)) {
			$host->admin_email = $default->admin_email;
		}
		if(!empty($default->port) && empty($host->port)) {
			$host->port = $default->port;
		}
		// Apply smart defaults to virtual host configuration
		if(!empty($default->ssl_config) && $host->port === AbstractApacheVirtualHost::PORT_HTTPS && empty($host->ssl_config)) {
			$host->ssl_config = $default->ssl_config;
		}
		// Apply smart defaults to authentication configuration
		if(!empty($default->auth) && !empty($host->auth) && !empty($default->auth->type) && empty($host->auth->type)) {
			$host->auth->type = $default->auth->type;
		}
		if(!empty($default->auth) && !empty($host->auth) && !empty($default->auth->name) && empty($host->auth->name)) {
			$host->auth->name = $default->auth->name;
		}
		if(!empty($default->auth) && !empty($host->auth) && !empty($default->auth->user_file) && empty($host->auth->user_file)) {
			$host->auth->user_file = $default->auth->user_file;
		}
		if(!empty($default->auth) && !empty($host->auth) && !empty($default->auth->group_file) && empty($host->auth->group_file)) {
			$host->auth->group_file = $default->auth->group_file;
		}
		if(!empty($default->auth) && !empty($host->auth) && !empty($default->auth->require) && empty($host->auth->require)) {
			$host->auth->require = $default->auth->require;
		}
	}
	
	/**
	 * @return string
	 */
	public function generate() {
		$this->checkGenerate();
		
		// Capture output
		ob_start();
		
		try {
			$this->render();
			// End output buffer and return contents
			$content = ob_get_contents();
			
		} finally {
			ob_end_clean();
		}
		
		return $content;
	}
	
	public function render() {
		foreach( $this->getAllVirtualHosts() as $virtualHost ) {
			$virtualHost->render();
		}
	}
	
	public function checkGenerate() {
//		foreach( $this->getAllVirtualHosts() as $virtualHost ) {
//			// Check SSL configuration of hosts, try to provide if missing or throw error
//			if( $virtualHost->isSecureConnection() && !$virtualHost->getSslConfigurationPath() ) {
//			}
//		}
	}
	
	/**
	 * @return AbstractApacheVirtualHost[]
	 */
	public function getAllVirtualHosts() {
		return array_merge($this->websiteHosts, $this->redirections, $this->proxies);
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
	public function getName() {
		return $this->name;
	}
	
	/**
	 * @return ApacheWebsiteHost[]
	 */
	public function getWebsiteHosts() {
		return $this->websiteHosts;
	}
	
	/**
	 * @return ApacheRedirection[]
	 */
	public function getRedirections() {
		return $this->redirections;
	}
	
	/**
	 * @return ApacheProxy[]
	 */
	public function getProxies() {
		return $this->proxies;
	}
}
