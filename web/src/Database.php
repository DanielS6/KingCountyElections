<?php
declare( strict_types = 1 );

/**
 * Database handling
 */

namespace KIElectionsDBApp;

use Exception;
use mysqli;
use mysqli_result;

class Database {

	public const VALUE_RESPONSE_PASS = 0;
	public const VALUE_RESPONSE_SMASH = 1;

	private mysqli $db;
	private string $sqlDir;

	public function __construct() {
		$this->db = new mysqli(
			KI_ELECTIONS_DB_HOST,
			KI_ELECTIONS_DB_USER,
			KI_ELECTIONS_DB_PASS,
			KI_ELECTIONS_DB_NAME
		);
		$this->sqlDir = dirname( __DIR__ ) . '/sql/';
	}

	public function __destruct() {
		// Close the connection
		$this->db->close();
	}

	public function tableExists( string $tableName ): bool {
		$result = $this->db->query( "SHOW TABLES LIKE '$tableName';" );
		return $result->num_rows !== 0;
	}

	private function ensureTable( string $tableName, string $patchFile ) {
		if ( $this->tableExists( $tableName ) ) {
			// Already created
			return;
		}
		$patchContents = file_get_contents( $this->sqlDir . $patchFile );
		$result = $this->db->query( $patchContents );
	}

	/**
	 * Dangerous - For use by scripts
	 */
	public function rawQuery( string $sqlToRun ): bool|mysqli_result {
		return $this->db->query( $sqlToRun );
	}

	public function streamSqlFile( string $filename ) {
		// For huge files don't load them all into memory
		$builder = '';
		$file = fopen( $filename, 'r' );
		while ( !feof( $file ) ) {
			$line = fgets( $file );
			if ( $line === false ) {
				continue;
			}
			$line = trim( $line );
			if ( $line == '' || str_starts_with( $line, '--' ) ) {
				continue;
			}
			if ( $builder === '' ) {
				$builder = $line;
			} else {
				$builder .= " $line\n";
			}
			$endOfStatement = str_ends_with( $line, ';' );
			if ( $endOfStatement || feof( $file ) ) {
				// echo "Running: $builder";
				$this->db->query( $builder );
				$builder = '';
			}
		}
		if ( $builder ) {
			// echo "Running: $builder";
			$this->db->query( $builder );
		}
	}

	public function ensureDatabase() {
		$this->ensureTable( 'ki_voters', 'ki_voters-table.sql' );
		$this->ensureTable( 'ki_voter_history', 'ki_voter_history-table.sql' );
	}

	public function dropTable( string $tableName, bool $mustExist ) {
		if ( !$mustExist ) {
			$this->db->query( "DROP TABLE IF EXISTS $tableName" );
			return;
		}
		if ( !$this->tableExists( $tableName ) ) {
			throw new Exception( "Table $tableName does not exist" );
		}
		$this->db->query( "DROP TABLE $tableName" );
	}

	public function clearTables() {
		// __temp and __old tables come from rebuilding
		$this->dropTable( 'ki_voter_history__temp', false );
		$this->dropTable( 'ki_voters__temp', false );
		$this->dropTable( 'ki_voter_history__old', false );
		$this->dropTable( 'ki_voters__old', false );
		$this->dropTable( 'ki_voter_history', false );
		$this->dropTable( 'ki_voters', false );
		// On the next page view ensureDatabase() will recreate the tables
	}

	public static function doSetup() {
		// So that the constructor can select the database without errors when
		// it doesn't exist (on docker)
		$mysqli = new mysqli(
			KI_ELECTIONS_DB_HOST,
			KI_ELECTIONS_DB_USER,
			KI_ELECTIONS_DB_PASS
		);
		$mysqli->query(
			"CREATE DATABASE IF NOT EXISTS " . KI_ELECTIONS_DB_NAME
		);
		// close the connection
		$mysqli->close();
		$db = new Database();
		$db->ensureDatabase();
	}

}
