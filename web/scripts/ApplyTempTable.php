<?php
declare( strict_types = 1 );

namespace KIElectionsDBApp\Maintenance;

if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	echo "This script must be run from the command line\n";
	exit( 1 );
}

define( 'ENTRY_POINT', 'ApplyTempTable' );

require_once dirname( __DIR__ ) . '/src/setup.php';

use GetOpt\ArgumentException;
use GetOpt\GetOpt;
use GetOpt\Option;
use KIElectionsDBApp\ConversionType;
use KIElectionsDBApp\Database;

$getOpt = new GetOpt();

$getOpt->addOptions( [
	Option::create( null, 'help', GetOpt::NO_ARGUMENT )
		->setDescription( 'Show help text' ),
	Option::create( null, 'type', GetOpt::REQUIRED_ARGUMENT )
		->setDescription( 'Type of data to apply (voters or history)' )
		->setValidation(
			static fn ( $val ) =>
				$val === ConversionType::VOTERS->value ||
				$val === ConversionType::HISTORY->value,
			'--type must be either `voters` or `history`'
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
if ( !isset( $options['type'] ) ) {
	echo "Missing parameter --type\n";
	exit();
}
$tableName = ConversionType::from( $options['type'] )->getTableName();
$tempTableName = $tableName . '__temp';

$db = new Database();
if ( !$db->tableExists( $tempTableName ) ) {
	echo "The temporary version $tempTableName does not exist!\n";
	exit();
}
if ( $db->tableExists( $tableName ) ) {
	echo "Original table exists, renaming both tables at once\n";
	$oldName = $tableName . '__old';
	var_dump(
		$db->rawQuery(
			"RENAME TABLE $tableName TO $oldName, $tempTableName to $tableName"
		)
	);
	echo "And now deleting the old version\n";
	$db->dropTable( $oldName, true );
} else {
	echo "Original table does not exist, renaming __temp";
	var_dump(
		$db->rawQuery(
			"RENAME TABLE $tempTableName to $tableName"
		)
	);
}
