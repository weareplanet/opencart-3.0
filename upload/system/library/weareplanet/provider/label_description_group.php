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

namespace WeArePlanet\Provider;

/**
 * Provider of label descriptor group information from the gateway.
 */
class LabelDescriptionGroup extends AbstractProvider {

	protected function __construct(\Registry $registry){
		parent::__construct($registry, 'oc_weareplanet_label_descriptor_group');
	}

	/**
	 * Returns the label descriptor group by the given code.
	 *
	 * @param int $id
	 * @return \WeArePlanet\Sdk\Model\LabelDescriptorGroup
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of label descriptor groups.
	 *
	 * @return \WeArePlanet\Sdk\Model\LabelDescriptorGroup[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
		$label_descriptor_group_service = new \WeArePlanet\Sdk\Service\LabelDescriptionGroupService(
				\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $label_descriptor_group_service->all();
	}

	protected function getId($entry){
		/* @var \WeArePlanet\Sdk\Model\LabelDescriptorGroup $entry */
		return $entry->getId();
	}
}