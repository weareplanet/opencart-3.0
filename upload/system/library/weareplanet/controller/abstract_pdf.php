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

namespace WeArePlanet\Controller;

abstract class AbstractPdf extends AbstractController {

	protected function downloadPackingSlip($order_id){
		$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $order_id);
		if ($transaction_info->getId() != null && $transaction_info->getState() == \WeArePlanet\Sdk\Model\TransactionState::FULFILL) {
			$service = new \WeArePlanet\Sdk\Service\TransactionService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
			$document = $service->getPackingSlip($transaction_info->getSpaceId(), $transaction_info->getTransactionId());
			$this->download($document);
		}
	}

	protected function downloadInvoice($order_id){
		$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $order_id);
		if ($transaction_info->getId() != null && in_array($transaction_info->getState(),
				array(
					\WeArePlanet\Sdk\Model\TransactionState::COMPLETED,
					\WeArePlanet\Sdk\Model\TransactionState::FULFILL,
					\WeArePlanet\Sdk\Model\TransactionState::DECLINE 
				))) {
					$service = new \WeArePlanet\Sdk\Service\TransactionService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
			$document = $service->getInvoiceDocument($transaction_info->getSpaceId(), $transaction_info->getTransactionId());
			$this->download($document);
		}
	}

	/**
	 * Sends the data received by calling the given path to the browser and ends the execution of the script
	 *
	 * @param string $path
	 */
	private function download(\WeArePlanet\Sdk\Model\RenderedDocument $document){
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $document->getTitle() . '.pdf"');
		header('Content-Description: ' . $document->getTitle());
		echo base64_decode($document->getData());
		exit();
	}
}