<?php

namespace WeArePlanet\Provider;

/**
 * Provider of label descriptor information from the gateway.
 */
class LabelDescriptor extends AbstractProvider {

	protected function __construct(\Registry $registry){
		parent::__construct($registry, 'oc_weareplanet_label_descriptor');
	}

	/**
	 * Returns the label descriptor by the given code.
	 *
	 * @param int $id
	 * @return \WeArePlanet\Sdk\Model\LabelDescriptor
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of label descriptors.
	 *
	 * @return \WeArePlanet\Sdk\Model\LabelDescriptor[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
		$label_descriptor_service = new \WeArePlanet\Sdk\Service\LabelDescriptionService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $label_descriptor_service->all();
	}

	protected function getId($entry){
		/* @var \WeArePlanet\Sdk\Model\LabelDescriptor $entry */
		return $entry->getId();
	}
}