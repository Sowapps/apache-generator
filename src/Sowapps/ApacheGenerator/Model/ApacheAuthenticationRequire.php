<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

class ApacheAuthenticationRequire implements Renderable {
	
	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var boolean
	 */
	protected $reject;
	
	/**
	 * @var string
	 */
	protected $subject;
	
	/**
	 * ApacheAuthentication constructor.
	 *
	 * @param stdClass $authentication
	 * @throws ApacheConfigurationException
	 */
	public function __construct($authentication) {
		if(empty($authentication->type)) {
			throw new ApacheConfigurationException('Missing type in authentication require configuration');
		}
		$this->type = $authentication->type;
		$this->subject = !empty($authentication->subject) ? $authentication->subject : null;
		$this->reject = !empty($authentication->reject);
	}
	
	public function render() {
		/**
		 * Require user cartman
		 * Require group sowapps
		 * Require valid-user
		 */
		echo "
		Require {$this->getRejectString()}{$this->getType()} {$this->getSubject()}";
	}
	
	public function getRejectString() {
		return $this->reject ? 'not ' : '';
	}
	
	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * @return boolean
	 */
	public function isReject() {
		return $this->reject;
	}
	
	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}
}
