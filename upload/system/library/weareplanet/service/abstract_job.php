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
 * This service provides functions to deal with jobs, including locking and setting states.
 */
abstract class AbstractJob extends AbstractService {

	/**
	 * Set the state of the given job to failed with the message of the api exception.
	 * Expects a database transaction to be running, and will commit / rollback depending on outcome.
	 * 
	 * @param \WeArePlanet\Entity\AbstractJob $job
	 * @param \WeArePlanet\Sdk\ApiException $api_exception
	 * @throws \Exception
	 * @return \WeArePlanet\Service\AbstractJob
	 */
	protected function handleApiException(\WeArePlanet\Entity\AbstractJob $job, \WeArePlanet\Sdk\ApiException $api_exception){
		try {
			$job->setState(\WeArePlanet\Entity\AbstractJob::STATE_FAILED_CHECK);
			$job->setFailureReason([
				\WeArePlanetHelper::FALLBACK_LANGUAGE => $api_exception->getMessage() 
			]);
			$job->save();
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
			return $job;
		}
		catch (\Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			throw new \Exception($e->getMessage() . ' | ' . $api_exception->getMessage(), $e->getCode(), $api_exception);
		}
	}

	protected function createBase(\WeArePlanet\Entity\TransactionInfo $transaction_info, \WeArePlanet\Entity\AbstractJob $job){
		$job->setTransactionId($transaction_info->getTransactionId());
		$job->setOrderId($transaction_info->getOrderId());
		$job->setSpaceId($transaction_info->getSpaceId());
		$job->setState(\WeArePlanet\Entity\AbstractJob::STATE_CREATED);
		
		return $job;
	}
}