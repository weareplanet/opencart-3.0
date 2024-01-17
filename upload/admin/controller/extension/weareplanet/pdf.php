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

class ControllerExtensionWeArePlanetPdf extends WeArePlanet\Controller\AbstractPdf {

	public function packingSlip(){
		$this->validate();
		$this->downloadPackingSlip($this->request->get['order_id']);
	}

	public function invoice(){
		$this->validate();
		$this->downloadInvoice($this->request->get['order_id']);
	}

	protected function getRequiredPermission(){
		return 'extension/weareplanet/pdf';
	}
}