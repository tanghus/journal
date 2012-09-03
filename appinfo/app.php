<?php
$l = new OC_L10N('journal');
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'calendar/lib/calendar.php';
OC::$CLASSPATH['OCA\Journal\App'] = 'journal/lib/app.php';
OC::$CLASSPATH['OCA\Journal\Share_Backend'] = 'journal/lib/share.php';
OC::$CLASSPATH['OCA\Journal\VJournal'] = 'journal/lib/vjournal.php';
OC::$CLASSPATH['OC_Search_Provider_Journal'] = 'journal/lib/search.php';
OC::$CLASSPATH['OCA\Journal\Hooks'] = 'journal/lib/hooks.php';

OCP\Util::connectHook(
	'OC_Task',
	'taskCompleted',
	'OC_Journal_Hooks',
	'taskToJournalEntry'
);
OCP\Util::connectHook(
	'OC_Calendar',
	'deleteCalendar',
	'OC_Journal_Hooks',
	'calendarDeleted'
);

OCP\App::addNavigationEntry( array(
	'id' => 'journal_index',
	'order' => 11,
	'href' => OCP\Util::linkTo( 'journal', 'index.php' ),
	'icon' => OCP\Util::imagePath( 'journal', 'journal.png' ),
	'name' => $l->t('Journal')
	)
);

OC_Search::registerProvider('OCA\Journal\Search_Provider');
OCP\Share::registerBackend('journal', 'OCA\Journal\Share_Backend');
