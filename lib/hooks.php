<?php
/**
 * ownCloud - Journal
 *
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

/**
 * This class manages hooks for our journal.
 */
class Hooks {
	/**
	 * @brief Hook to convert a completed Task (VTODO) to a journal
	 * 		entry and add it to the calendar.
	 * @param $vtodo An OC_VObject of type VTODO.
	 */
	public static function taskToJournalEntry($vtodo) {
		if (!$vtodo) {
			return;
		}

		\OCP\Util::writeLog('journal', __METHOD__ . ', Completed task: '
			. $vtodo->SUMMARY, \OCP\Util::DEBUG);
		$vcalendar = App::createVCalendar();
		$vjournal = App::createVJournal();
		$vcalendar->add($vjournal);

		if ($vtodo->DTSTART) {
			$vjournal->DTSTART = $vtodo->COMPLETED;
		}

		if ($vtodo->UID) {
			$vjournal->{'RELATED-TO'} = $vtodo->UID;
		}

		$vjournal->SUMMARY = App::$l10n->t('Completed task: ')
			. $vtodo->getAsString('SUMMARY');

		if ($vtodo->DESCRIPTION) {
			$vjournal->DESCRIPTION = $vtodo->DESCRIPTION;
		}

		$cid = \OCP\Config::getUserValue(\OCP\User::getUser()
			, 'journal',
			'default_calendar',
			null
		);

		if (!$cid) {
			$calendars = \OC_Calendar_Calendar::allCalendars(
				\OCP\User::getUser(), true);
			$firstCalendar = reset($calendars);
			$cid = $firstCalendar['id'];
		}

		try {
			\OC_Calendar_Object::add($cid, $vcalendar->serialize());
		} catch (\Exception $e) {
			\OCP\Util::writeLog('journal',
				__METHOD__ . ', Error adding completed Task to calendar: "'
				. $cid . '" ' . $e->getMessage(), \OCP\Util::ERROR);
		}
	}

	/**
	 * @brief Get notifications on deleted calendars.
	 * 		If it matched out default calendar the property is cleared.
	 * @param $aid Integer calendar ID.
	 */
	public static function calendarDeleted($aid) {
		$cid = \OCP\Config::getUserValue(
			\OCP\User::getUser(),
			'journal',
			'default_calendar',
			null
		);

		if ($aid == $cid) {
			\OC_Preferences::deleteKey(OCP\User::getUser(), 'journal', 'default_calendar');
		}
	}
}
