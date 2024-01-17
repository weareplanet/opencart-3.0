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

namespace WeArePlanet\Service;

/**
 * This service provides functions to deal with WeArePlanet refunds.
 */
class Refund extends AbstractJob {

	private function getExternalRefundId(\WeArePlanet\Entity\TransactionInfo $transaction_info){
		$count = \WeArePlanet\Entity\RefundJob::countForOrder($this->registry, $transaction_info->getOrderId());
		return 'r-' . $transaction_info->getOrderId() . '-' . ($count + 1);
	}

	public function create(\WeArePlanet\Entity\TransactionInfo $transaction_info, array $reductions, $restock){
		try {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			\WeArePlanetHelper::instance($this->registry)->dbTransactionLock($transaction_info->getSpaceId(), $transaction_info->getTransactionId());
			
			$job = \WeArePlanet\Entity\RefundJob::loadNotSentForOrder($this->registry, $transaction_info->getOrderId());
			$reduction_line_items = $this->getLineItemReductions($reductions);
			/* @var $job \WeArePlanet\Entity\RefundJob */
			if (!$job->getId()) {
				$job = $this->createBase($transaction_info, $job);
				$job->setReductionItems($reduction_line_items);
				$job->setRestock($restock);
				$job->setExternalId($this->getExternalRefundId($transaction_info));
				$job->save();
			}
			else if ($job->getReductionItems() != $reduction_line_items) {
				throw new \Exception(\WeArePlanetHelper::instance($this->registry)->getTranslation('error_already_running'));
			}
			
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
		}
		catch (\Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			throw $e;
		}
		
		return $job;
	}

	public function send(\WeArePlanet\Entity\RefundJob $job){
		try {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			\WeArePlanetHelper::instance($this->registry)->dbTransactionLock($job->getSpaceId(), $job->getTransactionId());
			
			$service = new \WeArePlanet\Sdk\Service\RefundService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
			$operation = $service->refund($job->getSpaceId(), $this->createRefund($job));
			
			if ($operation->getFailureReason() != null) {
				$job->setFailureReason($operation->getFailureReason()->getDescription());
			}
			
			$labels = array();
			foreach ($operation->getLabels() as $label) {
				$labels[$label->getDescriptor()->getId()] = $label->getContentAsString();
			}
			$job->setLabels($labels);
			
			$job->setJobId($operation->getId());
			$job->setState(\WeArePlanet\Entity\AbstractJob::STATE_SENT);
			$job->setAmount($operation->getAmount());
			$job->save();
			
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
			return $job;
		}
		catch (\WeArePlanet\Sdk\ApiException $api_exception) {
		}
		catch (\Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			throw $e;
		}
		
		return $this->handleApiException($job, $api_exception);
	}

	private function createRefund(\WeArePlanet\Entity\RefundJob $job){
		$refund_create = new \WeArePlanet\Sdk\Model\RefundCreate();
		$refund_create->setReductions($job->getReductionItems());
		$refund_create->setExternalId($job->getExternalId());
		$refund_create->setTransaction($job->getTransactionId());
		$refund_create->setType(\WeArePlanet\Sdk\Model\RefundType::MERCHANT_INITIATED_ONLINE);
		return $refund_create;
	}

	private function getLineItemReductions(array $reductions){
		$reduction_line_items = array();
		foreach ($reductions as $reduction) {
			if ($reduction['quantity'] || $reduction['unit_price']) {
				$line_item = new \WeArePlanet\Sdk\Model\LineItemReductionCreate();
				$line_item->setLineItemUniqueId($reduction['id']);
				$line_item->setQuantityReduction(floatval($reduction['quantity']));
				$line_item->setUnitPriceReduction(floatval($reduction['unit_price']));
				$reduction_line_items[] = $line_item;
			}
		}
		return $reduction_line_items;
	}
}