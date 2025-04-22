<?php
declare( strict_types = 1 );

namespace KIElectionsDBApp\Maintenance;

if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	echo "This script must be run from the command line\n";
	exit( 1 );
}

define( 'ENTRY_POINT', 'ConvertToSql' );

require_once dirname( __DIR__ ) . '/src/setup.php';

use GetOpt\ArgumentException;
use GetOpt\GetOpt;
use GetOpt\Option;
use KIElectionsDBApp\ConversionBuilder;
use KIElectionsDBApp\ConversionType;

$getOpt = new GetOpt();

$getOpt->addOptions( [
	Option::create( null, 'help', GetOpt::NO_ARGUMENT )
		->setDescription( 'Show help text' ),
	Option::create( null, 'source', GetOpt::REQUIRED_ARGUMENT )
		->setDescription( 'Path to TXT to convert' ),
	Option::create( null, 'target', GetOpt::REQUIRED_ARGUMENT )
		->setDescription( 'Path to SQL file to create' ),
	Option::create( null, 'type', GetOpt::REQUIRED_ARGUMENT )
		->setDescription( 'Type of conversion (voters or history)' )
		->setValidation(
			static fn ( $val ) =>
				$val === ConversionType::VOTERS->value ||
				$val === ConversionType::HISTORY->value,
			'--type must be either `voters` or `history`'
		),
	Option::create( null, 'from-blank', GetOpt::NO_ARGUMENT )
		->setDescription(
			'Create the table from blank (not used for non-first voter history)'
		),
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
foreach ( [ 'source', 'target', 'type' ] as $required ) {
	if ( !isset( $options[ $required ] ) ) {
		echo "Missing parameter --$required\n";
		exit();
	}
}

if ( !file_exists( $options['source'] ) ) {
	echo "File specified in --source does not exist\n";
	exit();
}
if ( file_exists( $options['target'] ) ) {
	echo "File specified in --target already exists\n";
	exit();
}

$converter = new ConversionBuilder(
	ConversionType::from( $options['type'] ),
	/* batchsize */
	10000
);
$converter->doConversion(
	$options['source'],
	$options['target'],
	isset( $options['from-blank'] )
);
