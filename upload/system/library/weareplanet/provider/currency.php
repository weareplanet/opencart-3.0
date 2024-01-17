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