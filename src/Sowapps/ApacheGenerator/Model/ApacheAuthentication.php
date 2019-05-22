<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

use Sowapps\ApacheGenerator\Exception\ApacheConfigurationException;
use stdClass;

class ApacheAuthentication implements Renderable {
	
	const TYPE_BASIC = 'Basic';
	
	const DEFAULT_NAME = 'Authentication';
	
	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $userFilePath;
	
	/**
	 * @var string
	 */
	protected $groupFilePath;
	
	/**
	 * @var ApacheAuthenticationRequire[]
	 */
	protected $requires;
	
	/**
	 * ApacheAuthentication constructor.
	 *
	 * @param stdClass $authentication
	 * @throws ApacheConfigurationException
	 */
	public function __construct($authentication) {
		if(empty($authentication->require)) {
			throw new ApacheConfigurationException('Missing require in authentication configuration');
		}
		if(!is_array($authentication->require)) {
			throw new ApacheConfigurationException('Invalid require in authentication configuration');
		}
		$this->type = !empty($authentication->type) ? $authentication->type : self::TYPE_BASIC;
		$this->name = !empty($authentication->name) ? $authentication->name : self::DEFAULT_NAME;
		$this->userFilePath = !empty($authentication->user_file) ? $authentication->user_file : null;
		$this->groupFilePath = !empty($authentication->group_file) ? $authentication->group_file : null;
		$this->requires = array();
		foreach($authentication->require as $require) {
			$this->requires[] = new ApacheAuthenticationRequire($require);
		}
		if($this->userFilePath && (!is_readable($this->userFilePath) || !is_file($this->userFilePath))) {
			throw new ApacheConfigurationException('Invalid user file path in authentication configuration');
		}
		if($this->groupFilePath && (!is_readable($this->groupFilePath) || !is_file($this->groupFilePath))) {
			throw new ApacheConfigurationException('Invalid group file path in authentication configuration');
		}
	}
	
	public function render() {
		echo "
		
		AuthType {$this->getType()}
		AuthName \"{$this->getName()}\"";
		if($this->userFilePath) {
			echo "
		AuthUserFile {$this->getUserFilePath()}";
		}
		if($this->groupFilePath) {
			echo "
		AuthGroupFile {$this->getGroupFilePath()}";
		}
		foreach($this->getRequires() as $require) {
			$require->render();
		}
	}
	
	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * @return string
	 */
	public function getUserFilePath() {
		return $this->userFilePath;
	}
	
	/**
	 * @return string
	 */
	public function getGroupFilePath() {
		return $this->groupFilePath;
	}
	
	/**
	 * @return ApacheAuthenticationRequire[]
	 */
	public function getRequires() {
		return $this->requires;
	}
}
