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

/**
 * This class manages our journals.
 */
class VJournal extends \OC_Calendar_Object {
	/**
	 * @brief Returns all VJOURNAL objects from a calendar
	 * @param integer $id
	 * @return array
	 *
	 * The objects are associative arrays. You'll find the original vObject in
	 * ['calendardata']
	 */
	public static function all($id) {
		$stmt = \OCP\DB::prepare('SELECT * FROM *PREFIX*clndr_objects WHERE calendarid = ? AND objecttype = "VJOURNAL"');
		$result = $stmt->execute(array($id));

		$calendarobjects = array();

		while ( $row = $result->fetchRow()) {
			$calendarobjects[] = $row;
		}

		return $calendarobjects;
	}

	/**
	 * @brief Mass updates an array of entries
	 * @param array $objects  An array of [id, journaldata].
	 */
	public static function updateDataByID($objects) {
		$stmt = \OCP\DB::prepare('UPDATE *PREFIX*clndr_objects SET calendardata = ?, lastmodified = ? WHERE id = ?');

		foreach ($objects as $object) {
			// Get existing event.
			$vevent = \OC_Calendar_App::getVCalendar($object[0]);
			// Get updated VJOURNAL part
			$vjournal = OC_VObject::parse($object[1]);

			if (!is_null($vjournal) && !is_null($vevent)) {
				try {
					$vjournal->setDateTime('LAST-MODIFIED', 'now', Sabre_VObject_Property_DateTime::UTC);
					unset($vevent->VJOURNAL); // Unset old VJOURNAL element
					$vevent->add($vjournal); // and add the updated.
					$data = $vevent->serialize();
					$stmt->execute(array($data, time(), $object[0]));
				} catch(\Exception $e) {
					\OCP\Util::writeLog('journal',
						__METHOD__.', exception: ' . $e->getMessage(),
						OCP\Util::ERROR
					);
					\OCP\Util::writeLog('journal', __METHOD__ . ', id: ' . $object[0], OCP\Util::DEBUG);
				}
			}

		}
	}

	/**
	 * @brief deletes an object
	 * @param integer $id id of object
	 * @return boolean
	 */
	public static function delete($id) {
		$oldobject = self::find($id);
		$calendar = OC_Calendar_Calendar::find($oldobject['calendarid']);
		if ($calendar['userid'] != OCP\User::getUser()) {
			$sharedCalendar = OCP\Share::getItemSharedWithBySource('calendar', $id);
			$sharedJournal = OCP\Share::getItemSharedWithBySource('journal', $id, OCP\Share::FORMAT_NONE, null, true);

			$calendarPermissions = 0;
			$journalPermissions = 0;

			if ($sharedCalendar) {
				$calendarPermissions = $sharedCalendar['permissions'];
			}

			if ($sharedJournal) {
				$journalPermissions = $sharedJournal['permissions'];
			}

			$permissions = max($calendarPermissions, $journalPermissions);

			if (!($permissions & OCP\PERMISSION_DELETE)) {
				throw new Exception(
					OC_Contacts_App::$l10n->t(
						'You do not have the permissions to delete this journal.'
					)
				);
			}

		}

		$stmt = OCP\DB::prepare( 'DELETE FROM `*PREFIX*clndr_objects` WHERE `id` = ?' );
		$stmt->execute(array($id));

		OC_Calendar_Calendar::touchCalendar($oldobject['calendarid']);

		OCP\Share::unshareAll('journal', $id);

		OCP\Util::emitHook('OC_Calendar', 'deleteEvent', $id);

		App::getVCategories()->purgeObject($id);

		return true;
	}

}
