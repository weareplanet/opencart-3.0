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
 * Provider of language information from the gateway.
 */
class Language extends AbstractProvider {

	protected function __construct(\Registry $registry){
		parent::__construct($registry, 'oc_weareplanet_languages');
	}

	/**
	 * Returns the language by the given code.
	 *
	 * @param string $code
	 * @return \WeArePlanet\Sdk\Model\RestLanguage
	 */
	public function find($code){
		return parent::find($code);
	}

	/**
	 * Returns the primary language in the given group.
	 *
	 * @param string $code
	 * @return \WeArePlanet\Sdk\Model\RestLanguage
	 */
	public function findPrimary($code){
		$code = substr($code, 0, 2);
		foreach ($this->getAll() as $language) {
			if ($language->getIso2Code() == $code && $language->getPrimaryOfGroup()) {
				return $language;
			}
		}
		
		return false;
	}

	public function findByIsoCode($iso){
		foreach ($this->getAll() as $language) {
			if ($language->getIso2Code() == $iso || $language->getIso3Code() == $iso) {
				return $language;
			}
		}
		return false;
	}

	public function findForStore($code, $locale = ""){
		$code = strtolower(substr($code, 0, 2)); // code may be de, deu, or de-DE. In case of three-letter may cause issues
		$possibleIetfs = array();
		if ($locale) { // locale may contain ietf codes. Or it may contain garbage
			$locales = explode(',', $locale);
			foreach ($locales as $possibleIetf) {
				if (strlen($possibleIetf) === 5) {
					$possibleIetf = strtolower(substr($possibleIetf, 0, 2)) . "-" . strtoupper(substr($possibleIetf, 3)); // change de_DE and de.de to de-DE
					if (!isset($possibleIetfs[$possibleIetf])) {
						$possibleIetfs[$possibleIetf] = true;
					}
				}
			}
		}
		$usePrimary = empty($possibleIetfs);
		$fallback = null;
		foreach ($this->getAll() as $language) {
			if ($language->getIso2Code() == $code) {
				if ($language->getPrimaryOfGroup()) {
					if ($usePrimary) {
						return $language;
					}
					$fallback = $language;
				}
				if (isset($possibleIetfs[$language->getIetfCode()])) {
					return $language;
				}
			}
			else if ($language->getIetfCode() === 'en-US' && empty($fallback)) {
				$fallback = $language;
			}
		}
		return $fallback; // fallback to primary if no ietf match
	}

	/**
	 * Returns a list of language.
	 *
	 * @return \WeArePlanet\Sdk\Model\RestLanguage[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
		$language_service = new \WeArePlanet\Sdk\Service\LanguageService(
				\WeArePlanetHelper::instance($this->registry)->getApiClient());
		return $language_service->all();
	}

	protected function getId($entry){
		/* @var \WeArePlanet\Sdk\Model\RestLanguage $entry */
		return $entry->getIetfCode();
	}
}