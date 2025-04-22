<?php
declare( strict_types = 1 );

namespace KIElectionsDBApp\Maintenance;

if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	echo "This script must be run from the command line\n";
	exit( 1 );
}

define( 'ENTRY_POINT', 'SqlRunner' );

require_once dirname( __DIR__ ) . '/src/setup.php';

use GetOpt\ArgumentException;
use GetOpt\GetOpt;
use GetOpt\Option;
use KIElectionsDBApp\Database;

$getOpt = new GetOpt();

$getOpt->addOptions( [
	Option::create( null, 'help', GetOpt::NO_ARGUMENT )
		->setDescription( 'Show help text' ),
	Option::create( null, 'source', GetOpt::REQUIRED_ARGUMENT )
		->setDescription( 'Path to SQL to run' ),
] );

try {
	$getOpt->process();
} catch ( ArgumentException $exception ) {
	if ( !$getOpt->getOption( 'help' ) ) {
		echo $exception->getMessage() . "\n";
		exit;
	}
}
if ( $getOpt->getOption( 'help' ) ) {
	echo $getOpt->getHelpText();
	exit;
}

$options = $getOpt->getOptions();
if ( !isset( $options['source'] ) ) {
	echo "Missing parameter --source\n";
	exit();
}

if ( !file_exists( $options['source'] ) ) {
	echo "File specified in --source does not exist\n";
	exit();
}

// Probably a big file, don't load into memory
echo "Streaming the sql file: " . $options['source'] . "\n";
$db = new Database();
$db->streamSqlFile( $options['source'] );
