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

namespace WeArePlanet\Webhook;

/**
 * Webhook processor to handle transaction completion state transitions.
 */
class TransactionCompletion extends AbstractOrderRelated {

	/**
	 *
	 * @see AbstractOrderRelated::loadEntity()
	 * @return \WeArePlanet\Sdk\Model\TransactionCompletion
	 */
	protected function loadEntity(Request $request){
		$completion_service = new \WeArePlanet\Sdk\Service\TransactionCompletionService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $completion_service->read($request->getSpaceId(), $request->getEntityId());
	}

	protected function getOrderId($completion){
		/* @var \WeArePlanet\Sdk\Model\TransactionCompletion $completion */
		return $completion->getLineItemVersion()->getTransaction()->getMerchantReference();
	}
	
	protected function getTransactionId($entity){
		/* @var $entity \WeArePlanet\Sdk\Model\TransactionCompletion */
		return $entity->getLinkedTransaction();
	}
	
	protected function processOrderRelatedInner(array $order_info, $completion){
		/* @var \WeArePlanet\Sdk\Model\TransactionCompletion $completion */
		switch ($completion->getState()) {
			case \WeArePlanet\Sdk\Model\TransactionCompletionState::FAILED:
				$this->failed($completion, $order_info);
				break;
			case \WeArePlanet\Sdk\Model\TransactionCompletionState::SUCCESSFUL:
				$this->success($completion, $order_info);
				break;
			default:
				// Ignore PENDING & CREATE
				// Nothing to do.
				break;
		}
	}

	protected function success(\WeArePlanet\Sdk\Model\TransactionCompletion $completion, array $order_info){
		$completion_job = \WeArePlanet\Entity\CompletionJob::loadByJob($this->registry, $completion->getLinkedSpaceId(), $completion->getId());
		if (!$completion_job->getId()) {
			//We have no completion job with this id -> the server could not store the id of the completion after sending the request. (e.g. connection issue or crash)
			//We only have on running completion which was not yet processed successfully and use it as it should be the one the webhook is for.
			$completion_job = \WeArePlanet\Entity\CompletionJob::loadRunningForOrder($this->registry, $order_info['order_id']);
			if (!$completion_job->getId()) {
				//completion not initated in shop backend ignore
				return;
			}
			$completion_job->setJobId($completion->getId());
		}
		$completion_job->setAmount($completion->getPaymentInformation());
		$completion_job->setState(\WeArePlanet\Entity\CompletionJob::STATE_SUCCESS);
		
		$completion_job->save();
	}

	protected function failed(\WeArePlanet\Sdk\Model\TransactionCompletion $completion, array $order_info){
		$completion_job = \WeArePlanet\Entity\CompletionJob::loadByJob($this->registry, $completion->getLinkedSpaceId(), $completion->getId());
		if (!$completion_job->getId()) {
			//We have no completion job with this id -> the server could not store the id of the completion after sending the request. (e.g. connection issue or crash)
			//We only have on running completion which was not yet processed successfully and use it as it should be the one the webhook is for.
			$completion_job = \WeArePlanet\Entity\CompletionJob::loadRunningForOrder($this->registry, $order_info['order_id']);
			if (!$completion_job->getId()) {
				return;
			}
			$completion_job->setJobId($completion->getId());
		}
		if ($completion->getFailureReason() != null) {
			$completion_job->setFailureReason($completion->getFailureReason()->getDescription());
		}
		
		$completion_job->setAmount($completion->getPaymentInformation());
		$completion_job->setState(\WeArePlanet\Entity\CompletionJob::STATE_FAILED_CHECK);
		\WeArePlanet\Entity\Alert::loadFailedJobs($this->registry)->modifyCount(1);
		
		$completion_job->save();
	}
}