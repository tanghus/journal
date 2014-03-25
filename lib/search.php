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

namespace OCA\Journal;

class SearchProvider extends \OC_Search_Provider {
	public function search($query){
		$calendars = \OC_Calendar_Calendar::allCalendars(\OCP\USER::getUser(), true);
		$results = array();

		if (count($calendars) === 0 || !\OCP\App::isEnabled('calendar')) {
			return $results;
		}

		$userTimezone = \OCP\Config::getUserValue(\OCP\USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());

		$l = new \OC_l10n('journal');
		foreach ($calendars as $calendar) {
			$objects = VJournal::all($calendar['id']);
			foreach ($objects as $object) {
				if (substr_count(strtolower($object['summary']), strtolower($query)) > 0) {
					$calendardata = \OC_VObject::parse($object['calendardata']);
					$vjournal = $calendardata->VJOURNAL;

					if (!isset($vjournal->DTSTART)) {
						continue;
					}

					$dtStart = $vjournal->DTSTART->getDateTime();
					$dtStart->setTimezone(new \DateTimeZone($userTimezone));

					if ($vjournal->DTSTART->getDateType() == \Sabre\VObject\Property\DateTime::DATE) {
						$info = $l->t('Date') . ': ' . $dtStart->format('d.m.Y');
					} else {
						$info = $l->t('Date') . ': ' . $dtStart->format('d.m.y H:i');
					}
					$link = \OCP\Util::linkTo('journal', 'index.php') . '#' . urlencode($object['id']);
					$results[] = new \OC_Search_Result($object['summary'], $info, $link, (string)$l->t('Journal'), null);
				}
			}
		}

		return $results;
	}
}
