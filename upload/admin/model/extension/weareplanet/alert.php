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
use WeArePlanet\Model\AbstractModel;

/**
 * Handles the display of alerts in the top right.
 * Is used in combination with
 * - controller/extension/weareplanet/alert.php
 * - system/library/weareplanet/modification/WeArePlanetAlerts.ocmod.xml
 */
class ModelExtensionWeArePlanetAlert extends AbstractModel {
	private $alerts;

	public function getAlertsTitle(){
		$this->load->language('extension/payment/weareplanet');
		return $this->language->get('title_notifications');
	}

	public function getAlerts(){
		if ($this->alerts == null) {
			try {
				$this->load->language('extension/payment/weareplanet');
				$this->alerts = array();
				$alert_entities = \WeArePlanet\Entity\Alert::loadAll($this->registry);
			
				foreach ($alert_entities as $alert_entity) {
					$this->alerts[] = array(
						'url' => $this->createUrl($alert_entity->getRoute(),
								array(
									\WeArePlanetVersionHelper::TOKEN => $this->session->data[\WeArePlanetVersionHelper::TOKEN] 
								)),
						'text' => $this->language->get($alert_entity->getKey()),
						'level' => $alert_entity->getLevel(),
						'count' => $alert_entity->getCount() 
					);
				}
			}
			catch(\Exception $e) {
				// We ignore errors here otherwise we might not be albe to display the admin backend UI.
			}
		}
		return $this->alerts;
	}

	public function getAlertCount(){
		$count = 0;
		foreach ($this->getAlerts() as $alert) {
			$count += $alert['count'];
		}
		return $count;
	}
}
