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

/**
 * Handles the display of alerts in the top right.
 * Is used in combination with
 * - model/extension/weareplanet/alert.php
 * - system/library/weareplanet/modification/WeArePlanetAlerts.ocmod.xml
 */
class ControllerExtensionWeArePlanetAlert extends WeArePlanet\Controller\AbstractEvent {

	/**
	 * Redirects the user to the manual task overview in the weareplanet backend.
	 */
	public function manual(){
		try {
			$this->validate();
			$this->response->redirect(\WeArePlanetHelper::getBaseUrl() . '/s/' . $this->config->get('weareplanet_space_id') . '/manual-task/list');
		}
		catch (Exception $e) {
			$this->displayError($e->getMessage());
		}
	}

	/**
	 * Redirect the user to the order with the oldest checkable failed job.
	 */
	public function failed(){
		try {
			$oldest_failed = \WeArePlanet\Entity\RefundJob::loadOldestCheckable($this->registry);
			if (!$oldest_failed->getId()) {
				$oldest_failed = \WeArePlanet\Entity\CompletionJob::loadOldestCheckable($this->registry);
			}
			if (!$oldest_failed->getId()) {
				$oldest_failed = \WeArePlanet\Entity\VoidJob::loadOldestCheckable($this->registry);
			}
			$this->response->redirect(
					$this->createUrl('sale/order/info',
							array(
								\WeArePlanetVersionHelper::TOKEN => $this->session->data[\WeArePlanetVersionHelper::TOKEN],
								'order_id' => $oldest_failed->getOrderId() 
							)));
		}
		catch (Exception $e) {
			$this->displayError($e->getMessage());
		}
	}

	protected function getRequiredPermission(){
		return 'extension/weareplanet/alert';
	}
}