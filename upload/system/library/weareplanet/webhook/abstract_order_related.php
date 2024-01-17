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
 * Abstract webhook processor for order related entities.
 */
abstract class AbstractOrderRelated extends AbstractWebhook {

	/**
	 * Processes the received order related webhook request.
	 *
	 * @param Request $request
	 */
	public function process(Request $request){
		if ($request->getSpaceId() != $this->registry->get('config')->get('weareplanet_space_id')) {
			throw new \Exception("Received webhook with space id {$request->getSpaceId()} in store for space id  {$this->registry->get('config')->get('weareplanet_space_id')}.");
		}
		
		$entity = $this->loadEntity($request);
		\WeArePlanetHelper::instance($this->registry)->dbTransactionStart();
		try {
			$order_id = $this->getOrderId($entity);
			$this->registry->get('load')->model('checkout/order');
			$order_info = $this->registry->get('model_checkout_order')->getOrder($order_id);
			if ($order_info) {
				$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $order_id);
				if ($transaction_info->getTransactionId() !== $this->getTransactionId($entity)) {
					\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
					return;
				}
				\WeArePlanetHelper::instance($this->registry)->dbTransactionLock($transaction_info->getSpaceId(), $transaction_info->getTransactionId());
				$this->processOrderRelatedInner($order_info, $entity);
			}
			
			\WeArePlanetHelper::instance($this->registry)->dbTransactionCommit();
		}
		catch (\Exception $e) {
			\WeArePlanetHelper::instance($this->registry)->dbTransactionRollback();
			throw $e;
		}
	}

	/**
	 * Loads and returns the entity for the webhook request.
	 *
	 * @param Request $request
	 * @return object
	 */
	abstract protected function loadEntity(Request $request);

	/**
	 * Returns the order id linked to the entity.
	 *
	 * @param object $entity
	 * @return string
	 */
	abstract protected function getOrderId($entity);

	/**
	 * Returns the transaction id linked to the entity
	 *
	 *
	 * @param object $entity
	 * @return int
	 */
	abstract protected function getTransactionId($entity);

	/**
	 * Actually processes the order related webhook request.
	 *
	 * This must be implemented
	 *
	 * @param array $order_info
	 * @param object $entity
	 */
	abstract protected function processOrderRelatedInner(array $order_info, $entity);
}