<?php
/**
 * ownCloud - Journal
 *
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @brief Set user preference.
 * @param $key
 * @param $value
 */

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');
OCP\JSON::callCheck();

$key = isset($_POST['key'])?$_POST['key']:null;
$value = isset($_POST['value'])?$_POST['value']:null;
if(is_null($key)) {
	OCP\JSON::error(array('data' => array('message' => OC_Journal_App::$l10n->t('Key is not set for: '.$value))));
	OCP\Util::writeLog('journal', __FILE__.', Key is not set for: '.$value, OCP\Util::ERROR);
	exit;
}

if(is_null($value)) {
	OCP\JSON::error(array('data' => array('message' => OC_Journal_App::$l10n->t('Value is not set for: '.$key))));
	OCP\Util::writeLog('journal', __FILE__.', Value is not set for: '.$key, OCP\Util::ERROR);
	exit;
}

if(OCP\Config::setUserValue(OCP\USER::getUser(), 'journal', $key, $value)) {
	OCP\JSON::success(array('data' => array('key' => $key, 'value' => $value)));
} else {
	OCP\JSON::error(array('data' => array('key' => $key, 'value' => $value, 'message' => OC_Journal_App::$l10n->t('Could not set preference: '.$key.':'.$value))));
	OCP\Util::writeLog('journal', __FILE__.', Could not set preference: '.$key.':'.$value, OCP\Util::ERROR);
}
