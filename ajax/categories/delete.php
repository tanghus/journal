<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::checkAppEnabled('journal');
OCP\JSON::callCheck();

foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.print_r($element, true));
}

require_once('../util.php');

$categories = isset($_POST['categories'])?$_POST['categories']:null;

if(is_null($categories)) {
	bailOut(OC_Contacts_App::$l10n->t('No categories selected for deletion.'));
}

debug(print_r($categories, true));

$calendars = array();
$singlecalendar = (bool)OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'single_calendar', false);
if($singlecalendar) {
	$cid = OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'default_calendar', null);
	$calendar = OC_Calendar_App::getCalendar($cid, true);
	if(!$calendar) {
		OCP\Util::writeLog('journal', 'The default calendar '.$cid.' is either not owned by '.OCP\User::getUser().' or doesn\'t exist.', OCP\Util::WARN);
		OCP\JSON::error(array('data' => array('message' => (string)OC_Journal_App::$l10->t('Couldn\'t access calendar with ID: '.$cid))));
		exit;
	}
	$calendars[] = $calendar;
} else {
	$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
}

if(count($calendars) == 0) {
	bailOut(OC_Contacts_App::$l10n->t('No calendars found.'));
}
$journals = array();
foreach($calendars as $calendar) {
	$journals = array_merge($journals, OC_Journal_VJournal::all($calendar['id']));
} 
$contacts = OC_Journal_VJournal::all($addressbookids);
if(count($contacts) == 0) {
	bailOut(OC_Contacts_App::$l10n->t('No contacts found.'));
}

$vjournals = array();
foreach($journals as $journal) {
	$vjournals[] = array($journal['id'], $journal['calendardata']);
} 

debug('Before delete: '.print_r($categories, true));

$catman = new OC_VCategories('journal');
$catman->delete($categories, $vjournals);
debug('After delete: '.print_r($catman->categories(), true));
OC_Journal_VJournal::updateDataByID($vjournals);
OCP\JSON::success(array('data' => array('categories'=>$catman->categories())));

?>
