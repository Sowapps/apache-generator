<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

class ApacheConfiguration implements Renderable {
	
	const DEFAULT_PORT = 80;
	
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
		if( empty($slug) ) {
			throw new ApacheConfigurationException('Missing slug');
		}
		if( empty($configuration->name) ) {
			throw new ApacheConfigurationException('Missing name in root configuration');
		}
		$this->slug = $slug;
		$this->name = $configuration->name;
		$this->websiteHosts = array();
		$this->redirections = array();
		$this->proxies = array();
		$self = $this;
		// Host default
		$hostDefault = !empty($configuration->default_host) ? $configuration->default_host : new stdClass();
		if( !isset($hostDefault->port) ) {
			$hostDefault->port = self::DEFAULT_PORT;
		}
		ApacheWebsiteHost::normalize($hostDefault);
		// Templates
		$templates = !empty($configuration->templates) ? (array) $configuration->templates : array();
		foreach($templates as &$template) {
			ApacheRedirection::normalize($template);
		}
		// Add website hosts from website_hosts configuration
		if( !empty($configuration->website_hosts) ) {
			$this->addHostsTo(
				$this->websiteHosts,
				(array) $configuration->website_hosts,
				function (&$host, $key) use ($self, $hostDefault, $templates) {
					$hostConfig = clone $host;
					$hostConfig->slug = $key;
					$self->applyDefaults($hostConfig, $hostDefault, $templates);
					$host = new ApacheWebsiteHost($hostConfig);
					// Add implicit website host's redirections
					if( isset($hostConfig->implicit_redirect) ) {
						$redirectHost = (object) array(
							'slug' => $key . '_impredir',
							'target' => $host->getMainUrl(),
						);
						switch( $hostConfig->implicit_redirect ) {
							case 'parent':
								list(, $redirectHost->host) = explode('.', $hostConfig->host, 2);
								break;
							case 'www':
								$redirectHost->host = 'www.' . $hostConfig->host;
								break;
							case 'subdomains':
								$redirectHost->host = 'www.' . $hostConfig->host;
								$redirectHost->aliases = array('*' . $hostConfig->host);
								break;
							case 'parent+subdomains':
								list(, $redirectHost->host) = explode('.', $hostConfig->host, 2);
								$redirectHost->aliases = array('*' . $redirectHost->host);
								break;
							default:
								throw new ApacheConfigurationException(sprintf('Invalid implicit redirect value "%s" in website host configuration', $hostConfig->implicit_redirect));
						}
						$self->applyDefaults($redirectHost, $hostDefault, $templates);
						$self->redirections[] = new ApacheRedirection($redirectHost);
					}
					// Add website host's redirect to redirections
					if( isset($hostConfig->redirect) ) {
						$redirectHost = clone $hostConfig->redirect;
						$redirectHost->slug = $key . '_redirect';
						$redirectHost->target = $host->getMainUrl();
						$self->applyDefaults($redirectHost, $hostDefault, $templates);
						$self->redirections[] = new ApacheRedirection($redirectHost);
					}
				});
		}
		// Add redirections from redirections configuration
		if( !empty($configuration->redirections) ) {
			$this->addHostsTo(
				$this->redirections,
				(array) $configuration->redirections,
				function (&$host, $key) use ($self, $hostDefault, $templates) {
					$hostConfig = clone $host;
					$hostConfig->slug = $key;
					$self->applyDefaults($hostConfig, $hostDefault, $templates);
					$host = new ApacheRedirection($hostConfig);
				});
		}
		// Add proxies from proxies configuration
		if( !empty($configuration->proxies) ) {
			$this->addHostsTo($this->proxies,
				(array) $configuration->proxies,
				function (&$host, $key) use ($self, $hostDefault, $templates) {
					$hostConfig = clone $host;
					$hostConfig->slug = $key;
					$self->applyDefaults($hostConfig, $hostDefault, $templates);
					$host = new ApacheProxy($hostConfig);
				});
		}
	}
	
	/**
	 * Apply callback and merge host lists
	 *
	 * @param $list
	 * @param $addList
	 * @param $callback
	 */
	protected function addHostsTo(&$list, $addList, $callback) {
		array_walk($addList, $callback);
		$list = array_merge($list, $addList);
	}
	
	/**
	 * Apply default host configuration to given host
	 *
	 * @param $host
	 * @param $default
	 * @throws ApacheConfigurationException
	 */
	protected function applyDefaults(&$host, $default, $templates) {
		$host->templates = !empty($host->templates) ? encapsulate($host->templates) : array();
		ApacheRedirection::normalize($host);
		foreach($host->templates as $templateKey) {
			if(!isset($templates[$templateKey])) {
				throw new ApacheConfigurationException(sprintf('Unknown template %s in host %s', $templateKey, $host->slug));
			}
			$this->applyHost($host, $templates[$templateKey], true);
		}
		$this->applyHost($host, $default);
		ApacheRedirection::normalize($host);
	}
	
	/**
	 * Apply default to host configuration
	 *
	 * @param object $host The host to update
	 * @param object $default The default to apply
	 * @param bool $force Force to use default, disable smart usage
	 */
	protected function applyHost(&$host, $default, $force = false) {
		// Apply defaults to virtual host configuration
		if( !empty($default->admin_email) && empty($host->admin_email) ) {
			$host->admin_email = $default->admin_email;
		}
		if( !empty($default->port) && empty($host->port) ) {
			$host->port = $default->port;
		}
		if( !empty($default->implicit_redirect) && !isset($host->implicit_redirect) ) {
			$host->implicit_redirect = $default->implicit_redirect;
		}
		// Apply smart defaults to virtual host configuration
		if( !empty($default->ssl_config) && $host->port === AbstractApacheVirtualHost::PORT_HTTPS && empty($host->ssl_config) ) {
			$host->ssl_config = $default->ssl_config;
		}
		if( $force && !empty($default->auth) && empty($host->auth) ) {
			// Force auth if unavailable
			$host->auth = clone $default->auth;
		} else {
			// Apply smart defaults to authentication configuration
			if( !empty($default->auth) && !empty($host->auth) && !empty($default->auth->type) && empty($host->auth->type) ) {
				$host->auth->type = $default->auth->type;
			}
			if( !empty($default->auth) && !empty($host->auth) && !empty($default->auth->name) && empty($host->auth->name) ) {
				$host->auth->name = $default->auth->name;
			}
			if( !empty($default->auth) && !empty($host->auth) && !empty($default->auth->user_file) && empty($host->auth->user_file) ) {
				$host->auth->user_file = $default->auth->user_file;
			}
			if( !empty($default->auth) && !empty($host->auth) && !empty($default->auth->group_file) && empty($host->auth->group_file) ) {
				$host->auth->group_file = $default->auth->group_file;
			}
			if( !empty($default->auth) && !empty($host->auth) && !empty($default->auth->require) && empty($host->auth->require) ) {
				$host->auth->require = $default->auth->require;
			}
		}
	}
	
	/**
	 * Generate apache2 configuration content for this configuration
	 *
	 * @return string
	 */
	public function generate() {
		// Capture output
		ob_start();
		
		try {
			$this->render();
			// End output buffer and return contents
			return ob_get_contents();
			
		} finally {
			// In case of exception or in case of success, we end output buffer
			// We let all exceptions get out
			ob_end_clean();
		}
	}
	
	/**
	 * Render apache2 configuration for all virtual hosts to output buffer
	 */
	public function render() {
		foreach( $this->getAllVirtualHosts() as $virtualHost ) {
			$virtualHost->render();
		}
	}
	
	/**
	 * Get all the virtual hosts of this configuration
	 *
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
