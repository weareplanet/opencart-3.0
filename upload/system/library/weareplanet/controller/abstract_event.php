<?php

namespace WeArePlanet\Controller;

abstract class AbstractEvent extends AbstractController {

	protected function validate(){
		$this->language->load('extension/payment/weareplanet');
		$this->validatePermission();
		// skip valdiating order.
	}
}