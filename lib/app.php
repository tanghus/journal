<?php
/**
 * ownCloud - Journal
 *
 * @author Bart Visscher
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
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
 * This class manages our journal.
 */
OC_Journal_App::$l10n = new OC_L10N('journal');
class OC_Journal_App {
	public static $l10n;
	/*
	 * @brief categories of the user
	 */
	protected static $categories = null;
	
	public static function arrayForJSON($id, $calendarid, $vjournal, $user_timezone) {
		// Possible properties: URL
		$journal = array( 'id' => $id, 'calendarid' => $calendarid );
		$journal['summary'] = $vjournal->getAsString('SUMMARY');
		$format = 'text';
		if($vjournal->DESCRIPTION) {
			foreach($vjournal->DESCRIPTION->parameters as $parameter){
				if(stripos($parameter->name, 'FORMAT') !== false && stripos($parameter->value, 'HTML') !== false){
					$format = 'html'; // an educated guess ;-)
					break;
				}
			}
			$desc = $vjournal->getAsString('DESCRIPTION');
			$journal['description'] = array(
									'value' => ($format=='html'?$body = preg_replace("/.*<body[^>]*>|<\/body>.*/si", "", $desc):$desc),
									'format' => $format,
									'parameters' => self::parametersForProperty($vjournal->DESCRIPTION)
									);
		} else {
			$journal['description'] = array('value' => '', 'format' => 'text');
		}
		$journal['organizer'] = array(
									'value' => $vjournal->getAsString('ORGANIZER'),
									'parameters' => self::parametersForProperty($vjournal->ORGANIZER)
									);
		$journal['categories'] = $vjournal->getAsArray('CATEGORIES');
		//error_log('DTSTART: '.print_r($vjournal->DTSTART, true));
		$dtprop = $vjournal->DTSTART;
		if($dtprop) {
			$dtstart = $vjournal->DTSTART->getDateTime();
			if($dtstart) {
				$tz = new DateTimeZone($user_timezone);
				if($tz->getName() != $dtstart->getTimezone()->getName() && !$vjournal->DTSTART->offsetExists('TZID')) {
					$dtstart->setTimezone($tz);
				}
				$journal['dtstart'] = $dtstart->format('U');
				$journal['only_date'] = ($dtprop->getDateType() == Sabre_VObject_Property_DateTime::DATE);
			} else {
				OCP\Util::writeLog('journal', 'Could not get DTSTART DateTime for '.$journal['summary'], OCP\Util::ERROR);
			}
		} else {
			OCP\Util::writeLog('journal', 'Could not get DTSTART for '.$journal['summary'], OCP\Util::ERROR);
		}
		return $journal;
	}

	/** Get a map of a properties parameters for JSON
	 * @param $property Sabre_VObject_Property
	 * @return array of parameters in { name => value, } format
	 */
	public static function parametersForProperty($property) {
		$temp = array();
		if(!$property) {
			return;
		}
		foreach($property->parameters as $parameter){
			$temp[$parameter->name] = $parameter->value;
		}
		return $temp;
	}

	/**
	 * Create a stub for a calendar.
	 * @return OC_VObject The newly created stub.
	 */	 
	public static function createVCalendar()	{
		$vcalendar = new OC_VObject('VCALENDAR');
		$appinfo = OCP\App::getAppInfo('journal');
		$appversion = OCP\App::getAppVersion('journal');
		$prodid = '-//ownCloud//NONSGML '.$appinfo['name'].' '.$appversion.'//EN';
		$vcalendar->add('PRODID', $prodid);
		$vcalendar->add('VERSION', '2.0');

		return $vcalendar;
	}

	/**
	 * Create a stub for a new journal entry.
	 * @return OC_VObject The newly created stub.
	 */	 
	public static function createVJournal()	{
		$vjournal = new OC_VObject('VJOURNAL');
		$vjournal->setDateTime('DTSTART', 'now', Sabre_VObject_Property_DateTime::LOCALTZ);
		$vjournal->setDateTime('CREATED', 'now', Sabre_VObject_Property_DateTime::UTC);
		$vjournal->setUID();
		$email = OCP\Config::getUserValue(OCP\User::getUser(), 'settings', 'email', '');
		if($email) {
			$vjournal->setString('ORGANIZER', 'MAILTO:'.$email);
		}
		return $vjournal;
	}

	/**
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	protected static function getVCategories() {
		if (is_null(self::$categories)) {
			self::$categories = new OC_VCategories('journal', null, OC_Calendar_App::getDefaultCategories());
		}
		return self::$categories;
	}
	
	/**
	 * @brief returns the categories for the user
	 * @return (Array) $categories
	 */
	public static function getCategories() {
		$categories = self::getVCategories()->categories();
		if(count($categories) == 0) {
			self::scanCategories();
			$categories = self::$categories->categories();
		}
		return ($categories ? $categories : self::getDefaultCategories());
	}

	/**
	 * scan journals for categories.
	 * @param $vjournals VJOURNALs to scan. null to check all journals for the current user.
	 */
	public static function scanCategories($vjournals = null) {
		if (is_null($vjournals)) {
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
			if(count($calendars) > 0) {
				$calids = array();
				foreach($calendars as $calendar) {
					$calids[] = $calendar['id'];
				}
				$vjournals = OC_Journal_VJournal::all($calids);
			}
		}
		if(is_array($vjournals) && count($vjournals) > 0) {
			$cards = array();
			foreach($vjournals as $vjournal) {
				$journals[] = $vjournal['calendardata'];
			}

			self::$categories->rescan($journals);
		}
	}

	/**
	 * check VJOURNAL for new categories.
	 * @see OC_VCategories::loadFromVObject
	 */
	public static function loadCategoriesFromVJournal(OC_VObject $journal) {
		self::getVCategories()->loadFromVObject($journal, true);
	}
}
