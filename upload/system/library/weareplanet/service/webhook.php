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

use WeArePlanet\Webhook\Entity;

/**
 * This service handles webhooks.
 */
class Webhook extends AbstractService {
	
	/**
	 * The webhook listener API service.
	 *
	 * @var \WeArePlanet\Sdk\Service\WebhookListenerService
	 */
	private $webhook_listener_service;
	
	/**
	 * The webhook url API service.
	 *
	 * @var \WeArePlanet\Sdk\Service\WebhookUrlService
	 */
	private $webhook_url_service;
	private $webhook_entities = array();

	/**
	 * Constructor to register the webhook entites.
	 */
	protected function __construct(\Registry $registry){
		parent::__construct($registry);
		$this->webhook_entities[1487165678181] = new Entity(1487165678181, 'Manual Task',
				array(
					\WeArePlanet\Sdk\Model\ManualTaskState::DONE,
					\WeArePlanet\Sdk\Model\ManualTaskState::EXPIRED,
					\WeArePlanet\Sdk\Model\ManualTaskState::OPEN 
				), 'WeArePlanet\Webhook\ManualTask');
		$this->webhook_entities[1472041857405] = new Entity(1472041857405, 'Payment Method Configuration',
				array(
					\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE,
					\WeArePlanet\Sdk\Model\CreationEntityState::DELETED,
					\WeArePlanet\Sdk\Model\CreationEntityState::DELETING,
					\WeArePlanet\Sdk\Model\CreationEntityState::INACTIVE 
				), 'WeArePlanet\Webhook\MethodConfiguration', true);
		$this->webhook_entities[1472041829003] = new Entity(1472041829003, 'Transaction',
				array(
					\WeArePlanet\Sdk\Model\TransactionState::CONFIRMED,
					\WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED,
					\WeArePlanet\Sdk\Model\TransactionState::DECLINE,
					\WeArePlanet\Sdk\Model\TransactionState::FAILED,
					\WeArePlanet\Sdk\Model\TransactionState::FULFILL,
					\WeArePlanet\Sdk\Model\TransactionState::VOIDED,
					\WeArePlanet\Sdk\Model\TransactionState::COMPLETED,
					\WeArePlanet\Sdk\Model\TransactionState::PROCESSING 
				), 'WeArePlanet\Webhook\Transaction');
		$this->webhook_entities[1472041819799] = new Entity(1472041819799, 'Delivery Indication',
				array(
					\WeArePlanet\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED 
				), 'WeArePlanet\Webhook\DeliveryIndication');
		
		$this->webhook_entities[1472041831364] = new Entity(1472041831364, 'Transaction Completion',
				array(
					\WeArePlanet\Sdk\Model\TransactionCompletionState::FAILED,
					\WeArePlanet\Sdk\Model\TransactionCompletionState::SUCCESSFUL 
				), 'WeArePlanet\Webhook\TransactionCompletion');
		
		$this->webhook_entities[1472041867364] = new Entity(1472041867364, 'Transaction Void',
				array(
					\WeArePlanet\Sdk\Model\TransactionVoidState::FAILED,
					\WeArePlanet\Sdk\Model\TransactionVoidState::SUCCESSFUL 
				), 'WeArePlanet\Webhook\TransactionVoid');
		
		$this->webhook_entities[1472041839405] = new Entity(1472041839405, 'Refund',
				array(
					\WeArePlanet\Sdk\Model\RefundState::FAILED,
					\WeArePlanet\Sdk\Model\RefundState::SUCCESSFUL 
				), 'WeArePlanet\Webhook\TransactionRefund');
		$this->webhook_entities[1472041806455] = new Entity(1472041806455, 'Token',
				array(
					\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE,
					\WeArePlanet\Sdk\Model\CreationEntityState::DELETED,
					\WeArePlanet\Sdk\Model\CreationEntityState::DELETING,
					\WeArePlanet\Sdk\Model\CreationEntityState::INACTIVE 
				), 'WeArePlanet\Webhook\Token');
		$this->webhook_entities[1472041811051] = new Entity(1472041811051, 'Token Version',
				array(
					\WeArePlanet\Sdk\Model\TokenVersionState::ACTIVE,
					\WeArePlanet\Sdk\Model\TokenVersionState::OBSOLETE 
				), 'WeArePlanet\Webhook\TokenVersion');
	}

	/**
	 * Installs the necessary webhooks in WeArePlanet.
	 */
	public function install($space_id, $url){
		if ($space_id !== null && !empty($url)) {
			$webhook_url = $this->getWebhookUrl($space_id, $url);
			if ($webhook_url == null) {
				$webhook_url = $this->createWebhookUrl($space_id, $url);
			}
			$existing_listeners = $this->getWebhookListeners($space_id, $webhook_url);
			foreach ($this->webhook_entities as $webhook_entity) {
				/* @var WC_WeArePlanet_Webhook_Entity $webhook_entity */
				$exists = false;
				foreach ($existing_listeners as $existing_listener) {
					if ($existing_listener->getEntity() == $webhook_entity->getId()) {
						$exists = true;
					}
				}
				if (!$exists) {
					$this->createWebhookListener($webhook_entity, $space_id, $webhook_url);
				}
			}
		}
	}
	
	public function uninstall($space_id, $url) {
		if($space_id !== null && !empty($url)) {
			$webhook_url = $this->getWebhookUrl($space_id, $url);
			if($webhook_url == null) {
				\WeArePlanetHelper::instance($this->registry)->log("Attempted to uninstall webhooks with URL $url, but was not found");
				return;
			}
			foreach($this->getWebhookListeners($space_id, $webhook_url) as $listener) {
				$this->getWebhookListenerService()->delete($space_id, $listener->getId());
			}
			
			$this->getWebhookUrlService()->delete($space_id, $webhook_url->getId());
		}
	}

	/**
	 *
	 * @param int|string $id
	 * @return Entity
	 */
	public function getWebhookEntityForId($id){
		if (isset($this->webhook_entities[$id])) {
			return $this->webhook_entities[$id];
		}
		return null;
	}

	/**
	 * Create a webhook listener.
	 *
	 * @param Entity $entity
	 * @param int $space_id
	 * @param \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url
	 * @return \WeArePlanet\Sdk\Model\WebhookListenerCreate
	 */
	protected function createWebhookListener(Entity $entity, $space_id, \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url){
		$webhook_listener = new \WeArePlanet\Sdk\Model\WebhookListenerCreate();
		$webhook_listener->setEntity($entity->getId());
		$webhook_listener->setEntityStates($entity->getStates());
		$webhook_listener->setName('Opencart ' . $entity->getName());
		$webhook_listener->setState(\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE);
		$webhook_listener->setUrl($webhook_url->getId());
		$webhook_listener->setNotifyEveryChange($entity->isNotifyEveryChange());
		return $this->getWebhookListenerService()->create($space_id, $webhook_listener);
	}

	/**
	 * Returns the existing webhook listeners.
	 *
	 * @param int $space_id
	 * @param \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url
	 * @return \WeArePlanet\Sdk\Model\WebhookListener[]
	 */
	protected function getWebhookListeners($space_id, \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url){
		$query = new \WeArePlanet\Sdk\Model\EntityQuery();
		$filter = new \WeArePlanet\Sdk\Model\EntityQueryFilter();
		$filter->setType(\WeArePlanet\Sdk\Model\EntityQueryFilterType::_AND);
		$filter->setChildren(
				array(
					$this->createEntityFilter('state', \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE),
					$this->createEntityFilter('url.id', $webhook_url->getId()) 
				));
		$query->setFilter($filter);
		return $this->getWebhookListenerService()->search($space_id, $query);
	}

	/**
	 * Creates a webhook url.
	 *
	 * @param int $space_id
	 * @return \WeArePlanet\Sdk\Model\WebhookUrlCreate
	 */
	protected function createWebhookUrl($space_id){
		$webhook_url = new \WeArePlanet\Sdk\Model\WebhookUrlCreate();
		$webhook_url->setUrl($this->getUrl());
		$webhook_url->setState(\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE);
		$webhook_url->setName('Opencart');
		return $this->getWebhookUrlService()->create($space_id, $webhook_url);
	}

	/**
	 * Returns the existing webhook url if there is one.
	 *
	 * @param int $space_id
	 * @return \WeArePlanet\Sdk\Model\WebhookUrl
	 */
	protected function getWebhookUrl($space_id, $url){
		$query = new \WeArePlanet\Sdk\Model\EntityQuery();
		$query->setNumberOfEntities(1);
		$filter = new \WeArePlanet\Sdk\Model\EntityQueryFilter();
		$filter->setType(\WeArePlanet\Sdk\Model\EntityQueryFilterType::_AND);
		$filter->setChildren(
				array(
					$this->createEntityFilter('state', \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE),
					$this->createEntityFilter('url', $url)
				));
		$query->setFilter($filter);
		$result = $this->getWebhookUrlService()->search($space_id, $query);
		if (!empty($result)) {
			return $result[0];
		}
		else {
			return null;
		}
	}

	/**
	 * Returns the webhook endpoint URL.
	 *
	 * @return string
	 */
	protected function getUrl(){
		return \WeArePlanetHelper::instance($this->registry)->getWebhookUrl();
	}

	/**
	 * Returns the webhook listener API service.
	 *
	 * @return \WeArePlanet\Sdk\Service\WebhookListenerService
	 */
	protected function getWebhookListenerService(){
		if ($this->webhook_listener_service == null) {
			$this->webhook_listener_service = new \WeArePlanet\Sdk\Service\WebhookListenerService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		}
		return $this->webhook_listener_service;
	}

	/**
	 * Returns the webhook url API service.
	 *
	 * @return \WeArePlanet\Sdk\Service\WebhookUrlService
	 */
	protected function getWebhookUrlService(){
		if ($this->webhook_url_service == null) {
			$this->webhook_url_service = new \WeArePlanet\Sdk\Service\WebhookUrlService(\WeArePlanetHelper::instance($this->registry)->getApiClient());
		}
		return $this->webhook_url_service;
	}
}