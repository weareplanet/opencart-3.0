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

namespace WeArePlanet\Entity;

/**
 *
 * @method void setAmount(float $amount)
 * @method float getAmount()
 *
 */
class CompletionJob extends AbstractJob {

	protected static function getFieldDefinition(){
		return array_merge(parent::getFieldDefinition(), [
			'amount' => ResourceType::DECIMAL 
		]);
	}

	protected static function getTableName(){
		return 'weareplanet_completion_job';
	}
}