<?php
/**
 * WeArePlanet OpenCart
 *
 * This OpenCart module enables to process payments with WeArePlanet (https://www.weareplanet.com).
 *
 * @package Whitelabelshortcut\WeArePlanet
 * @author Planet Merchant Services Ltd (https://www.weareplanet.com)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
require_once modification(DIR_SYSTEM . 'library/weareplanet/helper.php');

class ControllerExtensionWeArePlanetEvent extends WeArePlanet\Controller\AbstractEvent {

	/**
	 * Re-Creates required files for display of payment methods.
	 */
	public function createMethodConfigurationFiles(){
		try {
			$this->validate();
			$this->load->model('extension/weareplanet/dynamic');
			$this->model_extension_weareplanet_dynamic->install();
		}
		catch (Exception $e) {
			// ensure that permissions etc. do not cause page loads to fail
			return;
		}
	}

	protected function getRequiredPermission(){
		return 'extension/weareplanet/event';
	}
}