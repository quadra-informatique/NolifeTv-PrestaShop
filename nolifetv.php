<?php

/*
 * 1997-2012 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author Quadra Informatique <ecommerce@quadra-informatique.fr>
 *  @copyright 1997-2012 Quadra Informatique
 *  @version Release: $Revision: 1.0 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

class Nolifetv extends Module {

	protected $_cachePath;
	protected $_cacheCfg = array();

	public function __construct() {
		$this->name = 'nolifetv';
		$this->tab = 'front_office_features';
		$this->version = 1.0;
		$this->author = 'Quadra Informatique';
		parent::__construct();
		$this->displayName = $this->l('Nolife TV');
		$this->description = $this->l('View last Nolife TV Shows');

		$this->_cachePath = dirname(__FILE__) . DS . 'cache' . DS;
	}

	public function install() {
		return ( parent::install() AND
		Configuration::updateValue('NOAIR_CACHE_UPDATE', 1) AND
		$this->registerHook('leftColumn'));
	}

	public function uninstall() {
		return (parent::uninstall() AND
		Configuration::deleteByName('NOAIR_CACHE_UPDATE'));
	}

	public function hookLeftColumn($params) {
		global $smarty;
		$this->_updateNoAirCache();
		$smarty->assign('noAirData', $this->_getNoAirData());
		return $this->display(__FILE__, 'nolifetv.tpl');
	}

	public function hookRightColumn($params) {
		return $this->hookLeftColumn($params);
	}

	public function hookHeader($params) {
		return $this->hookLeftColumn($params);
	}

	public function hookFooter($params) {
		return $this->hookLeftColumn($params);
	}

	protected function _loadCacheConfig() {
		if ($ret = file_exists($this->_cachePath . 'noair.cfg'))
			$this->_cacheConfig = unserialize(file_get_contents($this->_cachePath . 'noair.cfg'));
		return $ret;
	}

	protected function _saveCacheConfig() {
		if (!file_exists($this->_cachePath . 'noair.cfg'))
			touch($this->_cachePath . 'noair.cfg');
		if (!$file = fopen($this->_cachePath . 'noair.cfg', 'w') OR
				fwrite($file, serialize($this->_cacheConfig)) === false OR
				!fclose($file))
			return false;
		return true;
	}

	/**
	 * Recupere les données des programmes et met a jour le xml le cas echeant
	 *
	 * @return xml object
	 */
	protected function _updateNoAirCache() {



		if (!file_exists($this->_cachePath . 'noair.cfg') OR
				(int) Configuration::getValue('NOAIR_CACHE_UPDATE') < (time() - 86400)) {

			$noAirXml = simplexml_load_file('http://www.nolife-tv.com/noair/noair.xml');
			$i = 0;
			foreach ($noAirXml->xpath('slot') as $xmlSlot) {
				foreach ($xmlSlot->attributes() as $key => $val) {
					switch ($key) {
						case 'dateUTC' :
							$this->_cacheConfig[$i]['timestampUTC'] = strtotime((string) $val);
						default:
							$this->_cacheConfig[$i][$key] = (string) $val;
					}
				}
				//	if (!$this->_downloadScreenshot($i))
				//		$this->_cacheConfig[$indexNb]['screenshot'] = $this->_path . '..' . DS . 'none.jpg';

				$i++;
			}
		}
		//print_r($noAirXml);
		print_r($this->_cacheConfig);

		die();


//return $racine;
	}



	/**
	 * Retourne les infos du programme en cours et des deux suivants
	 *
	 * @return array
	 */
	function getAttribute($xml, $xpath) {
		$recherche = $xml->xpath($xpath);
		$currentDate = date("Y/m/d H:i:s.");
		$tab_pgm = array();
		$i = 0;
		foreach ($recherche as $elt) {
			$find = date("Y/m/d H:i:s.", strtotime($elt['date']));
			if ($find > $currentDate) {
// programmes a suivre
				$i++;
				if ($i < 3) {
					$tab_pgm[$i]['title'] = $elt['title'];
					if ($elt['screenshot'] != "")
						$tab_pgm[$i]['img'] = $elt['screenshot'];
					else
						$tab_pgm[$i]['img'] = "modules/nolifetv/images/default.jpg";
					$format_date = date("\L\e d/m/Y \à H:i", strtotime($elt['date']));
					$tab_pgm[$i]['type'] = $elt['type'];
					$tab_pgm[$i]['begin'] = $format_date;
				}
			} else {
// programme en cours
				$tab_pgm[$i]['title'] = $elt['title'];
				if ($elt['screenshot'] != "")
					$tab_pgm[$i]['img'] = $elt['screenshot'];
				else
					$tab_pgm[$i]['img'] = "modules/nolifetv/images/default.jpg";
				$format_date = date("\L\e d/m/Y \à H:i", strtotime($elt['date']));
				$tab_pgm[$i]['type'] = $elt['type'];
				$tab_pgm[$i]['begin'] = $format_date;
			}
		}
		return $tab_pgm;
	}

	/**
	 * Verifie si le xml des programmes est a jour
	 *
	 * @return bool
	 */
	function getAvailable($xml, $xpath) {
		$recherche = $xml->xpath($xpath);
		$currentDate = date("Y/m/d H:i:s.");
		$isUpdate = false;
		foreach ($recherche as $elt) {
			$find = date("Y/m/d H:i:s.", strtotime($elt['date']));
			if ($find > $currentDate) {
				$isUpdate = true;
			}
		}
		return $isUpdate;
	}

}