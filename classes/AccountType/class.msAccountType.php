<?php

/**
 * Class msAccountType
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class msAccountType {

	const TYPE_ILIAS = 1;
	const TYPE_SHIBBOLETH = 2;
	/**
	 * @var array
	 */
	public static $domains = array();
	/**
	 * @var
	 */
	protected $matching_string;
	/**
	 * @var
	 */
	protected $subscription_type;
	/**
	 * @var int
	 */
	protected $account_type = self::TYPE_ILIAS;


	/**
	 * @param string $matching_string
	 * @param string $subscription_type
	 */
	public function __construct($matching_string, $subscription_type) {
		$this->setMatchingString($matching_string);
		$this->setSubscriptionType($subscription_type);
		$this->initAccountType();
	}


	/**
	 *
	 */
	public function initAccountType() {
		if ($this->getSubscriptionType() == msSubscription::TYPE_EMAIL AND msConfig::getValueByKey('shibboleth')) {
			self::readDomains();
			foreach (self::$domains as $aai) {
				// (bool)preg_match("/(\\@".$aai.")|(\\@[a-zA-Z0-9]*\\.".$aai.")/uism", $this->getMatchingString()) // Possible Fix fo
				if (strpos($this->getMatchingString(), $aai)) {
					$this->setAccountType(self::TYPE_SHIBBOLETH);

					return;
				}
			}
		}
		$this->setAccountType(self::TYPE_ILIAS);
	}


	/**
	 * @param mixed $matching_string
	 */
	public function setMatchingString($matching_string) {
		$this->matching_string = $matching_string;
	}


	/**
	 * @return mixed
	 */
	public function getMatchingString() {
		return $this->matching_string;
	}


	/**
	 * @param mixed $subscription_type
	 */
	public function setSubscriptionType($subscription_type) {
		$this->subscription_type = $subscription_type;
	}


	/**
	 * @return mixed
	 */
	public function getSubscriptionType() {
		return $this->subscription_type;
	}


	/**
	 * @param int $account_type
	 */
	public function setAccountType($account_type) {
		$this->account_type = $account_type;
	}


	/**
	 * @return int
	 */
	public function getAccountType() {
		return $this->account_type;
	}

	//
	// Helpers
	//

	/**
	 * @return array
	 */
	protected static function readDomains() {
		if (count(self::$domains) == 0) {
			if (msConfig::checkShibboleth()) {
				$xslt = new XSLTProcessor();
				$xslt->importStylesheet(new SimpleXMLElement(file_get_contents('domain_to_idp_entityid.xsl', true)));
				$metadata = new SimpleXMLElement(file_get_contents(msConfig::getValueByKey('metadata_xml')));
				$xml = simplexml_load_string($xslt->transformToXml($metadata));
				$domains = array();
				foreach ($xml->children() as $child) {
					/**
					 * @var SimpleXMLElement $child
					 */
					$domains[] = $child->__toString();
				}
				self::$domains = $domains;
			}
		}
	}
}
