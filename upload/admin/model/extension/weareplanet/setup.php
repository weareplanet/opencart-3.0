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
require_once modification(DIR_SYSTEM . 'library/weareplanet/helper.php');
use WeArePlanet\Model\AbstractModel;

class ModelExtensionWeArePlanetSetup extends AbstractModel {

	public function install(){
		$this->load->model("extension/weareplanet/migration");
		$this->load->model('extension/weareplanet/modification');
		$this->load->model('extension/weareplanet/dynamic');
		
		$this->model_extension_weareplanet_migration->migrate();
		
		try {
			$this->model_extension_weareplanet_modification->install();
			$this->model_extension_weareplanet_dynamic->install();
		}
		catch (Exception $e) {
		}
		
		$this->addPermissions();
		$this->addEvents();
	}

	public function synchronize($space_id){
		\WeArePlanetHelper::instance($this->registry)->refreshApiClient();
		\WeArePlanetHelper::instance($this->registry)->refreshWebhook();
		\WeArePlanet\Service\MethodConfiguration::instance($this->registry)->synchronize($space_id);
	}

	public function uninstall($purge = true){
		$this->load->model("extension/weareplanet/migration");
		$this->load->model('extension/weareplanet/modification');
		$this->load->model('extension/weareplanet/dynamic');
		
		$this->model_extension_weareplanet_dynamic->uninstall();
		if ($purge) {
			$this->model_extension_weareplanet_migration->purge();
		}
		$this->model_extension_weareplanet_modification->uninstall();
		
		$this->removeEvents();
		$this->removePermissions();
	}

	private function addEvents(){
		$this->getEventModel()->addEvent('weareplanet_create_dynamic_files', 'admin/controller/marketplace/modification/after',
				'extension/weareplanet/event/createMethodConfigurationFiles');
		$this->getEventModel()->addEvent('weareplanet_can_save_order', 'catalog/model/checkout/order/editOrder/before',
				'extension/weareplanet/event/canSaveOrder');
		$this->getEventModel()->addEvent('weareplanet_update_items_after_edit', 'catalog/controller/api/order/edit/after', 'extension/weareplanet/event/update');
		$this->getEventModel()->addEvent('weareplanet_include_scripts', 'catalog/controller/common/header/before',
				'extension/weareplanet/event/includeScripts');
	}

	private function removeEvents(){
		$this->getEventModel()->deleteEventByCode('weareplanet_create_dynamic_files');
		$this->getEventModel()->deleteEventByCode('weareplanet_can_save_order');
		$this->getEventModel()->deleteEventByCode('weareplanet_update_items_after_edit');
		$this->getEventModel()->deleteEventByCode('weareplanet_include_scripts');
	}

	/**
	 * Adds basic permissions.
	 * Permissions per payment method are added while creating the dynamic files.
	 */
	private function addPermissions(){
		$this->load->model("user/user_group");
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/event');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/completion');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/void');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/refund');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/update');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/pdf');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/alert');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/transaction');
	}

	private function removePermissions(){
		$this->load->model("user/user_group");
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/event');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/completion');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/void');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/refund');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/update');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/pdf');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/weareplanet/alert');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/weareplanet/transaction');
	}
}