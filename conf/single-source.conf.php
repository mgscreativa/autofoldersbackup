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
