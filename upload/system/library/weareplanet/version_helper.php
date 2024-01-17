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
require_once (DIR_SYSTEM . 'library/weareplanet/autoload.php');

/**
 * Versioning helper which offers implementations depending on opencart version. (Internal) Some version differences may be handled via rewriter.
 *
 * @author Planet Merchant Services Ltd (https://www.weareplanet.com)
 *
 */
class WeArePlanetVersionHelper {
	const TOKEN = 'user_token';

	public static function getModifications(){
		return array(
			'WeArePlanetCore' => array(
				'file' => 'WeArePlanetCore.ocmod.xml',
				'default_status' => 1 
			),
			'WeArePlanetAlerts' => array(
				'file' => 'WeArePlanetAlerts.ocmod.xml',
				'default_status' => 1 
			),
			'WeArePlanetAdministration' => array(
				'file' => 'WeArePlanetAdministration.ocmod.xml',
				'default_status' => 1 
			),
			'WeArePlanetQuickCheckoutCompatibility' => array(
				'file' => 'WeArePlanetQuickCheckoutCompatibility.ocmod.xml',
				'default_status' => 0 
			),
			'WeArePlanetXFeeProCompatibility' => array(
				'file' => 'WeArePlanetXFeeProCompatibility.ocmod.xml',
				'default_status' => 0
			),
			'WeArePlanetPreventConfirmationEmail' => array(
				'file' => 'WeArePlanetPreventConfirmationEmail.ocmod.xml',
				'default_status' => 0 
			),
			'WeArePlanetFrontendPdf' => array(
				'file' => 'WeArePlanetFrontendPdf.ocmod.xml',
				'default_status' => 1 
			) ,
			'WeArePlanetTransactionView' => array(
				'file' => 'WeArePlanetTransactionView.ocmod.xml',
				'default_status' => 1
			)
		);
	}

	public static function wrapJobLabels(\Registry $registry, $content){
		return $content;
	}

	public static function getPersistableSetting($value, $default){
		return $value;
	}

	public static function getTemplate($theme, $template){
		return $template;
	}

	public static function newTax(\Registry $registry){
		return new \Cart\Tax($registry);
	}

	public static function getSessionTotals(\Registry $registry){		// Totals
		$registry->get('load')->model('setting/extension');
		
		$totals = array();
		$taxes = $registry->get('cart')->getTaxes();
		$total = 0;
		
		// Because __call can not keep var references so we put them into an array.
		$total_data = array(
			'totals' => &$totals,
			'taxes' => &$taxes,
			'total' => &$total
		);
		
		$sort_order = array();
		$results = $registry->get('model_setting_extension')->getExtensions('total');
		foreach ($results as $key => $value) {
			$sort_order[$key] = $registry->get('config')->get('total_' . $value['code'] . '_sort_order');
		}
		
		array_multisort($sort_order, SORT_ASC, $results);
		
		foreach ($results as $result) {
			if ($registry->get('config')->get('total_' . $result['code'] . '_status')) {
				$registry->get('load')->model('extension/total/' . $result['code']);
				
				// We have to put the totals in an array so that they pass by reference.
				$registry->get('model_extension_total_' . $result['code'])->getTotal($total_data);
			}
		}
		
		$sort_order = array();
		
		foreach ($totals as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}
		
		array_multisort($sort_order, SORT_ASC, $totals);
		return $total_data['totals'];
	}
	
	public static function persistPluginStatus(\Registry $registry, array $post) {
		$status = array(
			'payment_weareplanet_status' => $post['weareplanet_status']
		);
		$registry->get('model_setting_setting')->editSetting('payment_weareplanet', $status, $post['id']);
	}
	
	public static function extractPaymentSettingCode($code) {
		return 'payment_' . $code;
	}

	public static function extractLanguageDirectory($language){
		return $language['code'];
	}

	public static function createUrl(Url $url_provider, $route, $query, $ssl){
		if ($route === 'extension/payment') {
			$route = 'marketplace/extension';
			// all calls with extension/payment createUrl use array
			$query['type'] = 'payment';
		}
		if (is_array($query)) {
			$query = http_build_query($query);
		}
		else if (!is_string($query)) {
			throw new Exception("Query must be of type string or array, " . get_class($query) . " given.");
		}
		return $url_provider->link($route, $query, $ssl);
	}
}