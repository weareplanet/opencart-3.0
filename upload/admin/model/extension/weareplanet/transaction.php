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
use WeArePlanet\Entity\TransactionInfo;
use WeArePlanet\Provider\PaymentMethod;

class ModelExtensionWeArePlanetTransaction extends AbstractModel {
	const DATE_FORMAT = 'Y-m-d H:i:s';

	public function loadList(array $filters){
		$transactionInfoList = TransactionInfo::loadByFilters($this->registry, $filters);
		/* @var $transactionInfoList TransactionInfo[] */
		$transactions = array();
		foreach ($transactionInfoList as $transactionInfo) {
			$paymentMethod = PaymentMethod::instance($this->registry)->find($transactionInfo->getPaymentMethodId());
			if ($paymentMethod) {
				$paymentMethodName = WeArePlanetHelper::instance($this->registry)->translate($paymentMethod->getName()) . " (" . $transactionInfo->getPaymentMethodId() . ")";
			}
			else {
				$paymentMethodName = $transactionInfo->getPaymentMethodId();
			}
			$transactions[] = array(
				'id' => $transactionInfo->getId(),
				'order_id' => $transactionInfo->getOrderId(),
				'transaction_id' => $transactionInfo->getTransactionId(),
				'space_id' => $transactionInfo->getSpaceId(),
				'space_view_id' => $transactionInfo->getSpaceViewId(),
				'state' => $transactionInfo->getState(),
				'authorization_amount' => $transactionInfo->getAuthorizationAmount(),
				'created_at' => $transactionInfo->getCreatedAt()->format(self::DATE_FORMAT),
				'updated_at' => $transactionInfo->getUpdatedAt()->format(self::DATE_FORMAT),
				'payment_method' => $paymentMethodName,
				'view' => WeArePlanetVersionHelper::createUrl($this->url, 'sale/order/info',
						array(
							'user_token' => $this->session->data['user_token'],
							'order_id' => $transactionInfo->getOrderId() 
						), true) 
			);
		}
		return $transactions;
	}
	
	public function getOrderStatuses() {
		return array(
			'',
			WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED,
			WeArePlanet\Sdk\Model\TransactionState::COMPLETED,
			WeArePlanet\Sdk\Model\TransactionState::CONFIRMED,
			WeArePlanet\Sdk\Model\TransactionState::CREATE,
			WeArePlanet\Sdk\Model\TransactionState::DECLINE,
			WeArePlanet\Sdk\Model\TransactionState::FULFILL,
			WeArePlanet\Sdk\Model\TransactionState::FAILED,
			WeArePlanet\Sdk\Model\TransactionState::PENDING,
			WeArePlanet\Sdk\Model\TransactionState::PROCESSING,
			WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED,
			WeArePlanet\Sdk\Model\TransactionState::VOIDED,
		);
	}
	
	public function countRows() {
		return TransactionInfo::countRows($this->registry);
	}
}