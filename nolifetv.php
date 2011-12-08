<?php

/*
 * 1997-2011 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author Quadra Informatique <ecommerce@quadra-informatique.fr>
 *  @copyright 1997-2011 Quadra Informatique
 *  @version Release: $Revision: 1.0 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_'))
    exit;

class Nolifetv extends Module {

    public function __construct() {
        $this->name = 'nolifetv';
        $this->tab = 'Quadra Informatique';
        $this->version = 1.0;
        $this->author = 'Quadra Informatique';
        $this->need_instance = 0;
        parent::__construct();
        $this->displayName = $this->l('Nolife TV');
        $this->description = $this->l('Retrouver les programmes de Nolife TV en un coup d\'oeil');
    }

    public function install() {
        if (parent::install() == false OR !$this->registerHook('leftColumn'))
            return false;
        return true;
    }

    public function uninstall() {
        if (!parent::uninstall())
            Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'nolifetv`');
        parent::uninstall();
    }

    public function hookLeftColumn($params) {
        global $smarty;
        // on recupere le xml des programmes
        $program = $this->getData();

        // on cherche les infos des programmes en cours
        $result = $this->getAttribute($program, "/NoAir/slot");

        // on renseigne le template avec les valeurs du xml
        //
         $smarty->assign('result', $result);

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
    /**
     * Recupere les données des programmes et met a jour le xml le cas echeant
     *
     * @return xml object
     */
    public function getData() {
        $dom = new DomDocument();
        // on va lire le repertoire cache
        $dir = dir("modules/nolifetv/cache/");
        $files_type = "current.xml";

        // on va traiter chaque fichier du repertoire
        while ($filename = $dir->read()) {
            $dispo = FALSE;
            if (($filename != '.') && ($filename != '..') && stristr($filename, $files_type)) {
                // si on trouve un current.xml
                $dispo = TRUE;
            }
        }
        // si on trouve un fichier xml
        if ($dispo) {
            $dom->load('modules/nolifetv/cache/current.xml');
            $currentxml = simplexml_import_dom($dom);

            // on verifie si le xml est a jour
            $result = $this->getAvailable($currentxml, "/NoAir/slot");

            if (!$result)
                $dom->load('http://www.nolife-tv.com/noair/noair.xml');
        } else {
            $dom->load('http://www.nolife-tv.com/noair/noair.xml');
        }
        // on sauvegarde le xml et on renvoie son contenu
        $racine = simplexml_import_dom($dom);
        $racine->asXML('modules/nolifetv/cache/current.xml');
        return $racine;
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
                    if ($elt['screenshot']!="")
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

?>