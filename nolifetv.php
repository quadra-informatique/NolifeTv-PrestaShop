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
		$this->version = '1.1.1';
		$this->author = 'Quadra Informatique';
		$this->module_key = "";
        $this->bootstrap=true;
        
		parent::__construct();
		
		$this->displayName = $this->l('Nolife TV : NoAir Webservice');
		$this->description = $this->l('Now on Nolife');

		$this->_cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
	}

	public function install() {
		return ( parent::install() AND
		Configuration::updateGlobalValue('NOAIR_CACHE_UPDATE', 1) AND
		Configuration::updateGlobalValue('NOAIR_SCREENSHOT_HEIGHT', 90) AND
		Configuration::updateGlobalValue('NOAIR_SCREENSHOT_WIDTH', 160) AND
		Configuration::updateGlobalValue('NOAIR_UPCOMING_NB_SHOW', 3) AND
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
    
    /**
     * Load the configuration form
     */
    public function getContent() {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitNolifeTv')) == true)
            $this->postProcess();

        return $this->checkPrerequisites().$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm() {

        $form = array(
            'legend' => array(
                'title' => $this->displayName,
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Screenshot Height'),
                    'name' => 'screenshot_height',
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Screenshot Width'),
                    'name' => 'screenshot_width',
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Upcoming Nb Show'),
                    'name' => 'upcoming_nb_show',
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitNolifeTv';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value['screenshot_height'] = (int)Configuration::getGlobalValue('NOAIR_SCREENSHOT_HEIGHT');
        $helper->fields_value['screenshot_width'] = (int)Configuration::getGlobalValue('NOAIR_SCREENSHOT_WIDTH');
        $helper->fields_value['upcoming_nb_show'] = (int)Configuration::getGlobalValue('NOAIR_UPCOMING_NB_SHOW');
        return $helper->generateForm(array(array('form' => $form)));
    }

	protected function checkPrerequisites() {
		if (!is_writable($this->_cachePath))
			return '<div class="alert error">' . $this->l('The module cache directory is not writable, please make it writable') . '</div>';
		return null;
	}

	protected function postProcess() {
		return (
		Configuration::updateGlobalValue('NOAIR_SCREENSHOT_HEIGHT', (int) Tools::getValue('screenshot_height')) AND
		Configuration::updateGlobalValue('NOAIR_SCREENSHOT_WIDTH', (int) Tools::getValue('screenshot_width')) AND
		Configuration::updateGlobalValue('NOAIR_UPCOMING_NB_SHOW', (int) Tools::getValue('upcoming_nb_show'))
		) ? $this->_updateNoAirCache(true) : false;
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
		Configuration::updateGlobalValue('NOAIR_CACHE_UPDATE', time());
		return true;
	}

	/**
	 * Import Nolife NoAir Data to the local Cache
	 *
	 * @return bool
	 */
	protected function _updateNoAirCache($force=false) {

		// Limit access to nolife webservice to once an hour
		if (!$force && (int) Configuration::getGlobalValue('NOAIR_CACHE_UPDATE') > (time() - 3600))
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

        if(! $nbToShow = (int) Configuration::getGlobalValue('NOAIR_UPCOMING_NB_SHOW'))
            return false;

		if (!$this->_loadNoAirCache() OR ($this->_cacheAvailableUpcoming < $nbToShow))
			$this->_updateNoAirCache();

		return $this->_cacheCurrentProgramId ? array_slice($this->_cacheData, $this->_cacheCurrentProgramId, $nbToShow) : false;
	}

}
