<?php
set_time_limit( 0 );
$time_start = microtime( true );
logClear();

if ( PHP_SAPI === 'cli' )
{
    if ( empty( $argv ) )
    {
        exit();
    }

    array_shift( $argv );
    foreach ( $argv as $arg )
    {
        $arg = trim( $arg, '-' );
        list( $command, $value ) = explode( '=', $arg );
        $_REQUEST[$command] = $value;
    }
}

if ( isset( $_REQUEST['debug'] ) && ! empty( $_REQUEST['debug'] ) )
{
    ini_set( 'display_errors', 1 );
    ini_set( 'display_startup_errors', 1 );
    error_reporting( E_ALL );
}

if ( isset( $_REQUEST['phpinfo'] ) && ! empty( $_REQUEST['phpinfo'] ) && function_exists( 'phpinfo' ) )
{
    phpinfo();
    exit( 0 );
}
else if ( ! function_exists( 'phpinfo' ) )
{
    logWrite( 'phpinfo function disabled.' );
    exit( 0 );
}

if ( isset( $_REQUEST['conf'] ) && ! empty( $_REQUEST['conf'] ) )
{
    $config       = $_REQUEST['conf'];
    $confFilePath = dirname( __FILE__ ) . '/conf/' . $config;
    if ( @file_exists( $confFilePath ) )
    {
        require_once( $confFilePath );
    }
    else
    {
        logWrite( 'Can\'t find config file "' . $confFilePath . '"' );
        exit( 0 );
    }
}
else
{
    logWrite( "No config file provided, exiting." );
    exit( 0 );
}

logWrite( 'Started backup at ' . date( 'd-m-Y H:i:s' ) );

$singleDirSource = $settings['singleDirSource'];
$backSource      = $settings['backSource'];
$backDest        = $settings['backDest'];
$excludeFolders  = $settings['excludeFolders'];
$daysToKeep      = $settings['daysToKeep'];
$weeksToKeep     = $settings['weeksToKeep'];
$monthsToKeep    = $settings['monthsToKeep'];


if ( ! file_exists( $backSource ) )
{
    logWrite( 'Directory ' . $backSource . ' not found. Extiting.' );
    exit ( 0 );
}

if ( $singleDirSource )
{
    backupFolder( $backSource, $backDest, $excludeFolders );
    rotateBackups( basename( $backSource ), $backDest, $daysToKeep, $weeksToKeep, $monthsToKeep );
}
else
{
    $dirs = glob( $backSource . '/' . '*', GLOB_NOSORT + GLOB_ONLYDIR );
    foreach ( $dirs as $dir )
    {
        if ( in_array( basename( $dir ), $excludeFolders ) )
        {
            continue;
        }
        backupFolder( $dir, $backDest, $excludeFolders );
        rotateBackups( basename( $dir ), $backDest, $daysToKeep, $weeksToKeep, $monthsToKeep );
    }
}

$loadAVGArray = sys_getloadavg();
logWrite( 'System load average: 1 min ' . $loadAVGArray[0] . ', 5 min ' . $loadAVGArray[1] . ', 15 min ' . $loadAVGArray[2] );
logWrite( 'Total execution time: ' . number_format( ( microtime( true ) - $time_start ), 0 ) . 's' );
logWrite( 'Finished backup at ' . date( 'd-m-Y H:i:s' ) );

function logClear() {
    $log_file_data = dirname( __FILE__ ) . '/log/backup.log';
    if ( file_exists( $log_file_data ) )
    {
        file_put_contents( $log_file_data, "" );
    }
}

function logWrite( $log_msg ) {
    $log_filename = dirname( __FILE__ ) . '/log';
    if ( ! file_exists( $log_filename ) )
    {
        mkdir( $log_filename, 0777, true );
    }

    $log_msg       = $log_msg . "\r\n";
    $write_log_msg = '[' . date( 'd-m-Y H:i:s' ) . '] ' . $log_msg;
    echo $log_msg;

    $log_file_data = $log_filename . '/backup.log';
    file_put_contents( $log_file_data, $write_log_msg, FILE_APPEND );
}

function rotateBackups( $backupName, $backDest, $daysToKeep, $weeksToKeep, $monthsToKeep ) {
    set_time_limit( 0 );
    $backDestDaily   = $backDest . '/' . $backupName . '/daily';
    $backDestWeekly  = $backDest . '/' . $backupName . '/weekly';
    $backDestMonthly = $backDest . '/' . $backupName . '/monthly';

    $newestDaylyFilename   = '';
    $oldestDaylyFilename   = '';
    $oldestWeeklyFilename  = '';
    $oldestMonthlyFilename = '';

    if ( ! file_exists( $backDestWeekly ) )
    {
        mkdir( $backDestWeekly, 0755, true );
    }

    if ( ! file_exists( $backDestMonthly ) )
    {
        mkdir( $backDestMonthly, 0755, true );
    }

    $filesDaily   = glob( $backDestDaily . '/' . '*.zip', GLOB_NOSORT );
    $filesWeekly  = glob( $backDestWeekly . '/' . '*.zip', GLOB_NOSORT );
    $filesMonthly = glob( $backDestMonthly . '/' . '*.zip', GLOB_NOSORT );

    $newestTimestamp = 0;
    foreach ( $filesDaily as $file )
    {
        $fileTimestamp = filemtime( $file );
        if ( $fileTimestamp > $newestTimestamp )
        {
            $newestDaylyFilename = $file;
            $newestTimestamp     = $fileTimestamp;
        }
    }

    if ( $weeksToKeep > 0 && count( $filesWeekly ) == 0 )
    {
        copy( $newestDaylyFilename, $backDestWeekly . '/' . basename( $newestDaylyFilename ) );
    }

    if ( $monthsToKeep > 0 && count( $filesMonthly ) == 0 )
    {
        copy( $newestDaylyFilename, $backDestMonthly . '/' . basename( $newestDaylyFilename ) );
    }

    if ( $daysToKeep > 7 )
    {
        $daysToKeep = 7;
    }

    if ( count( $filesDaily ) > $daysToKeep )
    {
        for ( $i = ( count( $filesDaily ) - $daysToKeep ); $i > 0; $i -- )
        {
            $oldestTimestamp = time();
            $oldestKey       = - 1;
            foreach ( $filesDaily as $key => $file )
            {
                $fileTimestamp = filemtime( $file );
                if ( $fileTimestamp < $oldestTimestamp )
                {
                    $oldestDaylyFilename = $file;
                    $oldestTimestamp     = $fileTimestamp;
                    $oldestKey           = $key;
                }
            }
            unlink( $oldestDaylyFilename );
            unset( $filesDaily[$oldestKey] );
        }
    }

    $oldestTimestamp = time();
    if ( $weeksToKeep > 0 && date( "w", time() ) === '1' )
    {
        if ( $weeksToKeep > 4 )
        {
            $weeksToKeep = 4;
        }

        copy( $newestDaylyFilename, $backDestWeekly . '/' . basename( $newestDaylyFilename ) );

        if ( count( $filesWeekly ) > $weeksToKeep )
        {
            for ( $i = ( count( $filesWeekly ) - $weeksToKeep ); $i > 0; $i -- )
            {
                $oldestKey = - 1;
                foreach ( $filesWeekly as $key => $file )
                {
                    $fileTimestamp = filemtime( $file );
                    if ( $fileTimestamp < $oldestTimestamp )
                    {
                        $oldestWeeklyFilename = $file;
                        $oldestTimestamp      = $fileTimestamp;
                        $oldestKey            = $key;
                    }
                }
                unlink( $oldestWeeklyFilename );
                unset( $filesWeekly[$oldestKey] );
            }
        }
    }

    $oldestTimestamp = time();
    if ( $monthsToKeep > 0 && date( "j", time() ) === '1' )
    {
        if ( $monthsToKeep > 12 )
        {
            $monthsToKeep = 12;
        }

        copy( $newestDaylyFilename, $backDestWeekly . '/' . basename( $newestDaylyFilename ) );

        if ( count( $filesMonthly ) > $monthsToKeep )
        {
            for ( $i = ( count( $filesMonthly ) - $monthsToKeep ); $i > 0; $i -- )
            {
                $oldestKey = - 1;
                foreach ( $filesMonthly as $key => $file )
                {
                    $fileTimestamp = filemtime( $file );
                    if ( $fileTimestamp < $oldestTimestamp )
                    {
                        $oldestMonthlyFilename = $file;
                        $oldestTimestamp       = $fileTimestamp;
                        $oldestKey             = $key;
                    }
                }
                unlink( $oldestMonthlyFilename );
                unset( $filesMonthly[$oldestKey] );
            }
        }
    }
}

function getTTL() {
    $ttlSeconds = 5;

    return time() + $ttlSeconds;
}

function backupFolder( $backSource, $backDest, $excludeFolders ) {
    set_time_limit( 0 );

    logWrite( 'Starting backup of ' . $backSource );

    $zipbasename = basename( $backSource );
    $backDest    = $backDest . '/' . $zipbasename . '/daily';
    $zipfilename = $backDest . '/' . $zipbasename . '-' . strftime( '%d-%m-%Y' ) . ".zip";

    chdir( $backSource );
    $dirList  = new RecursiveDirectoryIterator( './', FilesystemIterator::SKIP_DOTS );
    $fileList = new RecursiveIteratorIterator( $dirList, RecursiveIteratorIterator::SELF_FIRST );

    if ( ! file_exists( $backDest ) )
    {
        mkdir( $backDest, 0755, true );
    }

    if ( file_exists( $zipfilename ) )
    {
        unlink( $zipfilename );
    }

    $zip = new ZipArchive();
    if ( $zip->open( $zipfilename, ZipArchive::CREATE ) !== true )
    {
        logWrite( "Could not open archive" );
        exit();
    }

    $ttl = getTTL();
    foreach ( $fileList as $file )
    {
        if ( time() >= $ttl )
        {
            set_time_limit( 0 );
            $ttl = getTTL();
            logWrite( 'Resting a little or the CPU will get angry!' );
            usleep( 10000 );
        }

        $exclude = false;
        $file    = substr( $file, 2, strlen( $file ) );
        foreach ( $excludeFolders as $excludeFolder )
        {
            if ( preg_match( '/\b' . $excludeFolder . '\b/i', $file ) > 0 )
            {
                $exclude = true;
            }
        }

        if ( ! $exclude )
        {
            if ( is_dir( $file ) === true )
            {
                $zip->addEmptyDir( $file );
            }
            else if ( is_file( $file ) === true )
            {
                $zip->addFile( $file, $file );
            }
        }
    }

    logWrite( 'Started backup compression of ' . $backSource );
    if ( $zip->close() )
    {
        logWrite( 'ZIP file closed ok' );
    }
    else
    {
        logWrite( 'ZIP close failed' );
    }
    logWrite( 'Finished backup compression of ' . $backSource );
}
