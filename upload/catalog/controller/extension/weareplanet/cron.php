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

class ControllerExtensionWeArePlanetCron extends Controller {

	public function index(){
		$this->endRequestPrematurely();
		
		if (isset($this->request->get['security_token'])) {
			$security_token = $this->request->get['security_token'];
		}
		else {
			\WeArePlanetHelper::instance($this->registry)->log('Cron called without security token.', \WeArePlanetHelper::LOG_ERROR);
			die();
		}
		
		\WeArePlanet\Entity\Cron::cleanUpCronDB($this->registry);
		
		try {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			$result = \WeArePlanet\Entity\Cron::setProcessing($this->registry, $security_token);
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
			if (!$result) {
				die();
			}
		}
		catch (Exception $e) {
			// 1062 is mysql duplicate constraint error. This is expected and doesn't need to be logged.
			if (strpos('1062', $e->getMessage()) === false && strpos('constraint_key', $e->getMessage()) === false) {
				\WeArePlanetHelper::instance($this->registry)->log('Updating cron failed: ' . $e->getMessage(), \WeArePlanetHelper::LOG_ERROR);
			}
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			die();
		}
		
		$errors = $this->runTasks();
		
		try {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
			$result = \WeArePlanet\Entity\Cron::setComplete($this->registry, $security_token, implode('. ', $errors));
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
			if (!$result) {
				\WeArePlanetHelper::instance($this->registry)->log('Could not update finished cron job.', \WeArePlanetHelper::LOG_ERROR);
				die();
			}
		}
		catch (Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			\WeArePlanetHelper::instance($this->registry)->log('Could not update finished cron job: ' . $e->getMessage(), \WeArePlanetHelper::LOG_ERROR);
			die();
		}
		die();
	}

	private function runTasks(){
		$errors = array();
		foreach (\WeArePlanet\Entity\AbstractJob::loadNotSent($this->registry) as $job) {
			try {
				switch (get_class($job)) {
					case \WeArePlanet\Entity\CompletionJob::class:
						$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByTransaction($this->registry, $job->getSpaceId(),
								$job->getTransactionId());
						\WeArePlanet\Service\Transaction::instance($this->registry)->updateLineItemsFromOrder($transaction_info->getOrderId());
						\WeArePlanet\Service\Completion::instance($this->registry)->send($job);
						break;
					case \WeArePlanet\Entity\RefundJob::class:
						\WeArePlanet\Service\Refund::instance($this->registry)->send($job);
						break;
					case \WeArePlanet\Entity\VoidJob::class:
						\WeArePlanet\Service\VoidJob::instance($this->registry)->send($job);
						break;
					default:
						break;
				}
			}
			catch (Exception $e) {
				\WeArePlanetHelper::instance($this->registry)->log('Could not update job: ' . $e->getMessage(), \WeArePlanetHelper::LOG_ERROR);
				$errors[] = $e->getMessage();
			}
		}
		return $errors;
	}

	private function endRequestPrematurely(){
		if(ob_get_length()){
			ob_end_clean();
		}
		// Return request but keep executing
		set_time_limit(0);
		ignore_user_abort(true);
		ob_start();
		if (session_id()) {
			session_write_close();
		}
		header("Content-Encoding: none");
		header("Connection: close");
		header('Content-Type: text/javascript');
		ob_end_flush();
		flush();
		if (is_callable('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
	}
}