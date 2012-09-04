<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once(__DIR__.'/util.php');

$htmlwrap = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN" "http://www.w3.org/TR/REC-html40/strict.dtd"><html><head></head><body>%s</body></html>';
$divwrap = '<div>%s</div>';
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');
OCP\JSON::callCheck();

$id = isset($_POST['id']) ? $_POST['id'] : null;
$cid = isset($_POST['cid']) ? $_POST['cid'] : null;
$property = isset($_POST['type']) ? $_POST['type'] : null;
$value = isset($_POST['value']) ? $_POST['value'] : null;
if(is_null($id)) {
	OCP\JSON::error(array(
		'data'=>array(
			'message' => OCA\Journal\App::$l10n->t('ID is not set!'))
		)
	);
	exit;
}
if(is_null($property)) {
	OCP\JSON::error(array(
		'data'=>array(
			'message' => OCA\Journal\App::$l10n->t('Property name is not set!'))
		)
	);
	exit;
}
if(is_null($value)) {
	OCP\JSON::error(array(
		'data'=>array(
			'message' => OCA\Journal\App::$l10n->t('Property value is not set!'))
		)
	);
	exit;
}

foreach($_POST as $key => $val) {
    debug($key.': '.print_r($val, true));
}

$parameters = isset($_POST['parameters']) ? $_POST['parameters'] : null;

if($id == 'new') {
	debug('Creating new entry.');
	$vcalendar = OCA\Journal\App::createVCalendar();
	$vjournal = OCA\Journal\App::createVJournal();
	$vcalendar->add($vjournal);
} else {
	$vcalendar = OC_Calendar_App::getVCalendar($id);
	$vjournal = $vcalendar->VJOURNAL;
}

debug('saveproperty: ' . $property . ': ' . print_r($value, true));

switch($property) {
	case 'DESCRIPTION':
		$hasgenericformat = false;
		$haskdeformat = false;
		if(!$vjournal->DESCRIPTION) {
			$vjournal->setString('DESCRIPTION', $value);
		}
		if($parameters && isset($parameters['FORMAT']) && strtoupper($parameters['FORMAT']) == 'HTML') {
			if($value[0] != '<') { // Fugly hack coming up
				$value = sprintf($divwrap, $value);
			}
			$vjournal->DESCRIPTION->value = sprintf($htmlwrap, $value);
			foreach($vjournal->DESCRIPTION->parameters as $parameter){
				if(stripos($parameter->name, 'X-KDE-TEXTFORMAT') !== false && stripos($parameter->value, 'HTML') !== false){
					$haskdeformat = true;
				}
				if(stripos($parameter->name, 'X-TEXTFORMAT') !== false && stripos($parameter->value, 'HTML') !== false){
					$hasgenericformat = true;
				}
			}
			if(!$haskdeformat) {
				try {
					$vjournal->DESCRIPTION->add(new Sabre_VObject_Parameter('X-KDE-TEXTFORMAT', 'HTML'));
				} catch (Exception $e) {
					OCP\JSON::error(array('data'=>array('message'=>OC_Journal_App::$l10n->t('Error setting rich text format parameter: '.$e->getMessage()))));
					exit();
				}
			}
			if(!$hasgenericformat) { // Add a more generic text formatting parameter in case other clients would use VJOURNAL this way.
				try {
					$vjournal->DESCRIPTION->add(new Sabre_VObject_Parameter('X-TEXTFORMAT', 'HTML'));
				} catch (Exception $e) {
					OCP\JSON::error(array('data'=>array('message'=>OC_Journal_App::$l10n->t('Error setting rich text format parameter: '.$e->getMessage()))));
					exit();
				}
			}
		} else {
			$vjournal->DESCRIPTION->value = $value;
		}
		break;
	case 'DTSTART':
		try {
			$date_only = isset($_POST['date_only']) && (bool)$_POST['date_only'] == true ? true : false;
			$timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
			$timezone = new DateTimeZone($timezone);
			//$dtstart = new DateTime($value, $timezone);
			$dtstart = new DateTime('@'.$value);
			$dtstart->setTimezone($timezone);
			$type = Sabre_VObject_Property_DateTime::LOCALTZ;
			if ($date_only) {
				$type = Sabre_VObject_Property_DateTime::DATE;
			}
			$vjournal->setDateTime('DTSTART', $dtstart, $type);
		} catch (Exception $e) {
			OCP\JSON::error(array('data'=>array('message'=>OC_Journal_App::$l10n->t('Invalid date/time: '.$e->getMessage()))));
			exit();
		}
		break;
	case 'ORGANIZER':
	case 'SUMMARY':
	case 'CATEGORIES':
		$vobject = $vjournal->getVObject();
		if(isset($vobject[$property])) {
			debug('Adding property: '.$property.': '.$value);
			$vobject[$property]['value'] = $value;
		} else {
			$vjournal->setString($property, $value);
		}
		break;
		$vjournal->setString($property, $value);
		break;
	default:
		OCP\JSON::error(array('data'=>array('message'=>'Unknown type: '.$property)));
		exit();
}

$vjournal->setDateTime('LAST-MODIFIED', 'now', Sabre_VObject_Property_DateTime::UTC);
$vjournal->setDateTime('DTSTAMP', 'now', Sabre_VObject_Property_DateTime::UTC);

if(is_null($cid)) {
	$cid = OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'default_calendar', null);
	// Check that the calendar exists and that it's ours.
	if(OC_Calendar_App::getCalendar($cid, true) == false) {
		OCP\Util::writeLog('journal', 'The default calendar '.$cid.' is either not owned by '.OCP\User::getUser().' or doesn\'t exist.', OCP\Util::WARN);
		$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
		$first_calendar = $calendars[0];
		$cid = $first_calendar['id'];
	}
}

if($id == 'new') {
	try {
		$id = OC_Calendar_Object::add($cid, $vcalendar->serialize());
		debug('Added '.$id.' to '.$cid);
	} catch(Exception $e) {
		OCP\JSON::error(array('message'=>$e->getMessage()));
		exit;
	}
} else {
	try {
		OC_Calendar_Object::edit($id, $vcalendar->serialize());
	} catch(Exception $e) {
		OCP\JSON::error(array('message'=>$e->getMessage()));
		exit;
	}
}
$user_timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$journal_info = OCA\Journal\App::arrayForJSON($id, $cid, $vjournal, $user_timezone);
OCP\JSON::success(array('data' => $journal_info));
