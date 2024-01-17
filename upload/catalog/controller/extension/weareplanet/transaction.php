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
use WeArePlanet\Controller\AbstractController;

class ControllerExtensionWeArePlanetTransaction extends AbstractController {

	public function fail(){
		if (isset($this->request->get['order_id']) &&
				 \WeArePlanet\Service\Transaction::instance($this->registry)->waitForStates($this->request->get['order_id'],
						array(
							\WeArePlanet\Sdk\Model\TransactionState::FAILED 
						), 5)) {
			$transaction_info = \WeArePlanet\Entity\TransactionInfo::loadByOrderId($this->registry, $this->request->get['order_id']);
			unset($this->registry->get('session')->data['order_id']);
			$this->session->data['error'] = $transaction_info->getFailureReason();
		}
		else {
			$this->session->data['error'] = $this->language->get('error'); //TODO error text
		}
		$this->response->redirect($this->createUrl('checkout/checkout', ''));
	}

	protected function getRequiredPermission(){
		return '';
	}
}