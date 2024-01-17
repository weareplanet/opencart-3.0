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

namespace WeArePlanet\Controller;

abstract class AbstractEvent extends AbstractController {

	protected function validate(){
		$this->language->load('extension/payment/weareplanet');
		$this->validatePermission();
		// skip valdiating order.
	}
}