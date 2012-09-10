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


error_reporting(0);

function downloadScreenshot($indexNb, $cachePath) {

	if (!file_exists($cachePath . 'noair.cfg'))
		return false;
	$cacheConfig = unserialize(file_get_contents($cachePath . 'noair.cfg'));
	$urlFrom = $cacheConfig[$indexNb]['screenshot'] ? 'screenshot' : 'AdditionalScreenshot';
	$srcFile = $cacheConfig[$indexNb][$urlFrom];
	$destFile = $cachePath . $indexNb . '.jpg';

	// Load config from Prestashop
	require_once('../../config/config.inc.php');
	$destWidth = (int)Configuration::get('NOAIR_SCREENSHOT_WIDTH');
	$destHeight = (int)Configuration::get('NOAIR_SCREENSHOT_HEIGHT');

	// Delete previous image
	if (file_exists($destFile))
		if (!unlink($destFile))
			return false;

	// Download Image
	if ($srcFile AND
			$data = file_get_contents($srcFile) AND
			touch($destFile) AND
			$handle = fopen($destFile, 'r+') AND
			fwrite($handle, $data) AND
			fclose($handle)) {

		// Resize Image
		list($srcWidth, $srcHeight, $type, $attr) = getimagesize($destFile);
		if ($srcWidth > $destWidth || $srcHeight > $destHeight) {
			if (!$image = imagecreatefromjpeg($destFile))
				if (!$image = imagecreatefrompng($destFile))
					if (!$image = imagecreatefromgif($destFile))
						return false;
			unlink($destFile);
			$resize = imagecreatetruecolor($destWidth, $destHeight);
			imagecopyresampled($resize, $image, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
			if (!imagejpeg($resize, $destFile, 100))
				return false;
		}
		return true;

	// use default image
	}else{
		return copy ($cachePath . '..' . DIRECTORY_SEPARATOR . 'none.jpg',$destFile);
	}



	return true;
}

function SilentDownloadScreenshot($indexNb, $cachePath) {
	ob_start();
	try {
		$ret=downloadScreenshot($indexNb, $cachePath);
	}catch (Exception $e) {
		$ret=false;
	}
	ob_end_flush();
	return $ret;
}

$cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

if (!file_exists($cachePath . (int) $_GET['id'] . '.jpg')) {
	if (!downloadScreenshot((int) $_GET['id'], $cachePath))
		die(); # ARGGGGH
}
Header('Content-type: image/jpeg');
echo file_get_contents($cachePath . (int) $_GET['id'] . '.jpg');
