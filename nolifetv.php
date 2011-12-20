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
		Configuration::updateValue('NOAIR_SCREENSHOT_HEIGHT', 100) AND
		Configuration::updateValue('NOAIR_SCREENSHOT_WIDTH', 200) AND
		$this->registerHook('leftColumn'));
	}

	public function uninstall() {
		return (parent::uninstall() AND
		Configuration::deleteByName('NOAIR_CACHE_UPDATE') AND
		Configuration::deleteByName('NOAIR_SCREENSHOT_HEIGHT') AND
		Configuration::deleteByName('NOAIR_SCREENSHOT_WIDTH')
        );
	}

	public function hookLeftColumn($params) {
		global $smarty;
		$this->_updateNoAirCache();
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

	protected function _loadCacheNoAir() {
		if ($ret = file_exists($this->_cachePath . 'noair.cfg'))
			$this->_cacheNoAir = unserialize(file_get_contents($this->_cachePath . 'noair.cfg'));
		return $ret;
	}

	protected function _saveCacheNoAir() {
		if (!file_exists($this->_cachePath . 'noair.cfg'))
			touch($this->_cachePath . 'noair.cfg');
		if (!$file = fopen($this->_cachePath . 'noair.cfg', 'w') OR
				fwrite($file, serialize($this->_cacheNoAir)) === false OR
				!fclose($file))
			return false;
		return true;
	}

	/**
	 * Recupere les données des programmes et met a jour le xml le cas echeant
	 *
	 * @return nothing
	 */
	protected function _updateNoAirCache() {
		if (!file_exists($this->_cachePath . 'noair.cfg') OR
				(int) Configuration::get('NOAIR_CACHE_UPDATE') < (time() - 86400)) {

            # DELETES .jpg files

            if ($handle = opendir($this->_cachePath)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != ".." && strstr($entry, '.jpg')) {
                        unlink($this->_cachePath . $entry);
                    }
                }
                closedir($handle);
            }

            # UPDATES the noAir cache

			$noAirXml = simplexml_load_file('http://www.nolife-tv.com/noair/noair.xml');
			$i = 0;
			foreach ($noAirXml->xpath('slot') as $xmlSlot) {
				foreach ($xmlSlot->attributes() as $key => $val) {
					switch ($key) {
						case 'dateUTC' :
							$this->_cacheNoAir[$i]['timestampUTC'] = strtotime((string) $val);
                        case 'screenshot': #generating cached screenshot url
                            $this->_cacheNoAir[$i]['img'] = "modules/nolifetv/screenshot.php?id=".$i;
						default:
							$this->_cacheNoAir[$i][$key] = (string) $val;
					}
				}
				$i++;
			}

            # SAVES the cache into local file

            $this->_saveCacheNoAir();
		}
	}

    /**
    * Returns the xml data stored in the cacheNoAir file
    * Populates the cache if the cache is empty
    *
    * @return cacheNoAir (Serialized XML)
    */
    protected function _getNoAirData(){
        if(empty($this->_cacheNoAir)){
            $this->_loadCacheNoAir();
        }
        return $this->_cacheNoAir;
    }

    /**
    * Returns the xml data stored in the cacheNoAir file
    * Displays the current show and the $limit following shows
    *
    * $limit The x elements following the current show 
    * @return sliced cacheNoAir (Serialized XML)
    */
    public function getUpcoming($limit = 3){
        $nowUTC = time() - ((int) date('Z')); # OUR TIMESTAMPS ARE BASED ON THE UTC DATE, SO WE NEED TO WORK WITH UTC NOW

        $data = $this->_getNoAirData();
        $currentId = null;
        $i = 0;
        foreach($data AS $elmt){
            #echo '<br/>test '.$elmt['timestampUTC'];
            if($elmt['timestampUTC'] > $nowUTC){
                $currentId = $i > 0 ? $i-1 : $i;
                break;
            }
            $i++;
        }       
        return array_slice($data, $currentId, $limit + 1);
    }
}
