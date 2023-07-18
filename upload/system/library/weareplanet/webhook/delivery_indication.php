<?php

namespace WeArePlanet\Webhook;

/**
 * Webhook processor to handle delivery indication state transitions.
 */
class DeliveryIndication extends AbstractOrderRelated {

	/**
	 *
	 * @see AbstractOrderRelated::load_entity()
	 * @return \WeArePlanet\Sdk\Model\DeliveryIndication
	 */
	protected function loadEntity(Request $request){
		$delivery_indication_service = new \WeArePlanet\Sdk\Service\DeliveryIndicationService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $delivery_indication_service->read($request->getSpaceId(), $request->getEntityId());
	}

	protected function getOrderId($delivery_indication){
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication */
		return $delivery_indication->getTransaction()->getMerchantReference();
	}

	protected function getTransactionId($delivery_indication){
		/* @var $delivery_indication \WeArePlanet\Sdk\Model\DeliveryIndication */
		return $delivery_indication->getLinkedTransaction();
	}

	protected function processOrderRelatedInner(array $order_info, $delivery_indication){
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication */
		switch ($delivery_indication->getState()) {
			case \WeArePlanet\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review($order_info);
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	protected function review(array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], $order_info['order_status_id'],
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_manual'), true);
	}
}