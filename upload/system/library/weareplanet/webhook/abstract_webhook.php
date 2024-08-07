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
 * Abstract webhook processor.
 */
abstract class AbstractWebhook {
	private static $instances = array();
	protected $registry;
	
	private function __construct(\Registry $registry){
		$this->registry = $registry;
	}

	/**
	 * @return static
	 */
	public static function instance(\Registry $registry){
		$class = get_called_class();
		if (!isset(self::$instances[$class])) {
			self::$instances[$class] = new $class($registry);
		}
		return self::$instances[$class];
	}

	/**
	 * Processes the received webhook request.
	 *
	 * @param \WeArePlanet\Webhook\Request $request
	 */
	abstract public function process(Request $request);
}