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
 * Webhook processor to handle transaction state transitions.
 */
class Transaction extends AbstractOrderRelated {

	/**
	 *
	 * @see AbstractOrderRelated::load_entity()
	 * @return \WeArePlanet\Sdk\Model\Transaction
	 */
	protected function loadEntity(Request $request){
		$transaction_service = new \WeArePlanet\Sdk\Service\TransactionService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $transaction_service->read($request->getSpaceId(), $request->getEntityId());
	}

	protected function getOrderId($transaction){
		/* @var \WeArePlanet\Sdk\Model\Transaction $transaction */
		return $transaction->getMerchantReference();
	}

	protected function getTransactionId($transaction){
		/* @var \WeArePlanet\Sdk\Model\Transaction $transaction */
		return $transaction->getId();
	}

	protected function processOrderRelatedInner(array $order_info, $transaction){
		/* @var \WeArePlanet\Sdk\Model\Transaction $transaction */
		$transactionInfo = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $order_info['order_id']);

		$finalStates = [
			\WeArePlanet\Sdk\Model\TransactionState::FAILED,
			\WeArePlanet\Sdk\Model\TransactionState::VOIDED,
			\WeArePlanet\Sdk\Model\TransactionState::DECLINE,
			\WeArePlanet\Sdk\Model\TransactionState::FULFILL
		];

		\WeArePlanetHelper::instance($this->registry)->ensurePaymentCode($order_info, $transaction);

		$transactionInfoState = strtoupper($transactionInfo->getState());
		if (!in_array($transactionInfoState, $finalStates)) {
			\WeArePlanet\Service\Transaction::instance($this->registry)->updateTransactionInfo($transaction, $order_info['order_id']);

			switch ($transaction->getState()) {
				case \WeArePlanet\Sdk\Model\TransactionState::CONFIRMED:
					$this->processing($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::DECLINE:
					$this->decline($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::FAILED:
					$this->failed($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::FULFILL:

					if (!in_array($transactionInfoState, ['AUTHORIZED', 'COMPLETED'])) {
						$this->authorize($transaction, $order_info);
					}
					$this->fulfill($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::VOIDED:
					$this->voided($transaction, $order_info);
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::COMPLETED:
					$this->waiting($transaction, $order_info);
					break;
				default:
					// Nothing to do.
					break;
			}
		}
	}

	protected function processing(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_processing_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_processing'));
	}

	protected function confirm(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_processing_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_confirm'));
	}

	protected function authorize(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_authorized_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_authorize'));
	}

	protected function waiting(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_completed_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_waiting'));
	}

	protected function decline(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_decline_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_decline'));
	}

	protected function failed(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_failed_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_failed'));
	}

	protected function fulfill(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_fulfill_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_fulfill'));
	}

	protected function voided(\WeArePlanet\Sdk\Model\Transaction $transaction, array $order_info){
		\WeArePlanetHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], 'weareplanet_voided_status_id',
				\WeArePlanetHelper::instance($this->registry)->getTranslation('message_webhook_voided'));
	}
}