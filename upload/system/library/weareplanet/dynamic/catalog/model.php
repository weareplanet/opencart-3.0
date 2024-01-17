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
require_once (DIR_SYSTEM . "library/weareplanet/helper.php");

abstract class ModelExtensionPaymentWeArePlanetBase extends Model {
	private static $paymentMethods;

	public abstract function getTitle();

	protected abstract function getCode();

	protected abstract function getSortOrder();

	protected abstract function getTerms();

	public function getMethod($address, $total){
		if (!$this->config->get('weareplanet_status')) {
			return array();
		}
		
		// for journal3 one step checkout the user data is empty by default
		// i assume this is some oversight by the folks at journal3
		$data = $this->registry->get('session')->data;
		if(isset($data['j3_checkout_id']) && !isset($this->session->data['user_id'])) {
			$this->session->data['user_id'] = $data['j3_checkout_id'];
		}

		// check if transaction can be saved to the session.
		if (\WeArePlanetHelper::instance($this->registry)->getCustomerSessionIdentifier() === null) {
			return array();
		}
		
		$order_info = array(
			'payment_address' => $address 
		);
		$billing = \WeArePlanetHelper::instance($this->registry)->getAddress('payment');
		$shipping = \WeArePlanetHelper::instance($this->registry)->getAddress('shipping', $order_info);
		if (empty($billing) && empty($shipping)) {
			return array();
		}
		
		try {
			if (isset($this->session->data['order_id'])) {
				$transaction = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $this->session->data['order_id']);
				if ($transaction->getTransactionId() &&
						 !in_array($transaction->getState(),
								array(
									\WeArePlanet\Sdk\Model\TransactionState::PENDING,
									\WeArePlanet\Sdk\Model\TransactionState::CREATE 
								))) {
					unset($this->session->data['order_id']);
				}
			}
			
			$available_methods = \WeArePlanet\Service\Transaction::instance($this->registry)->getPaymentMethods($order_info);
			$configuration_id = \WeArePlanetHelper::extractPaymentMethodId($this->getCode());
			
			foreach ($available_methods as $method) {
				if ($method->getId() == $configuration_id) {
					return [
						'title' => $this->getTitle(),
						'code' => $this->getCode(),
						'terms' => $this->getTerms(),
						'sort_order' => $this->getSortOrder() 
					];
				}
			}
		}
		catch (Exception $e) {
		}
		return array();
	}
}