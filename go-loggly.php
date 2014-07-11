<?php
/**
 * Plugin name:	Gigaom Loggly Integration
 * Description:	Our Loggly adapter plugin: Establish a connection and log events to Loggly.
 * Author: 		Gigaom
 * Author URI: 	http://gigaom.com/
 */

/**
 * Singleton
 */
function go_loggly()
{
	global $go_loggly;

	if ( ! isset( $go_loggly ) || ! $go_loggly )
	{
		require_once __DIR__ . '/components/class-go-loggly.php';
		$go_loggly = new GO_Loggly();
	}//end if

	return $go_loggly;
} // END go_loggly