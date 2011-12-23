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
	protected $_cacheData = array();

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
		Configuration::updateValue('NOAIR_SCREENSHOT_HEIGHT', 81) AND
		Configuration::updateValue('NOAIR_SCREENSHOT_WIDTH', 144) AND
		Configuration::updateValue('NOAIR_UPCOMING_NB_SHOW', 3) AND
		$this->registerHook('leftColumn'));
	}

	public function uninstall() {
		return (parent::uninstall() AND
		Configuration::deleteByName('NOAIR_CACHE_UPDATE') AND
		Configuration::deleteByName('NOAIR_SCREENSHOT_HEIGHT') AND
		Configuration::deleteByName('NOAIR_SCREENSHOT_WIDTH') AND
		Configuration::deleteByName('NOAIR_UPCOMING_NB_SHOW')
		);
	}

	public function hookLeftColumn($params) {
		global $smarty;
		$smarty->assign('noAirData', $this->getUpcoming());
		return $this->display(__FILE__, 'nolifetv.tpl');
	}

	# Because it's the same function....

	public function hookRightColumn($params) {
		return $this->hookLeftColumn($params);
	}

	public function hookHeader($params) {
		return $this->hookLeftColumn($params);
	}

	public function hookFooter($params) {
		return $this->hookLeftColumn($params);
	}

	# Admin stuff

	public function getContent() {

		$html = '<h2>' . $this->displayName . '</h2>';

		if (!empty($_POST)) {
			$html .= $this->_postProcess() ?
					'<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>' :
					'<div class="alert error">' . $this->l('Settings not updated') . '</div>';
		}
		return $html . $this->_displayForm();
	}

	protected function _postProcess() {
		return (
		Configuration::updateValue('NOAIR_SCREENSHOT_HEIGHT', (int) Tools::getValue('screenshot_height')) AND
		Configuration::updateValue('NOAIR_SCREENSHOT_WIDTH', (int) Tools::getValue('screenshot_width')) AND
		Configuration::updateValue('NOAIR_UPCOMING_NB_SHOW', (int) Tools::getValue('upcoming_nb_show'))
		);
	}

	protected function _displayForm() {
		global $cookie;
		return '
        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
        <fieldset style="padding:5px 0 15px 5px" ><legend style="margin-left:8px;"><img src="' . $this->_path . 'logo.gif" alt="" title="" />' . $this->l('Settings') . '</legend>
            <label>' . $this->l('Last Noair Cache Update') . '</label>
            <div style="padding: 0.2em 0.5em 1em 210px;">
		  ' . date('l d F Y H:i:s', (int) Configuration::get('NOAIR_CACHE_UPDATE')) . '
            </div>
            <label>' . $this->l('Screenshot Height') . '</label>
            <div class="margin-form">
		  <input type="text" name="screenshot_height" id="screenshot_height" maxlength="3" size="3" value="' . Tools::getValue('screenshot_height', Configuration::get('NOAIR_SCREENSHOT_HEIGHT')) . '" />
            </div>
            <label>' . $this->l('Screenshot Width') . '</label>
            <div class="margin-form">
		  <input type="text" name="screenshot_width" id="screenshot_width" maxlength="3" size="3" value="' . Tools::getValue('screenshot_width', Configuration::get('NOAIR_SCREENSHOT_WIDTH')) . '" />
            </div>
            <label>' . $this->l('Upcoming Nb Show') . '</label>
            <div class="margin-form">
		  <input type="text" name="upcoming_nb_show" id="upcoming_nb_show" maxlength="3" size="3" value="' . Tools::getValue('upcoming_nb_show', Configuration::get('NOAIR_UPCOMING_NB_SHOW')) . '" />
            </div>
            <center><input type="submit" name="submitBlockRss" value="' . $this->l('Save') . '" class="button" /></center>
	</fieldset>
	</form>';
	}

	# Cache Stuff

	/**
	 * Import Nolife NoAir Data to the local Cache
	 *
	 * @return bool
	 */
	protected function _loadNoAirCache() {
		if (!file_exists($this->_cachePath . 'noair.cfg') OR
				(int) Configuration::get('NOAIR_CACHE_UPDATE') < (time() - 3600)) {
			return $this->_updateNoAirCache();
		} else {
			if ($ret = file_exists($this->_cachePath . 'noair.cfg'))
				$this->_cacheData = unserialize(file_get_contents($this->_cachePath . 'noair.cfg'));
			return $ret;
		}
	}

	/**
	 * Import Nolife NoAir Data to the local Cache
	 *
	 * @return bool
	 */
	protected function _saveNoAirCache() {
		if (!file_exists($this->_cachePath . 'noair.cfg'))
			touch($this->_cachePath . 'noair.cfg');
		if (!$file = fopen($this->_cachePath . 'noair.cfg', 'w') OR
				fwrite($file, serialize($this->_cacheData)) === false OR
				!fclose($file))
			return false;
		Configuration::updateValue('NOAIR_CACHE_UPDATE', time());
		return true;
	}

	/**
	 * Import Nolife NoAir Data to the local Cache
	 *
	 * @return bool
	 */
	protected function _updateNoAirCache() {

		# DELETES .jpg files

		if ($handle = opendir($this->_cachePath)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && strstr($entry, '.jpg')) {
					unlink($this->_cachePath . $entry);
				}
			}
			closedir($handle);
		}

		# UPDATES the noAir data cache file

		$noAirXml = simplexml_load_file('http://www.nolife-tv.com/noair/noair.xml');
		$i = 0;
		foreach ($noAirXml->xpath('slot') as $xmlSlot) {
			foreach ($xmlSlot->attributes() as $key => $val) {
				switch ($key) {
					case 'dateUTC' :
						$this->_cacheData[$i]['timestampUTC'] = strtotime((string) $val);
					default:
						$this->_cacheData[$i][$key] = (string) $val;
				}
			}
			$this->_cacheData[$i]['cacheId'] = $i;
			$i++;
		}

		# SAVES the cache into local file
		return $this->_saveNoAirCache();
	}

	/**
	 * Returns the xml data stored in the cacheNoAir file
	 * Displays the current show and the $limit following shows
	 *
	 * $limit The x elements following the current show
	 * @return sliced cacheNoAir (Serialized XML)
	 */
	public function getUpcoming() {

		$this->_loadNoAirCache();

		$nowUTC = time() - ((int) date('Z')); # OUR TIMESTAMPS ARE BASED ON THE UTC DATE, SO WE NEED TO WORK WITH UTC NOW
		$limit = (int) Configuration::get('NOAIR_UPCOMING_NB_SHOW');

		$currentId = null;
		$i = 0;
		foreach ($this->_cacheData AS $program) {
			#echo '<br/>test '.$elmt['timestampUTC'];
			if ($program['timestampUTC'] > $nowUTC) {
				$currentId = $i > 0 ? $i - 1 : $i;
				break;
			}
			$i++;
		}

		// todo : check si plus assez de progs en cache

		return array_slice($this->_cacheData, $currentId, $limit + 1);
	}

}