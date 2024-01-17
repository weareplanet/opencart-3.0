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

class ControllerExtensionWeArePlanetVoid extends \WeArePlanet\Controller\AbstractController {

	public function index(){
		$this->response->addHeader('Content-Type: application/json');
		try {
			$this->validate();
			
			$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $this->request->get['order_id']);
			
			$running = \WeArePlanet\Entity\VoidJob::loadRunningForOrder($this->registry, $transaction_info->getOrderId());
			if ($running->getId()) {
				throw new \Exception($this->language->get('error_already_running'));
			}
			
			if (!\WeArePlanetHelper::instance($this->registry)->isCompletionPossible($transaction_info)) {
				throw new \Exception($this->language->get('error_cannot_create_job'));
			}
			
			$job = \WeArePlanet\Service\VoidJob::instance($this->registry)->create($transaction_info);
			\WeArePlanet\Service\VoidJob::instance($this->registry)->send($job);
			
			$this->load->model('extension/weareplanet/order');
			$new_buttons = $this->model_extension_weareplanet_order->getButtons($this->request->get['order_id']);
			
			$this->response->setOutput(
					json_encode(
							array(
								'success' => sprintf($this->language->get('message_void_success'), $transaction_info->getTransactionId()),
								'buttons' => $new_buttons 
							)));
		}
		catch (Exception $e) {
			$this->response->setOutput(json_encode(array(
				'error' => $e->getMessage() 
			)));
		}
	}

	protected function getRequiredPermission(){
		return 'extension/weareplanet/void';
	}
}