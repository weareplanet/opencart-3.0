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

class ControllerExtensionWeArePlanetUpdate extends \WeArePlanet\Controller\AbstractController {

	public function index(){
		$this->response->addHeader('Content-Type: application/json');
		
		try {
			$this->validate();
			
			$message = $this->language->get('message_refresh_success');
			
			$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $this->request->get['order_id']);
			if ($transaction_info->getId() === null) {
				throw new Exception($this->language->get('error_not_weareplanet'));
			}
			
			$completion_job = \WeArePlanet\Entity\CompletionJob::loadNotSentForOrder($this->registry, $this->request->get['order_id']);
			if ($completion_job->getId()) {
				\WeArePlanet\Service\Completion::instance($this->registry)->send($completion_job);
				$message .= '<br/>' . sprintf($this->language->get('message_resend_completion'), $completion_job->getId());
			}
			
			$void_job = \WeArePlanet\Entity\VoidJob::loadNotSentForOrder($this->registry, $this->request->get['order_id']);
			if ($void_job->getId()) {
				\WeArePlanet\Service\VoidJob::instance($this->registry)->send($void_job);
				$message .= '<br/>' . sprintf($this->language->get('message_resend_void'), $void_job->getId());
			}
			
			$refund_job = \WeArePlanet\Entity\RefundJob::loadNotSentForOrder($this->registry, $this->request->get['order_id']);
			if ($refund_job->getId()) {
				\WeArePlanet\Service\Refund::instance($this->registry)->send($refund_job);
				$message .= '<br/>' . sprintf($this->language->get('message_resend_refund'), $refund_job->getId());
			}
			
			$this->load->model('extension/weareplanet/order');
			$new_buttons = $this->model_extension_weareplanet_order->getButtons($this->request->get['order_id']);
			
			$this->response->setOutput(json_encode([
				'success' => $message,
				'buttons' => $new_buttons 
			]));
			return;
		}
		catch (Exception $e) {
			$this->response->setOutput(json_encode([
				'error' => $e->getMessage() 
			]));
		}
	}

	protected function getRequiredPermission(){
		return 'extension/weareplanet/update';
	}
}