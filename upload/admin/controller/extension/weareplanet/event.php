<?php
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