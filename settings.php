<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);

$cid = OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'default_calendar', null);
$single_calendar = OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'single_calendar', false);
OCP\Util::addScript('journal', 'settings');
$tmpl = new OC_TALTemplate('journal', 'settings');
$tmpl->assign('calendars', $calendars);
$tmpl->assign('single_calendar', $single_calendar);
$tmpl->assign('cid', $cid);

return $tmpl->printPage();
