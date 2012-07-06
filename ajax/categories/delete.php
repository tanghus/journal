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

require_once(__DIR__.'/../util.php');

$categories = isset($_POST['categories'])?$_POST['categories']:null;

if(is_null($categories)) {
	bailOut(OC_Journal_App::$l10n->t('No categories selected for deletion.'));
}

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
	bailOut(OC_Journal_App::$l10n->t('No calendars found.'));
}
$events = array();
foreach($calendars as $calendar) {
	$events = array_merge($events, OC_Journal_VJournal::all($calendar['id']));
}

if(count($events) == 0) {
	bailOut(OC_Journal_App::$l10n->t('No events found.'));
}

$vjournals = array();
foreach($events as $event) {
	try {
		$vobject = OC_VObject::parse($event['calendardata']);
		$vjournals[] = array($event['id'], $vobject->VJOURNAL->serialize());
	} catch(Exception $e) {
	    debug($e->getMessage());
	}
} 

$catman = new OC_VCategories('journal');
$catman->delete($categories, $vjournals);
OC_Journal_VJournal::updateDataByID($vjournals);
OCP\JSON::success(array('data' => array('categories'=>$catman->categories())));

?>
