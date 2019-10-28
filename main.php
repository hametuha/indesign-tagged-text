<?php

list( $self, $src_dir, $dest_dir ) = $argv;

require __DIR__ . '/vendor/autoload.php';

if ( ! is_dir( $src_dir ) || ! is_dir( $dest_dir ) ) {
	die( 'Please specify directory name: php main.php ./src ./dest' );
}

$converter = new \Hametuha\InDesignTaggedText( 'MAC', 'UNICODE' );

foreach ( scandir( $dest_dir ) as $file ) {
	if ( ! preg_match( '#\.txt$#u', $file ) ) {
		continue;
	}
	$converter->convert( $src_dir . '/' . $file )	;
	$converter->save( $dest_dir . '/' . $file );
}
