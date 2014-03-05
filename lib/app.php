<?php
/**
 * ownCloud - Journal
 *
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Bart Visscher
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

namespace OCA\Journal;

use Sabre\VObject;

/**
 * This class manages our journal.
 */
App::$l10n = new \OC_L10N('journal');

class App {
	public static $l10n;
	/*
	 * @brief categories of the user
	 */
	protected static $categories = null;

	public static function arrayForJSON($id, $calendarid, $vjournal, $user_timezone) {

		$owner = \OC_Calendar_Object::getowner($id);
		$permissions = \OCP\PERMISSION_ALL;

		if($owner !== \OCP\User::getUser()) {
			$sharedJournal = \OCP\Share::getItemSharedWithBySource('journal', $id);
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource('calendar', $calendarid);
			$calendar_permissions = 0;
			$journal_permissions = 0;
			if ($sharedCalendar) {
				$calendar_permissions = $sharedCalendar['permissions'];
			}
			if ($sharedJournal) {
				$journal_permissions = $sharedJournal['permissions'];
			}
			$permissions = max($calendar_permissions, $journal_permissions);
		}
		$journal = array(
			'id' => $id,
			'calendarid' => $calendarid,
			'permissions' => $permissions,
			'owner' => $owner,
			'summary' =>  strtr(
				(string)$vjournal->SUMMARY,
				array('\,' => ',', '\;' => ';', '\\\\' => '\\')
				),
		);
		//$journal['summary'] = $vjournal->getAsString('SUMMARY');
		$format = 'text';

		if(isset($vjournal->DESCRIPTION)) {
			foreach($vjournal->DESCRIPTION->parameters as $parameter){
				if(stripos($parameter->name, 'FORMAT') !== false
						&& stripos($parameter->value, 'HTML') !== false) {
					$format = 'html'; // an educated guess ;-)
					break;
				}
			}
			$desc = strtr(
				(string)$vjournal->DESCRIPTION,
				array('\,' => ',', '\;' => ';', '\\\\' => '\\')
			);
			// Do a double check for format
			if(stripos($desc, '<!DOCTYPE') !== false || stripos($desc, '<html') !== false) {
				$format = 'html';
			}
			$journal['description'] = array(
									'value' => ($format=='html'
											? $body = preg_replace("/.*<body[^>]*>|<\/body>.*/si", "", $desc)
											: $desc),
									'format' => $format,
									'parameters' => self::parametersForProperty($vjournal->DESCRIPTION)
									);
		} else {
			$journal['description'] = array('value' => '', 'format' => 'text');
		}

		if(isset($vjournal->ORGANIZER)) {
			$organizer = (string)$vjournal->ORGANIZER;
			if(strpos($organizer, ':') !== false) {
				list(,$organizer) = explode(':', $organizer);
			}
			$journal['organizer'] = $organizer;
		} else {
			$journal['organizer'] = '';
		}
		$journal['categories'] = isset($vjournal->CATEGORIES)
			? $vjournal->CATEGORIES->getParts()
			: array();
		if(isset($vjournal->DTSTART)) {
			$dtstart = $vjournal->DTSTART->getDateTime();
			if($dtstart) {
				$tz = new \DateTimeZone($user_timezone);
				if($tz->getName() != $dtstart->getTimezone()->getName()
					&& !$vjournal->DTSTART->offsetExists('TZID')) {
					$dtstart->setTimezone($tz);
				}
				$journal['dtstart'] = $dtstart->format('U');
				$journal['only_date'] = ($vjournal->DTSTART->getDateType()
							== VObject\Property\DateTime::DATE);
			} else {
				\OCP\Util::writeLog('journal',
					'Could not get DTSTART DateTime for ' . $journal['summary'],
					\OCP\Util::ERROR
				);
			}
		} else {
			\OCP\Util::writeLog('journal',
				'Could not get DTSTART for ' . $journal['summary'],
				\OCP\Util::ERROR
			);
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
	public static function createVCalendar() {
		// TODO: Add TIMEZONE object.
		$vobject = VObject\Component::create('VCALENDAR');
		$vcalendar = new \OC_VObject($vobject);
		$appinfo = \OCP\App::getAppInfo('journal');
		$appversion = \OCP\App::getAppVersion('journal');
		$prodid = '-//ownCloud//NONSGML ' . $appinfo['name']
				. ' ' . $appversion . '//EN';
		$vcalendar->add('PRODID', $prodid);
		$vcalendar->add('VERSION', '2.0');

		return $vcalendar;
	}

	/**
	 * Create a stub for a new journal entry.
	 * @return OC_VObject The newly created stub.
	 */
	public static function createVJournal() {
		$vobject = VObject\Component::create('VJOURNAL');
		$vjournal = new \OC_VObject($vobject);
		$vjournal->setDateTime('DTSTART', 'now', VObject\Property\DateTime::LOCALTZ);
		$vjournal->setDateTime('CREATED', 'now', VObject\Property\DateTime::UTC);
		$vjournal->setUID();
		$email = \OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '');
		if($email) {
			$vjournal->setString('ORGANIZER', 'MAILTO:'.$email);
		}
		return $vjournal;
	}

	/**
	 * @brief returns the default categories of ownCloud
	 * @return (array) $categories
	 */
	public static function getDefaultCategories() {
		return array(
			(string)self::$l10n->t('Birthday'),
			(string)self::$l10n->t('Business'),
			(string)self::$l10n->t('Call'),
			(string)self::$l10n->t('Clients'),
			(string)self::$l10n->t('Deliverer'),
			(string)self::$l10n->t('Holidays'),
			(string)self::$l10n->t('Ideas'),
			(string)self::$l10n->t('Journey'),
			(string)self::$l10n->t('Jubilee'),
			(string)self::$l10n->t('Meeting'),
			(string)self::$l10n->t('Other'),
			(string)self::$l10n->t('Personal'),
			(string)self::$l10n->t('Projects'),
			(string)self::$l10n->t('Questions'),
			(string)self::$l10n->t('Work'),
		);
	}

	/**
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	protected static function getVCategories() {
		// Version check
		list($version,) = \OCP\Util::getVersion();

		if (is_null(self::$categories)) {
			if($version < 6) {
				self::$categories = new \OC_VCategories('journal', null, self::getDefaultCategories());
			} else {
				$categories = \OC::$server->getTagManager()->load('journal');
				if($categories->isEmpty('journal')) {
					self::scanCategories();
				}
				self::$categories = \OC::$server->getTagManager()
					->load('journal', self::getDefaultCategories());
			}

		}
		return self::$categories;
	}

	/**
	 * @brief returns the categories of the vcategories object
	 * @return (array) $categories
	 */
	public static function getCategories(){
		// Version check
		list($version,) = \OCP\Util::getVersion();

		if($version < 6) {
			$categories = self::getVCategories()->categories();
		} else {

			$getNames = function($tag) {
				return $tag['name'];
			};
			$categories = self::getVCategories()->getTags();
			$categories = array_map($getNames, $categories);
		}

		return $categories;
	}

	/**
	 * @brief returns the categories for the user
	 * @return (Array) $categories
	 */
	/*public static function getCategories() {
		$categories = self::getVCategories()->categories();
		if(count($categories) == 0) {
			self::scanCategories();
			$categories = self::$categories->categories();
		}
		return ($categories ? $categories : self::getDefaultCategories());
	}*/

	/**
	 * scan journals for categories.
	 * @param $vevents VJOURNALs to scan. null to check all journals for the current user.
	 * @returns bool
	 */
	public static function scanCategories() {
		$calendars = array();
		$singlecalendar = (bool)\OCP\Config::getUserValue(
			\OCP\User::getUser(), 'journal', 'single_calendar', false
		);
		if($singlecalendar) {
			$cid = \OCP\Config::getUserValue(
				\OCP\User::getUser(), 'journal', 'default_calendar', null);
			$calendar = \OC_Calendar_App::getCalendar($cid, true);
			if(!$calendar) {
				\OCP\Util::writeLog('journal',
					'The default calendar ' . $cid . ' is either not owned by '
					. \OCP\User::getUser() . ' or doesn\'t exist.',
					\OCP\Util::WARN
				);
				return false;
			}
			$calendars[] = $calendar;
		} else {
			$calendars = \OC_Calendar_Calendar::allCalendars(\OCP\User::getUser(), true);
		}
		\OCP\Util::writeLog('journal', __METHOD__ . ', calendars: '
			. count($calendars), \OCP\Util::DEBUG);
		if(count($calendars) > 0) {
			foreach($calendars as $calendar) {
				foreach(VJournal::all($calendar['id']) as $vevent) {
					$vobject = \OC_VObject::parse($vevent['calendardata']);
					try {
						self::getVCategories()->loadFromVObject($vobject->VJOURNAL, true);
					} catch(\Exception $e) {
						\OCP\Util::writeLog('journal',
							__METHOD__.', exception: ' . $e->getMessage(),
							\OCP\Util::ERROR
						);
					}
				}
			}
		}
		return true;
	}
}
