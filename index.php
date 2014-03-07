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

$errors = array();
$categories = array();
$calendars = array();
$singlecalendar = false;
$id = null;

$l = new OC_L10N('journal');

// Version check
list($version,) = \OCP\Util::getVersion();

$required_apps = array(
	array('id' => 'tal', 'name' => 'TAL Page Templates'),
	array('id' => 'journal', 'name' => 'Journal'),
	array('id' => 'calendar', 'name' => 'Calendar'),
);

foreach ($required_apps as $app) {
	if(!OCP\App::isEnabled($app['id'])) {
		$error = (string)$l->t('The %%s app isn\'t enabled! Please enable it here: <strong><a href="%%s?appid=%%s">Enable %%s app</a></strong>');
		$errors[] = sprintf($error, $app['name'],OC_Helper::linkToRoute('settings_apps'), $app['id'], $app['name']);
	}
}

if (count($errors) === 0) {
	$categories = OCA\Journal\App::getCategories();
	$singlecalendar = (bool)OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'single_calendar', false);
	$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);

	if (count($calendars) === 0) {
		$error = (string)$l->t('You have no calendars. Please add one at the <strong><a href="%%s">Calendar app</a></strong>');
		$errors[] = sprintf($error, OCP\Util::linkTo('calendar', 'index.php'));
	}

	// Load a specific entry?
	$id = isset($_GET['id']) ? $_GET['id'] : null;

	OCP\Util::addScript('3rdparty/timepicker', 'jquery.ui.timepicker');
	OCP\Util::addScript('contacts','jquery.multi-autocomplete');

	if ($version < 6) {
		OCP\Util::addScript('','oc-vcategories');
	} else {
		OCP\Util::addScript('','tags');
	}

	OCP\Util::addScript('journal', '3rdparty/Markdown.Converter');
	OCP\Util::addScript('journal', '3rdparty/Markdown.Sanitizer');
	OCP\Util::addScript('journal', '3rdparty/markdown_dom_parser');
	OCP\Util::addScript('journal', '3rdparty/html2markdown');
	OCP\Util::addScript('journal', 'jquery.rte');
	//OCP\Util::addScript('journal', 'jquery.textchange');
	OCP\Util::addScript('journal', 'journal');
	OCP\Util::addscript('tal','modernizr');
	OCP\Util::addStyle('3rdparty/timepicker', 'jquery.ui.timepicker');
	OCP\Util::addStyle('journal', 'rte');
	OCP\Util::addStyle('journal', 'journal');
	if ($version < 6) {
		OCP\Util::addStyle('journal', 'icons');
	}
	OCP\App::setActiveNavigationEntry('journal_index');
}

//$tmpl = new OCP\Template('journal', 'journals', 'user');
if ($errors) {
	$tmpl = new OCP\Template('journal', 'rtfm', 'user' );
	$tmpl->assign('errors',$errors, false);
} else {
	$tmpl = new OCA\TAL\Template('journal', 'index', 'user');
	$tmpl->assign('categories', $categories);
	$tmpl->assign('calendars', $calendars);
	$tmpl->assign('singlecalendar', $singlecalendar);
	$tmpl->assign('id', $id);
	$tmpl->assign('version', $version);
}

$tmpl->printPage();
