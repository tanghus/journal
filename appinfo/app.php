<?php
$l = new OC_L10N('journal');
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'calendar/lib/calendar.php';
OC::$CLASSPATH['OCA\Journal\App'] = 'journal/lib/app.php';
OC::$CLASSPATH['OCA\Journal\Share_Backend'] = 'journal/lib/share.php';
OC::$CLASSPATH['OCA\Journal\VJournal'] = 'journal/lib/vjournal.php';
OC::$CLASSPATH['OCA\\Journal\\SearchProvider'] = 'journal/lib/search.php';
OC::$CLASSPATH['OCA\Journal\Hooks'] = 'journal/lib/hooks.php';

OCP\Util::connectHook(
	'OC_Task',
	'taskCompleted',
	'OCA\Journal\Hooks',
	'taskToJournalEntry'
);
OCP\Util::connectHook(
	'OC_Calendar',
	'deleteCalendar',
	'OCA\Journal\Hooks',
	'calendarDeleted'
);

OCP\App::addNavigationEntry( array(
	'id' => 'journal_index',
	'order' => 11,
	'href' => OCP\Util::linkTo( 'journal', 'index.php' ),
	'icon' => OCP\Util::imagePath( 'journal', 'journal.svg' ),
	'name' => $l->t('Journal')
	)
);

OC_Search::registerProvider('OCA\Journal\SearchProvider');
OCP\Share::registerBackend('journal', 'OCA\Journal\Share_Backend');
