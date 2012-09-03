<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');

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
$user_timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
session_write_close();
$journals = array();
foreach( $calendars as $calendar ){
	$calendar_journals = OCA\Journal\VJournal::all($calendar['id']);
	foreach( $calendar_journals as $journal ) {
		if(is_null($journal['summary'])) {
			continue;
		}
		$object = OC_VObject::parse($journal['calendardata']);
		$vjournalobj = $object->VJOURNAL;
		try {
			$journals[] = OCA\Journal\App::arrayForJSON($journal['id'], $journal['calendarid'], $vjournalobj, $user_timezone);
		} catch(Exception $e) {
			OCP\Util::writeLog('journal', 'ajax/getentries.php. id: '.$journal['id'].' '.$e->getMessage(), OCP\Util::ERROR);
		}
	}
}

OCP\JSON::success(array(
	'data' => array(
		'entries' => $journals,
		'singlecalendar' => (int)$singlecalendar,
		'cid' => $singlecalendar ? $cid : null)
	)
);
