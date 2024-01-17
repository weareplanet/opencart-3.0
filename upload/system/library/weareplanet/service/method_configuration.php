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

namespace WeArePlanet\Service;

class MethodConfiguration extends AbstractService {

	/**
	 * Updates the data of the payment method configuration.
	 *
	 * @param \WeArePlanet\Sdk\Model\PaymentMethodConfiguration $configuration
	 */
	public function updateData(\WeArePlanet\Sdk\Model\PaymentMethodConfiguration $configuration){
		/* @var \WeArePlanet\Entity\MethodConfiguration $entity */
		$entity = \WeArePlanet\Entity\MethodConfiguration::loadByConfiguration($this->registry, $configuration->getLinkedSpaceId(), $configuration->getId());
		if ($entity->getId() !== null && $this->hasChanged($configuration, $entity)) {
			$entity->setConfigurationName($configuration->getName());
			$entity->setTitle($configuration->getResolvedTitle());
			$entity->setDescription($configuration->getResolvedDescription());
			$entity->setImage($configuration->getResolvedImageUrl());
			$entity->setSortOrder($configuration->getSortOrder());
			$entity->save();
		}
	}

	private function hasChanged(\WeArePlanet\Sdk\Model\PaymentMethodConfiguration $configuration, \WeArePlanet\Entity\MethodConfiguration $entity){
		if ($configuration->getName() != $entity->getConfigurationName()) {
			return true;
		}
		
		if ($configuration->getResolvedTitle() != $entity->getTitle()) {
			return true;
		}
		
		if ($configuration->getResolvedDescription() != $entity->getDescription()) {
			return true;
		}
		
		if ($configuration->getResolvedImageUrl() != $entity->getImage()) {
			return true;
		}
		
		if ($configuration->getSortOrder() != $entity->getSortOrder()) {
			return true;
		}
		
		return false;
	}

	/**
	 * Synchronizes the payment method configurations from WeArePlanet.
	 */
	public function synchronize($space_id){
		$existing_found = array();
		$existing_configurations = \WeArePlanet\Entity\MethodConfiguration::loadBySpaceId($this->registry, $space_id);
		
		$payment_method_configuration_service = new \WeArePlanet\Sdk\Service\PaymentMethodConfigurationService(
				\WeArePlanetHelper::instance($this->registry)->getApiClient());
		$configurations = $payment_method_configuration_service->search($space_id, new \WeArePlanet\Sdk\Model\EntityQuery());
		
		foreach ($configurations as $configuration) {
			$method = \WeArePlanet\Entity\MethodConfiguration::loadByConfiguration($this->registry, $space_id, $configuration->getId());
			if ($method->getId() !== null) {
				$existing_found[] = $method->getId();
			}
			
			$method->setSpaceId($space_id);
			$method->setConfigurationId($configuration->getId());
			$method->setConfigurationName($configuration->getName());
			$method->setState($this->getConfigurationState($configuration));
			$method->setTitle($configuration->getResolvedTitle());
			$method->setDescription($configuration->getResolvedDescription());
			$method->setImage($configuration->getResolvedImageUrl());
			$method->setSortOrder($configuration->getSortOrder());
			$method->save();
		}
		
		foreach ($existing_configurations as $existing_configuration) {
			if (!in_array($existing_configuration->getId(), $existing_found)) {
				$existing_configuration->setState(\WeArePlanet\Entity\MethodConfiguration::STATE_HIDDEN);
				$existing_configuration->save();
			}
		}
		
		\WeArePlanet\Provider\PaymentMethod::instance($this->registry)->clearCache();
	}

	/**
	 * Returns the payment method for the given id.
	 *
	 * @param int $id
	 * @return \WeArePlanet\Sdk\Model\PaymentMethod
	 */
	protected function getPaymentMethod($id){
		return \WeArePlanet\Provider\PaymentMethod::instance($this->registry)->find($id);
	}

	/**
	 * Returns the state for the payment method configuration.
	 *
	 * @param \WeArePlanet\Sdk\Model\PaymentMethodConfiguration $configuration
	 * @return string
	 */
	protected function getConfigurationState(\WeArePlanet\Sdk\Model\PaymentMethodConfiguration $configuration){
		switch ($configuration->getState()) {
			case \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE:
				return \WeArePlanet\Entity\MethodConfiguration::STATE_ACTIVE;
			case \WeArePlanet\Sdk\Model\CreationEntityState::INACTIVE:
				return \WeArePlanet\Entity\MethodConfiguration::STATE_INACTIVE;
			default:
				return \WeArePlanet\Entity\MethodConfiguration::STATE_HIDDEN;
		}
	}
}