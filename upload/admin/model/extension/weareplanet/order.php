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
 * Handles the button on the order info page.
 */
class ModelExtensionWeArePlanetOrder extends AbstractModel {

	/**
	 * Returns all jobs with status FAILED_CHECK, and moves these into state FAILED_DONE.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function getFailedJobs($order_id){
		$this->language->load('extension/payment/weareplanet');
		$jobs = array_merge($this->getJobMessages(\WeArePlanet\Entity\VoidJob::loadFailedCheckedForOrder($this->registry, $order_id)),
				$this->getJobMessages(\WeArePlanet\Entity\CompletionJob::loadFailedCheckedForOrder($this->registry, $order_id)),
				$this->getJobMessages(\WeArePlanet\Entity\RefundJob::loadFailedCheckedForOrder($this->registry, $order_id)));
		\WeArePlanet\Entity\VoidJob::markFailedAsDone($this->registry, $order_id);
		\WeArePlanet\Entity\CompletionJob::markFailedAsDone($this->registry, $order_id);
		\WeArePlanet\Entity\RefundJob::markFailedAsDone($this->registry, $order_id);
		return $jobs;
	}

	public function getButtons($order_id){
		$this->language->load('extension/payment/weareplanet');
		if (!isset($this->request->get['order_id'])) {
			return array();
		}
		$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $order_id);
		if ($transaction_info->getId() == null) {
			return array();
		}
		
		$buttons = array();
		
		if (\WeArePlanetHelper::instance($this->registry)->isCompletionPossible($transaction_info)) {
			$buttons[] = $this->getCompletionButton();
			$buttons[] = $this->getVoidButton();
		}
		
		if (\WeArePlanetHelper::instance($this->registry)->isRefundPossible($transaction_info)) {
			$buttons[] = $this->getRefundButton();
		}
		
		if (\WeArePlanetHelper::instance($this->registry)->hasRunningJobs($transaction_info)) {
			$buttons[] = $this->getUpdateButton();
		}
		
		return $buttons;
	}

	/**
	 *
	 * @param \WeArePlanet\Entity\AbstractJob[] $jobs
	 */
	private function getJobMessages($jobs){
		$job_messages = array();
		foreach ($jobs as $job) {
			$format = $this->language->get('weareplanet_failed_job_message');
			
			if ($job instanceof \WeArePlanet\Entity\CompletionJob) {
				$type = $this->language->get('completion_job');
			}
			else if ($job instanceof \WeArePlanet\Entity\RefundJob) {
				$type = $this->language->get('refund_job');
			}
			else if ($job instanceof \WeArePlanet\Entity\VoidJob) {
				$type = $this->language->get('void_job');
			}
			else {
				$type = get_class($job);
			}
			
			$format = '%s %s: %s';
			$job_messages[] = sprintf($format, $type, $job->getJobId(), $job->getFailureReason());
		}
		return $job_messages;
	}

	private function getVoidButton(){
		return array(
			'text' => $this->language->get('button_void'),
			'icon' => 'ban',
			'route' => 'extension/weareplanet/void' 
		);
	}

	private function getCompletionButton(){
		return array(
			'text' => $this->language->get('button_complete'),
			'icon' => 'check',
			'route' => 'extension/weareplanet/completion' 
		);
	}

	private function getRefundButton(){
		return array(
			'text' => $this->language->get('button_refund'),
			'icon' => 'reply',
			'route' => 'extension/weareplanet/refund/page' 
		);
	}

	private function getUpdateButton(){
		return array(
			'text' => $this->language->get('button_update'),
			'icon' => 'refresh',
			'route' => 'extension/weareplanet/update' 
		);
	}
}