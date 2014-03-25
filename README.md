# Journal/Notes app for ownCloud

| Code quality | Latest release |
|--------------|----------------|
| [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/tanghus/journal/badges/quality-score.png?s=126186f91faaf8adcfe463e4fe26e252a3fe4dad)](https://scrutinizer-ci.com/g/tanghus/journal/) |
 [![Latest release](http://img.shields.io/github/release/tanghus/journal.svg)](https://github.com/tanghus/journal/releases) |

## Features

- Saves notes/journal entries as VJOURNAL records in the ownCloud Calendar.

- Integrates with ownClouds search backend.

- Sort entries by date/time ascending/descending or summary ascending/descending.

- Filter entries by timerange.

- Plain text or rich text editing (rich text editing is still buggy and immature).

- When switching from text to html the text is now parsed as MarkDown and vice-versa.

- Syncs with KDEPIMs Journal part.

- Share Journal entries with other users or groups.

- Completed tasks from the Task app can be automatically added as journal entries.

- Stores entry data as json objects in each element for quich access and to minimize ajax calls.

To install this app you will first have to install the [TAL Page Templates for ownCloud](https://github.com/tanghus/tal#readme) app.
You will also need to have the shipped Calendar app enabled, and at least one calendar enabled to store your Journal entries in.

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
