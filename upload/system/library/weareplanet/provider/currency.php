<?php

namespace WeArePlanet\Provider;

/**
 * Provider of currency information from the gateway.
 */
class Currency extends AbstractProvider {

	protected function __construct(\Registry $registry){
		parent::__construct($registry, 'oc_weareplanet_currencies');
	}

	/**
	 * Returns the currency by the given code.
	 *
	 * @param string $code
	 * @return \WeArePlanet\Sdk\Model\RestCurrency
	 */
	public function find($code){
		return parent::find($code);
	}

	/**
	 * Returns a list of currencies.
	 *
	 * @return \WeArePlanet\Sdk\Model\RestCurrency[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
		$currency_service = new \WeArePlanet\Sdk\Service\CurrencyService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $currency_service->all();
	}

	protected function getId($entry){
		/* @var \WeArePlanet\Sdk\Model\RestCurrency $entry */
		return $entry->getCurrencyCode();
	}
}