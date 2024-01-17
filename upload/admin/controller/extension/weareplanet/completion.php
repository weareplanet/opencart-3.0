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

class ControllerExtensionWeArePlanetCompletion extends \WeArePlanet\Controller\AbstractController {

	public function index(){
		$this->response->addHeader('Content-Type: application/json');
		try {
			$this->validate();
			
			$completion_job = \WeArePlanet\Entity\CompletionJob::loadRunningForOrder($this->registry, $this->request->get['order_id']);
			
			if ($completion_job->getId() !== null) {
				throw new Exception($this->language->get('error_already_running'));
			}
			
			$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $this->request->get['order_id']);
			
			if (!\WeArePlanetHelper::instance($this->registry)->isCompletionPossible($transaction_info)) {
				throw new \Exception($this->language->get('error_cannot_create_job'));
			}
			
			// ensure line items are current (e.g. events were skipped when order is edited)
			\WeArePlanet\Service\Transaction::instance($this->registry)->updateLineItemsFromOrder($this->request->get['order_id']);
			
			$job = \WeArePlanet\Service\Completion::instance($this->registry)->create($transaction_info);
			\WeArePlanet\Service\Completion::instance($this->registry)->send($job);
			
			$this->load->model('extension/weareplanet/order');
			$new_buttons = $this->model_extension_weareplanet_order->getButtons($this->request->get['order_id']);
			
			$this->response->setOutput(
					json_encode(
							array(
								'success' => sprintf($this->language->get('message_completion_success'), $transaction_info->getTransactionId()),
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
		return 'extension/weareplanet/completion';
	}
}