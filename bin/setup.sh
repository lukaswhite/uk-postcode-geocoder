#!/usr/bin/env php
<?php

require_once( 'vendor/autoload.php' );

if ( ! isset( $argv[ 1 ] ) ) {
    echo "You must provide the path to the database directory as the first argument.\n";
    die;
}

if ( ! isset( $argv[ 2 ] ) ) {
    echo "You must provide the path to the database directory as the second argument.\n";
    die;
}

if ( ! file_exists( $argv[ 2 ] ) ) {
    echo "The file does not exist\n";
    die;
}

$provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner( $argv[ 1 ], $argv[ 2 ] );
$provisioner->run( );
