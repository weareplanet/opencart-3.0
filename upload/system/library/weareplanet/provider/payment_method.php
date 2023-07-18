<?php

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