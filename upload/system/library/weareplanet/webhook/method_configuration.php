<?php
namespace WeArePlanet\Webhook;

/**
 * Webhook processor to handle payment method configuration state transitions.
 */
class MethodConfiguration extends AbstractWebhook {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param Request $request
	 */
	public function process(Request $request){
		$payment_method_configuration_service = \WeArePlanet\Service\MethodConfiguration::instance($this->registry);
		$space_id = $this->registry->get('config')->get('weareplanet_space_id');
		$payment_method_configuration_service->synchronize($space_id);
	}
}