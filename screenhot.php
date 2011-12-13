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

function downloadScreenshot($indexNb, $cachePath) {

	if (!file_exists($cachePath . 'noair.cfg'))
		return false:
	$cacheConfig = unserialize(file_get_contents($cachePath . 'noair.cfg'));
	$urlFrom = $cacheConfig[$indexNb]['screenshot'] ? 'screenshot' : 'AdditionalScreenshot';
	$srcFile = $cacheConfig[$indexNb][$urlFrom];
	$destFile = $cachePath . $indexNb . '.jpg';
	$destWidth = 200;
	$destHeight = 100;

	// Delete previous image
	if (file_exists($destFile))
		if (!unlink($destFile))
			return false;

	// Download Image
	if (!$srcFile OR
			!$data = file_get_contents($srcFile) OR
			!touch($destFile) OR
			!$handle = fopen($destFile, 'r+') OR
			!fwrite($handle, $data) OR
			!fclose($handle))
		return false;

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
}

Header('Content-type: image/jpeg');

$cachePath = dirname(__FILE__) . DS . 'cache' . DS;

if (!file_exists($cachePath . (int) $_GET['id'] . '.jpg')) {
	if (!downloadScreenshot((int) $_GET['id'], $cachePath)) {
		echofile_get_contents($cachePath . '..' . DS . 'none.jpg');
		die();
	}
}
echo file_get_contents($cachePath . (int) $_GET['id'] . '.jpg');