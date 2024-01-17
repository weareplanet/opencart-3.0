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
use WeArePlanet\Controller\AbstractController;

class ControllerExtensionWeArePlanetAddress extends AbstractController {
	
	private static $ADDRESS_FIELDS = array(
		'firstname',
		'lastname',
		'company',
		'address_id',
		'address_1',
		'address_2',
		'city',
		'postcode',
		'country_id',
		'zone_id',
		'custom_field'
	);
	
	private static $ADDRESS_TYPES = array(
		'payment',
		'shipping'
	);
	
	public function update(){
		foreach(self::$ADDRESS_TYPES as $type) {
			foreach(self::$ADDRESS_FIELDS as $field) {
				if(isset($this->request->get[$type . "_" . $field])) {
					$this->session->data[$type . "_" . $field] = $this->request->get[$type . "_" . $field];
				}
			}
		}
	}
}