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
 *  @version Release: $Revision: 1.1 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

class Nolifetv extends Module {

	protected $_cachePath = '';
	protected $_cacheData = array();
	protected $_cacheCurrentProgramId = null;
	protected $_cacheAvailableUpcoming = null;

	public function __construct() {
		$this->name = 'nolifetv';
		$this->tab = 'front_office_features';
		$this->version = 1.1;
		$this->author = 'Quadra Informatique';
		$this->module_key = "";
		
		parent::__construct();
		
		$this->displayName = $this->l('Nolife TV : NoAir Webservice');
		$this->description = $this->l('Now on Nolife');

		$this->_cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
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
		if (!$programs = $this->getUpcoming())
			return;
		$smarty->assign('noAirData', $programs);
		return $this->display(__FILE__, 'nolifetv.tpl');
	}

	// Because it's the same function....

	public function hookRightColumn($params) {
		return $this->hookLeftColumn($params);
	}

	public function hookHeader($params) {
		return $this->hookLeftColumn($params);
	}

	public function hookFooter($params) {
		return $this->hookLeftColumn($params);
	}

	// Admin stuff

	public function getContent() {

		$html = '<h2>' . $this->displayName . '</h2>';

		$html .= $this->_checkPrerequisites();

		if (!empty($_POST)) {
			$html .= $this->_postProcess() ?
					'<div class="conf confirm"><img src="../img/admin/ok.gif" alt="ok" /> ' . $this->l('Settings updated') . '</div>' :
					'<div class="alert error">' . $this->l('Settings not updated') . '</div>';
		}
		return $html . $this->_displayForm();
	}

	protected function _checkPrerequisites() {
		if (!is_writable($this->_cachePath))
			return '<div class="alert error">' . $this->l('The module cache directory is not writable, please make it writable') . '</div>';
		return null;
	}

	protected function _postProcess() {
		return (
		Configuration::updateValue('NOAIR_SCREENSHOT_HEIGHT', (int) Tools::getValue('screenshot_height')) AND
		Configuration::updateValue('NOAIR_SCREENSHOT_WIDTH', (int) Tools::getValue('screenshot_width')) AND
		Configuration::updateValue('NOAIR_UPCOMING_NB_SHOW', (int) Tools::getValue('upcoming_nb_show'))
		) ? $this->_updateNoAirCache(true) : false;
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
            <div class="clear"></div>
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
	     <p style="font-size: xx-small;font-style: italic">
		 ' . $this->l('Legal notice') . ' : <a href="http://www.quadra-informatique.fr" alt="Quadra Informatique">Quadra Informatique</a>
	        ' . $this->l('is not affiliated with') . ' <a href="http://www.nolife-tv.com" alt="Nolife">Nolife</a>
	     </p>
	</fieldset>
	</form>';
	}

	// Cache Stuff

	/**
	 * Import Nolife NoAir Data to the local Cache
	 *
	 * @return bool
	 */
	protected function _loadNoAirCache() {

		if ($ret = file_exists($this->_cachePath . 'noair.cfg')) {
			$this->_cacheData = unserialize(file_get_contents($this->_cachePath . 'noair.cfg'));
			$this->_setUpcomingCounters();
		}
		return $ret;
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
	protected function _updateNoAirCache($force=false) {

		// Limit access to nolife webservice to once an hour
		if (!$force && (int) Configuration::get('NOAIR_CACHE_UPDATE') > (time() - 3600))
			return false;

		// DELETES .jpg files
		if ($handle = opendir($this->_cachePath)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && strstr($entry, '.jpg')) {
					unlink($this->_cachePath . $entry);
				}
			}
			closedir($handle);
		}

		// UPDATES the noAir data cache file
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

		// SAVES the cache into local file
		$this->_setUpcomingCounters();
		return $this->_saveNoAirCache();
	}

	// Upcoming Stuff

	/**
	 * Return a formated array with :
	 *  - the current program cacheId, and
	 *  - The number of progams available after this program.
	 *
	 * @return arrau
	 */
	public function _setUpcomingCounters() {

		// reset counters
		$this->_cacheCurrentProgramId = null;
		$this->_cacheAvailableUpcoming = null;

		// OUR TIMESTAMPS ARE BASED ON THE UTC DATE, SO WE NEED TO WORK WITH UTC NOW
		$nowUTC = time() - ((int) date('Z'));

		// get first program position
		foreach ($this->_cacheData as $program) {
			if ($program['timestampUTC'] > $nowUTC) {
				$this->_cacheCurrentProgramId = $program['cacheId'] > 0 ? $program['cacheId'] - 1 : $program['cacheId'];
				break;
			}
		}
		if (!$this->_cacheCurrentProgramId)
			return;

		// get nb programs available after
		foreach ($this->_cacheData as $program)
			if ($program['cacheId'] >= $this->_cacheCurrentProgramId)
				$this->_cacheAvailableUpcoming++;
	}

	/**
	 * Load and check the Noair Cache.
	 * returns a ready to use array for a smarty template file
	 * return false is no progam are available, even after cache refresh
	 *
	 * @return array / bool
	 */
	public function getUpcoming() {

		$nbToShow = 1 + (int) Configuration::get('NOAIR_UPCOMING_NB_SHOW');

		if (!$this->_loadNoAirCache() OR ($this->_cacheAvailableUpcoming < $nbToShow))
			$this->_updateNoAirCache();

		return $this->_cacheCurrentProgramId ? array_slice($this->_cacheData, $this->_cacheCurrentProgramId, $nbToShow) : false;
	}

}
