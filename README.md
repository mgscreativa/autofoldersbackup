
# AutoFoldersBackup

AutoFldersBackup is a pretty handy script to create automated backups of folders with ZIP compression and rotation. It's written in PHP and can be run from command line or browser.

# Features

- Keep track of daily, weekly and monthly backups
- Backup a single folder source, or treat the source folder as multiple backups sources (handy for shared hosting with addon-domains folders :wink:)
- Set excluded folders (yes you don't want to backup cache folders or log folders)
- Configured through simple configuration file
- It can be run from command line or web
- It logs activities to log folder
- Saves backups in individual folders in the backups destination folder separated by days, weeks, and months
- Compresses backups in ZIP format
- Automatically rotates old backups

# Configuration File Format
Configuration files are stored inside **conf** folder, and can be as many as you want.

## Single Folder Source

    <?php
    setlocale( LC_ALL, "es-AR" );
    date_default_timezone_set( 'America/Argentina/Buenos_Aires' );
    
    $settings = array(
        'singleDirSource' => true,
        'backSource'      => '/PATH/TO/SOURCE/FOLDER',
        'backDest'        => '/PATH/TO/DEST/FOLDER',
        'excludeFolders'  => array( 'cache', 'tmp', 'logs', 'addon-domains', 'large-folder', 'pretty-large-folder' ),
        'daysToKeep'      => 7,
        'weeksToKeep'     => 4,
        'monthsToKeep'    => 12,
    );
    
## Multiple Folders Sources

    <?php
    setlocale( LC_ALL, "es-AR" );
    date_default_timezone_set( 'America/Argentina/Buenos_Aires' );
    
    $settings = array(
        'singleDirSource' => false,
        'backSource'      => '/PATH/TO/SOURCE/FOLDER',
        'backDest'        => '/PATH/TO/DEST/FOLDER',
        'excludeFolders'  => array( 'cache', 'tmp', 'logs', 'large-folder', 'pretty-large-folder' ),
        'daysToKeep'      => 7,
        'weeksToKeep'     => 4,
        'monthsToKeep'    => 12,
    );
    
First two lines needed to get desired locale and timezone. Handy to get local timings in the log file.

- **singleDirSource**: Set to true to process backSource as single folder, meaning that will backup all files and folders inside that folder. Set to false to treat all folders as individual back sources
- **backSource**: Absolute path to backups source folder
- **backDest**: Absolute path to backups destination folder
- **excludeFolders**: Array with folders or files to be excluded from backup
- **daysToKeep**: Amount of days to be kept in the backup
- **weeksToKeep**: Amount of weeks to be kept in the backup
- **monthsToKeep**: Amount of months to be kept in the backup

# Configuration Instructions
Let's say you have a shared hosting account with the addon-domains folder inside /public_html. In that case you may want to create two config files, one for the public_html folder (excluding addon-domains) treated as single-source and another config file for the addon-domains folder treated as multi-source. This way you will have all addon-domains folders backed up independently and your public_html main folder too, without taking extra space.

It's is advisable to tweak the values of ...ToKeep vars, because your backup can take way too much space.

# Usage 
## From Command Line:

> /PATH/TO/PHP/CLI/BINARY/php ./index.php --conf=single-source.conf.php

## Trough HTTP Request
> /PATH/TO/CURL/BINARY/curl -m 86400 -vs http://domain.com/autofoldersbackup/index.php?conf=single-source.conf.php

You should change he value of **conf** parameter to call different configuration files

## Run Options
|Option|Command Line|Web Request|Description|
|--|--|--|--|
|**conf**|--conf=CONFIG-FILE.conf.php|conf=CONFIG-FILE.conf.php|Sets the current configuration file to process|
|**debug**|--debug=1|debug=1|Tries to enable PHP debug messages|
|**phpinfo**|--phpinfo=1|phpinfo=1|Tries to print phpinfo() output, then exits|

## Run Considerations
This script includes a **.user.ini** file that should be kept and tweaked to your need. It's configured to try to avoid maximum execution time hits, and leverage memory limits. In our tests with real huge shared server files we get real low memory usages but execution times of more than 300 seconds, so it is advisable to test the script before you put it to run with cron.

# Contributing
This script would be maintained very briefly, so if you found a bug, please, don't fill an issue report, instead we encourage you to analyze the script, try to solve the issue and send us a pull request to be added!

