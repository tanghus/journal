<?php
/**
 * Copyright (c) 2012 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Journal;

class Share_Backend implements \OCP\Share_Backend {

	const FORMAT_JOURNAL = 0;

	private static $entry;

	public function isValidSource($itemSource, $uidOwner) {
		self::$entry = \OC_Calendar_Object::find($itemSource);
		if (self::$entry) {
			return true;
		}
		return false;
	}

	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		if(!self::$entry) {
			self::$entry = \OC_Calendar_Object::find($itemSource);
		}
		return self::$entry['summary'];
	}

	public function formatItems($items, $format, $parameters = null) {
		$entries = array();
		if ($format == self::FORMAT_JOURNAL) {
			foreach ($items as $item) {
				$entry = \OC_Calendar_Object::find($item['item_source']);
				$entry['summary'] = $item['item_target'];
				$entry['permissions'] = $item['permissions'];
				$entries[] = $entry;
			}
		}
		return $entries;
	}

}
