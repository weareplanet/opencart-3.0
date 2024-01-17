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
 * Provider of payment connector information from the gateway.
 */
class PaymentConnector extends AbstractProvider {

	protected function __construct(\Registry $registry){
		parent::__construct($registry, 'oc_weareplanet_payment_connectors');
	}

	/**
	 * Returns the payment connector by the given id.
	 *
	 * @param int $id
	 * @return \WeArePlanet\Sdk\Model\PaymentConnector
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment connectors.
	 *
	 * @return \WeArePlanet\Sdk\Model\PaymentConnector[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
		$connector_service = new \WeArePlanet\Sdk\Service\PaymentConnectorService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $connector_service->all();
	}

	protected function getId($entry){
		/* @var \WeArePlanet\Sdk\Model\PaymentConnector $entry */
		return $entry->getId();
	}
}