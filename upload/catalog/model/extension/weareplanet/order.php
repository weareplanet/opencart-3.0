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
use WeArePlanet\Model\AbstractModel;

/**
 * Handles the customer order info.
 */
class ModelExtensionWeArePlanetOrder extends AbstractModel {

	public function getButtons($order_id){
		if (!\WeArePlanetHelper::instance($this->registry)->isValidOrder($order_id)) {
			return array();
		}
		
		$this->language->load('extension/payment/weareplanet');
		$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $order_id);
		
		$buttons = array();
		
		if ($this->config->get('weareplanet_download_packaging') && $transaction_info->getState() == \WeArePlanet\Sdk\Model\TransactionState::FULFILL) {
			$buttons[] = $this->getPackagingButton();
		}
		
		if ($this->config->get('weareplanet_download_invoice') && in_array($transaction_info->getState(),
				array(
					\WeArePlanet\Sdk\Model\TransactionState::FULFILL,
					\WeArePlanet\Sdk\Model\TransactionState::COMPLETED,
					\WeArePlanet\Sdk\Model\TransactionState::DECLINE 
				))) {
			$buttons[] = $this->getInvoiceButton();
		}
		
		return $buttons;
	}

	private function getInvoiceButton(){
		return array(
			'text' => $this->language->get('button_invoice'),
			'icon' => 'download',
			'url' => $this->createUrl('extension/weareplanet/pdf/invoice', array(
				'order_id' => $this->request->get['order_id'] 
			)) 
		);
	}

	private function getPackagingButton(){
		return array(
			'text' => $this->language->get('button_packing_slip'),
			'icon' => 'download',
			'url' => $this->createUrl('extension/weareplanet/pdf/packingSlip', array(
				'order_id' => $this->request->get['order_id'] 
			)) 
		);
	}
}