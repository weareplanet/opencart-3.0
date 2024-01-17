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
 * This service provides functions to deal with WeArePlanet voids.
 */
class VoidJob extends AbstractJob {

	public function create(\WeArePlanet\Entity\TransactionInfo $transaction_info){
		try {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			\WeArePlanetHelper::instance($this->registry)->dbTransactionLock($transaction_info->getSpaceId(), $transaction_info->getTransactionId());
			
			$job = \WeArePlanet\Entity\VoidJob::loadNotSentForOrder($this->registry, $transaction_info->getOrderId());
			if (!$job->getId()) {
				$job = $this->createBase($transaction_info, $job);
				$job->save();
			}
			
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
			return $job;
		}
		catch (\Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			throw $e;
		}
	}

	public function send(\WeArePlanet\Entity\VoidJob $job){
		try {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			\WeArePlanetHelper::instance($this->registry)->dbTransactionLock($job->getSpaceId(), $job->getTransactionId());
			
			$service = new \WeArePlanet\Sdk\Service\TransactionVoidService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
			$operation = $service->voidOnline($job->getSpaceId(), $job->getTransactionId());
			
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
		return $this->handleApiException($hob, $api_exception);
	}
}