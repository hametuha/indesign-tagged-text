#!/usr/bin/env php
<?php
/**
 * CLI interface
 *
 * @package indesign-tagged-text
 */

// Is this CLI?
if ( 'cli' !== php_sapi_name() ) {
	die( 'Do not access directly.' );
}

// Get arguments.
list( $self, $src_dir, $dest_dir ) = $argv;
$markdown                          = isset( $argv[3] ) && '--markdown' === $argv[3];

// Check directory.
foreach ( [ $src_dir, $dest_dir ] as $dir ) {
	if ( ! is_dir( $dir ) ) {
		$message = sprintf( 'Directory not exists: %s', $dir );
		die( $message . PHP_EOL );
	}
}

// Check class.
if ( ! class_exists( 'Hametuha\\InDesignTaggedText' ) ) {
	$found = false;
	foreach ( [
		'.',
		__DIR__,
	] as $base_dir ) {
		$autoloader = $base_dir . '/vendor/autoload.php';
		if ( file_exists( $autoloader ) ) {
			require_once $autoloader;
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		die( 'Failed to find composer.' . PHP_EOL );
	}
}

// Initialize.
$converter = new \Hametuha\InDesignTaggedText( 'MAC', 'UNICODE' );

// Check directory existence.
if ( ! is_dir( $dest_dir ) ) {
	if ( ! mkdir( $dest_dir, 0755, true ) ) {
		die( sprintf( 'Failed to create destination folder: %s', $dest_dir ) );
	}
}

// Scan and convert.
$total = 0;
foreach ( scandir( $src_dir ) as $file ) {
	if ( ! preg_match( '#\.txt$#u', $file ) ) {
		continue;
	}
	printf( 'Converting %s' . PHP_EOL, $file );
	$converter->convert( $src_dir . '/' . $file );
	$converter->save( $dest_dir . '/' . $file, $markdown );
	$total++;
}

echo PHP_EOL;
echo sprintf( 'Finished converting %d files', $total ) . PHP_EOL;
