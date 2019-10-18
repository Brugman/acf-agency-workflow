<?php

/**
 * Dump.
 */

if ( !function_exists( 'd' ) )
{
    function d( $var )
    {
        echo "<pre style=\"max-height:35vh;z-index:9999;position:relative;overflow-y:scroll;white-space:pre-wrap;word-wrap:break-word;padding:10px 15px;border:1px solid #fff;background-color:#161616;text-align:left;line-height:1.5;font-family:Courier;font-size:16px;color:#fff;\">";
        print_r( $var );
        echo "</pre>";
    }
}

if ( !function_exists( 'dump' ) )
{
    function dump( $var )
    {
        d( $var );
    }
}

/**
 * Dump & die.
 */

if ( !function_exists( 'dd' ) )
{
    function dd( $var )
    {
        d( $var );
        exit;
    }
}

/**
 * Write to log.
 */

function write_to_log( $in = 'an-obscure-default' )
{
    if ( $in == 'an-obscure-default' )
        return false;

    $file = __DIR__.'/debug.log';

    if ( !file_exists( $file ) )
        fopen( $file, 'w' ) or exit( 'unable to create log file' );

    $fh = fopen( $file, 'a' ) or exit( 'unable to append log file' );

    $out = '';
    $out .= date('H:i:s');

    if ( is_string( $in ) || is_numeric( $in ) )
    {
        $out .= ' | ';
        $out .= $in;
    }
    else
    {
        $out .= "\r\n";
        $out .= print_r( $in, true );
        $out .= '--------';
    }

    $out .= "\r\n";

    fwrite( $fh, $out );

    return true;
}

