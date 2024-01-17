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

/**
 * Prevents loading inexisting model, but allow plugin status to be correct
 */
class ModelExtensionPaymentWeArePlanet extends Model {

	public function getMethod($address, $total){
		return array();
	}
}