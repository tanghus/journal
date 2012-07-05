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
 * @brief Move Journal entry to another calendar.
 * @param $id Journal entry ID
 * @param $calendarid 
 */

require_once(__DIR__.'/util.php');

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
OCP\JSON::checkAppEnabled('journal');
OCP\JSON::callCheck();

$id = isset($_POST['id'])?strip_tags($_POST['id']):null;
$calendarid = isset($_POST['calendarid'])?strip_tags($_POST['calendarid']):null;
if(is_null($id)) {
	bailOut(OC_Journal_App::$l10n->t('Journal entry ID is not set.'));
}

if(is_null($calendarid)) {
	bailOut(OC_Journal_App::$l10n->t('Calendar ID is not set.'));
}

if(!OC_Calendar_Object::moveToCalendar($id, $calendarid)) {
	bailOut(OC_Journal_App::$l10n->t('Error moving to calendar'));
}

OCP\JSON::success();