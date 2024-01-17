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
 * Webhook processor to handle manual task state transitions.
 */
class ManualTask extends AbstractWebhook {

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @param \WeArePlanet\Webhook\Request $request
	 */
	public function process(Request $request){
		$manual_task_service = \WeArePlanet\service\ManualTask::instance($this->registry);
		$manual_task_service->update();
	}
}