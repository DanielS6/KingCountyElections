<?php
declare( strict_types = 1 );

/**
 * Set up everything that we need (like error reporting, sessions, and
 * autoloading). This should be the first thing included in all entry points.
 */

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );
ini_set( 'date.timezone', 'UTC' );

if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'POST' ) {
	ignore_user_abort( true );
}

if ( version_compare( PHP_VERSION, '8.4', '<' ) ) {
	trigger_error(
		'PHP 8.4+ is required, you are using ' . PHP_VERSION,
		E_USER_ERROR
	);
}
if ( !extension_loaded( 'mysqli' ) ) {
	trigger_error( 'PHP extension `mysqli`is missing!', E_USER_ERROR );
}

if ( !defined( 'ENTRY_POINT' ) ) {
	echo "ENTRY_POINT is not defined!\n";
	exit();
}

define( 'KI_ELECTIONS_DB_HOST', 'db' );
define( 'KI_ELECTIONS_DB_USER', 'root' );
define( 'KI_ELECTIONS_DB_PASS', 'root' );
define( 'KI_ELECTIONS_DB_NAME', 'ki_elections_db' );

// Autoloading of composer
require_once 'vendor/autoload.php';

// Autoloading of our classes
spl_autoload_register(
	static function ( string $className ) {
		if ( str_starts_with( $className, 'KIElectionsDBApp\\' ) ) {
			// Trim off the `KIElectionsDBApp\`
			$className = substr( $className, 17 );
			require_once str_replace( '\\', '/', $className ) . '.php';
		}
	}
);

\KIElectionsDBApp\Database::doSetup();
