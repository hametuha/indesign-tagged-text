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

// Check directory.
foreach ( [ $src_dir, $dest_dir ] as $dir ) {
	if ( ! is_dir( $dir ) ) {
		$message = sprintf( 'Directory not exists: %s', $dir );
		die( $message . PHP_EOL );
	}
}

// Check class.
if ( ! class_exists( 'Hametuha\\InDesignTaggedText' ) ) {
	$autoloader = __DIR__ . '/vendor/autoload.php';
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	} else {
		die( 'Failed to find composer.' . PHP_EOL );
	}
}

// Initialize.
$converter = new \Hametuha\InDesignTaggedText( 'MAC', 'UNICODE' );

foreach ( scandir( $dest_dir ) as $file ) {
	if ( ! preg_match( '#\.txt$#u', $file ) ) {
		continue;
	}
	$converter->convert( $src_dir . '/' . $file );
	$converter->save( $dest_dir . '/' . $file );
}

echo 'Finished converting.' . PHP_EOL;
