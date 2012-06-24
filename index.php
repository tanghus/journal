<?php
/**
 * ownCloud - Journal
 *
 * @author Thomas Tanghus
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

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('journal');
OCP\App::checkAppEnabled('contacts');
OCP\App::checkAppEnabled('tal');

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
if( count($calendars) == 0 ) {
	header('Location: ' . OCP\Util::linkTo('calendar', 'index.php'));
	exit;
}
// Load a specific entry?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;

OCP\Util::addScript('3rdparty/timepicker', 'jquery.ui.timepicker');
OCP\Util::addScript('contacts','jquery.multi-autocomplete');
OCP\Util::addScript('','oc-vcategories');
OCP\Util::addScript('journal', 'jquery.rte');
OCP\Util::addScript('journal', 'jquery.textchange');
OCP\Util::addScript('journal', 'journal');
OCP\Util::addscript('tal','modernizr');
OCP\Util::addStyle('3rdparty/timepicker', 'jquery.ui.timepicker');
OCP\Util::addStyle('journal', 'rte');
OCP\Util::addStyle('journal', 'journal');
OCP\App::setActiveNavigationEntry('journal_index');

$categories = OC_Calendar_App::getCategoryOptions();
//$tmpl = new OCP\Template('journal', 'journals', 'user');
$tmpl = new OC_TALTemplate('journal', 'index', 'user');
$tmpl->assign('categories', $categories);
$tmpl->assign('calendars', $calendars);
$tmpl->assign('id',$id);
$tmpl->printPage();
