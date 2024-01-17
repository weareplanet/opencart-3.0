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

class ControllerExtensionWeArePlanetError extends AbstractController {

	public function index(){
		if (isset($this->request->get['error_code'])) {
			$error_code = $this->request->get['error_code'];
		}
		else {
			$error_code = 'error_default';
		}
		
		$data = array();
		
		$this->language->load('extension/payment/weareplanet');
		
		$data['text_message'] = $this->language->get($error_code);
		$data['heading_title'] = $this->language->get('heading_error');
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->loadView("extension/weareplanet/error", $data));
	}

	protected function getRequiredPermission(){
		return '';
	}
}