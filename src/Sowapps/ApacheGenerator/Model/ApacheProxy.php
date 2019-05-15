<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\ApacheGenerator\Model;

class ApacheProxy extends ApacheRedirection {
	
	public function renderContent() {
		$isTargetSecure = $this->isTargetSecure();
		if( $this->isSecureConnection() && !$isTargetSecure ) {
			echo "
	RequestHeader edit Destination ^https: http: early";
		}
		if( $isTargetSecure ) {
			echo "
	SSLProxyEngine On";
		}
		echo "
	ProxyPreserveHost On
	ProxyPass {$this->getPath()} {$this->getTarget()}
	ProxyPassReverse {$this->getPath()} {$this->getTarget()}";
	}
	
	public function isTargetSecure() {
		return parse_url($this->getTarget(), PHP_URL_SCHEME) === 'https';
	}
	
	protected function getTitle() {
		return sprintf('Proxy %s of %s', $this->getSlug(), $this->getHost());
	}
}
