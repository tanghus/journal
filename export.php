<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('calendar');
$calid = isset($_GET['calid']) ? $_GET['calid'] : null;
$journal = isset($_GET['id']) ? $_GET['id'] : null;
$nl = "\n";

if($calid){
	$calendar = OC_Calendar_App::getCalendar($cal, true);
	if(!$calendar){
		header('HTTP/1.0 404 Not Found');
		exit;
	}
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '_', $calendar['displayname']) . '.ics');
	$vjournals = OC_Journal_VJournal::all($calid);
	if($vjournals) {
		foreach($vjournals as $vjournal) {
			echo $vjournal['calendardata'] . $nl;
		}
	}
}elseif($journal){
	$data = OC_Calendar_App::getEventObject($journal, true);
	if(!$data){
		header('HTTP/1.0 404 Not Found');
		exit;
	}
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '-', $data['summary']) . '.ics');
	echo $data['calendardata'];
}