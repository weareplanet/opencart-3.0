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

namespace WeArePlanet\Provider;

/**
 * Provider of payment method information from the gateway.
 */
class PaymentMethod extends AbstractProvider {

	protected function __construct(\Registry $registry){
		parent::__construct($registry, 'oc_weareplanet_payment_methods');
	}

	/**
	 * Returns the payment method by the given id.
	 *
	 * @param int $id
	 * @return \WeArePlanet\Sdk\Model\PaymentMethod
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment methods.
	 *
	 * @return \WeArePlanet\Sdk\Model\PaymentMethod[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
		$method_service = new \WeArePlanet\Sdk\Service\PaymentMethodService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $method_service->all();
	}

	protected function getId($entry){
		/* @var \WeArePlanet\Sdk\Model\PaymentMethod $entry */
		return $entry->getId();
	}
}