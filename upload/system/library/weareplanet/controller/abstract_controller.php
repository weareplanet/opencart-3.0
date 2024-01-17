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

/**
 * Base controller class offering a validate method and wrappers for functions which may differ between versions (redirect, link etc.)
 *
 * The validate method checks if permissions are set (if in admin)
 * If the order id is set in the request->get[] array,
 * If the order id is part of a weareplanet transaction,
 * If the user (non admin) is the owner of the given order.
 */
abstract class AbstractController extends \Controller {

	protected function loadView($template, $data = array()){
	    $template = \WeArePlanetVersionHelper::getTemplate($this->config->get('config_template'), $template);
	    return $this->load->view($template, $data);
	}

	protected function validate(){
		$this->language->load('extension/payment/weareplanet');
		$this->validatePermission();
		$this->validateOrder();
	}

	protected function validatePermission(){
		if (\WeArePlanetHelper::instance($this->registry)->isAdmin()) {
			if (!$this->user->hasPermission('access', $this->getRequiredPermission())) {
				throw new \Exception($this->language->get('error_permission'));
			}
		}
	}

	protected function displayError($message){
		$variables = $this->getAdminSurroundingTemplates();
		$variables['text_error'] = $message;
		$this->response->setOutput($this->loadView("extension/weareplanet/error", $variables));
	}

	protected function getAdminSurroundingTemplates(){
		return array(
			'header' => $this->load->controller("common/header"),
			'column_left' => $this->load->controller("common/column_left"),
			'footer' => $this->load->controller("common/footer") 
		);
	}

	protected function validateOrder(){
		if (!isset($this->request->get['order_id'])) {
			throw new \Exception($this->language->get('error_order_id'));
		}
		if (!\WeArePlanetHelper::instance($this->registry)->isValidOrder($this->request->get['order_id'])) {
			throw new \Exception($this->language->get('error_not_weareplanet'));
		}
	}

	protected function createUrl($route, $query){
		return \WeArePlanetVersionHelper::createUrl($this->url, $route, $query, $this->config->get('config_secure'));
	}

	protected abstract function getRequiredPermission();
}