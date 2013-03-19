# Journal/Notes app for ownCloud

## Features

- Saves notes/journal entries as VJOURNAL records in the ownCloud Calendar.

- Integrates with ownClouds search backend.

- Sort entries by date/time ascending/descending or summary ascending/descending.

- Filter entries by timerange.

- Plain text or rich text editing (rich text editing is still buggy and immature).

- Syncs with KDEPIMs Journal part.

- Share Journal entries with other users or groups.

- Completed tasks from the Task app can be automatically added as journal entries.

- Stores entry data as json objects in each element for quich access and to minimize ajax calls.

To install this app you will first have to install the [TAL Page Templates for ownCloud](https://github.com/tanghus/tal#readme) app.

## Installation from git

1. Install TAL Page Templates. Instructions at [Github](https://github.com/tanghus/tal#readme)

2. Go to your ownCloud apps dir and clone the repo there:
   <pre>
	 cd owncloud/apps
	 git clone git://github.com/tanghus/journal.git</pre>

3. From your browser go to the ownCloud apps page (`/settings/apps.php`) and enable the Journal app.

4. After a page refresh you should see the Journal app in the main menu.


## DISCLAIMER

There's no garantee this app won't eat your data, chew it up and spit it out. It works directly on the calendar app data
though not touching anything but VJOURNAL entries. [Always backup!](http://tanghus.net/2012/04/backup-owncloud-calendar-and-contacts/)

Please report any issues at the [github issue tracker](https://github.com/tanghus/journal/issues)