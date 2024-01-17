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
use \WeArePlanet\Controller\AbstractController;

abstract class ControllerExtensionPaymentWeArePlanetBase extends AbstractController {

	public function index(){
		if (!$this->config->get('weareplanet_status')) {
			return '';
		}
		$this->load->language('extension/payment/weareplanet');
		$data = array();
		
		$data['configuration_id'] = \WeArePlanetHelper::extractPaymentMethodId($this->getCode());
		
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_loading'] = $this->language->get('text_loading');
		
		$this->load->model('extension/payment/' . $this->getCode());
		$data['text_payment_title'] = $this->{"model_extension_payment_{$this->getCode()}"}->getTitle();
		$data['text_further_details'] = $this->language->get('text_further_details');
		
		$data['opencart_js'] = 'catalog/view/javascript/weareplanet.js';
		$data['external_js'] = WeArePlanet\Service\Transaction::instance($this->registry)->getJavascriptUrl();
		
		return $this->loadView('extension/payment/weareplanet/iframe', $data);
	}

	public function confirm(){
		if (!$this->config->get('weareplanet_status')) {
			return '';
		}
		$result = array(
			'status' => false 
		);
		try {
			$transaction = $this->confirmTransaction();
			$result['status'] = true;
			$result['redirect'] = WeArePlanet\Service\Transaction::instance($this->registry)->getPaymentPageUrl($transaction, $this->getCode());
		}
		catch (Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			\WeArePlanetHelper::instance($this->registry)->log($e->getMessage(), \WeArePlanetHelper::LOG_ERROR);
			$this->load->language('extension/payment/weareplanet');
			$result['message'] = $this->language->get('error_confirmation'); 
			unset($this->session->data['order_id']); // this order number cannot be used anymore
			WeArePlanet\Service\Transaction::instance($this->registry)->clearTransactionInSession();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($result));
	}

	private function confirmTransaction(){
		$transaction = WeArePlanet\Service\Transaction::instance($this->registry)->getTransaction($this->getOrderInfo(), false,
				array(
					\WeArePlanet\Sdk\Model\TransactionState::PENDING 
				));
		if ($transaction->getState() == \WeArePlanet\Sdk\Model\TransactionState::PENDING) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			\WeArePlanetHelper::instance($this->registry)->dbTransactionLock($transaction->getLinkedSpaceId(), $transaction->getId());
			WeArePlanet\Service\Transaction::instance($this->registry)->update($this->session->data, true);
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
			return $transaction;
		}
		
		throw new Exception('Transaction is not pending.');
	}
	
	private function getOrderInfo() {
		if(!isset($this->session->data['order_id'])) {
			throw new Exception("No order_id to confirm.");
		}
		$this->load->model('checkout/order');
		return $this->model_checkout_order->getOrder($this->session->data['order_id']);
	}

	protected function getRequiredPermission(){
		return '';
	}

	protected abstract function getCode();
}