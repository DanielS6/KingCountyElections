<?php
declare( strict_types = 1 );

namespace KIElectionsDBApp;

class ConversionBuilder {

	// phpcs:disable Generic.Files.LineLength.TooLong
	private const COMMON_SQL_START = <<<END
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
END;
	// phpcs:enable Generic.Files.LineLength.TooLong

	private const COMMON_SQL_END = <<<END
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
END;

	private ConversionType $type;
	private int $batchSize;

	public function __construct( ConversionType $type, int $batchSize ) {
		$this->type = $type;
		$this->batchSize = $batchSize;
	}

	/**
	 * Given the raw line from one of the text files, that was already
	 *   - trimmed
	 *   - checked for single quotes
	 *   - checked for backslashes
	 * Return the list of values to insert for that row, in the form
	 * `('a', 123, ...)`
	 */
	private function convertLine( string $line ): string {
		return match ( $this->type ) {
			ConversionType::VOTERS => $this->convertVotersLine( $line ),
			ConversionType::HISTORY => $this->convertHistoryLine( $line ),
		};
	}

	/**
	 * Given the raw line from the voters files, that was already
	 *   - trimmed
	 *   - checked for single quotes
	 *   - checked for backslashes
	 * Return the list of values to insert for that row, in the form
	 * `('a', 123, ...)`
	 */
	private function convertVotersLine( string $line ): string {
		// No named groups in preg_replace
		$groups = [];
		$matches = preg_match(
			'/^
				(?P<id>\d+)\|
				(?P<FName>[^\|]*)\|
				(?P<MName>[^\|]*)\|
				(?P<LName>[^\|]*)\|
				(?P<NameSuffix>[^\|]*)\|
				(?P<BYear>\d{4})\|
				(?P<Gender>[MFOU]?)\|
				(?P<RegStNum>[^\|]*)\|
				(?P<RegStFrac>[^\|]*)\|
				(?P<RegStName>[^\|]*)\|
				(?P<RegStType>[^\|]*)\|
				(?P<RegUnitType>[^\|]*)\|
				(?P<RegStPreDirection>[^\|]*)\|
				(?P<RegStPostDirection>[^\|]*)\|
				(?P<RegStUnitNum>[^\|]*)\|
				(?P<RegCity>[^\|]*)\|
				(?P<RegState>[^\|]*)\|
				(?P<RegZipCode>\d*)\|
				(?P<CountyCode>[^\|]*)\|
				(?P<PrecinctCode>\d*)\|
				(?P<PrecinctPart>[^\|]*)\|
				(?P<LegislativeDistrict>\d*)\|
				(?P<CongressionalDistrict>\d*)\|
				(?P<Mail1>[^\|]*)\|
				(?P<Mail2>[^\|]*)\|
				(?P<Mail3>[^\|]*)\|
				(?P<MailCity>[^\|]*)\|
				(?P<MailZip>[^\|]*)\|
				(?P<MailState>[^\|]*)\|
				(?P<MailCountry>[^\|]*)\|
				(?P<RegYYYY>\d{4})-(?P<RegMM>\d{2})-(?P<RegDD>\d{2})\|
				(?P<vote>(\d{4}-\d{2}-\d{2})?)\|
				(?P<Status>[^\|]*)
			$/x',
			$line,
			$groups
		);
		// var_dump( $groups );
		// Some people have never voted
		if ( $groups['vote'] !== '' ) {
			$groups['vote'] = str_replace( '-', '', $groups['vote'] );
		}
		// Some people in pricinct 8888 don't have a legislative district?
		if ( $groups['LegislativeDistrict'] === '' ) {
			$groups['LegislativeDistrict'] = 0;
		}
		$registration = (
			$groups['RegYYYY'] .
			$groups['RegMM'] .
			$groups['RegDD']
		);
		$rowValues = '(' .
			$groups['id'] . ', ' .
			"'" . $groups['FName'] . "', " .
			"'" . $groups['MName'] . "', " .
			"'" . $groups['LName'] . "', " .
			"'" . $groups['NameSuffix'] . "', " .
			$groups['BYear'] . ", " .
			"'" . $groups['Gender'] . "', " .
			"'" . $groups['RegStNum'] . "', " .
			"'" . $groups['RegStFrac'] . "', " .
			"'" . $groups['RegStName'] . "', " .
			"'" . $groups['RegStType'] . "', " .
			"'" . $groups['RegUnitType'] . "', " .
			"'" . $groups['RegStPreDirection'] . "', " .
			"'" . $groups['RegStPostDirection'] . "', " .
			"'" . $groups['RegStUnitNum'] . "', " .
			"'" . $groups['RegCity'] . "', " .
			"'" . $groups['RegState'] . "', " .
			"'" . $groups['RegZipCode'] . "', " .
			"'" . $groups['CountyCode'] . "', " .
			"'" . $groups['PrecinctCode'] . "', " .
			"'" . $groups['PrecinctPart'] . "', " .
			$groups['LegislativeDistrict'] . ", " .
			$groups['CongressionalDistrict'] . ", " .
			"'" . $groups['Mail1'] . "', " .
			"'" . $groups['Mail2'] . "', " .
			"'" . $groups['Mail3'] . "', " .
			"'" . $groups['MailCity'] . "', " .
			"'" . $groups['MailZip'] . "', " .
			"'" . $groups['MailState'] . "', " .
			"'" . $groups['MailCountry'] . "', " .
			"'" . $registration . "', " .
			"'" . $groups['vote'] . "', " .
			"'" . $groups['Status'] . "'" .
		")";
		// echo "\n\n" . $rowValues . "\n\n";
		// var_dump( $rowValues );
		return $rowValues;
	}

	/**
	 * Given the raw line from the voting history files, that was already
	 *   - trimmed
	 *   - checked for single quotes
	 *   - checked for backslashes
	 * Return the list of values to insert for that row, in the form
	 * `('a', 123, ...)`
	 */
	private function convertHistoryLine( string $line ): string {
		// No named groups in preg_replace
		$groups = [];
		$matches = preg_match(
			'/^
				(?P<histId>\d+)\|
				(?P<CountyCode>[A-Z]{2})\|
				(?P<CountyCodeVoting>[A-Z]{2})\|
				(?P<voterId>\d+)\|
				(?P<electYYYY>\d{4})-(?P<electMM>\d{2})-(?P<electDD>\d{2})
			$/x',
			$line,
			$groups
		);
		// var_dump( $groups );
		$electDate = (
			$groups['electYYYY'] .
			$groups['electMM'] .
			$groups['electDD']
		);
		$rowValues = '(' .
			$groups['histId'] . ', ' .
			"'" . $groups['CountyCode'] . "', " .
			"'" . $groups['CountyCodeVoting'] . "', " .
			$groups['voterId'] . ", " .
			"'" . $electDate . "'" .
		")";
		// echo "\n\n" . $rowValues . "\n\n";
		// var_dump( $rowValues );
		return $rowValues;
	}

	/**
	 * Get the SQL to
	 *   - back up variables
	 *   - drop the temporary table if it exists
	 *   - create it with the right structure [only if not just appending]
	 *   - disable key checking
	 */
	private function getStartSql( bool $fromBlank ): string {
		$result = self::COMMON_SQL_START;
		$tableName = $this->type->getTableName();
		$tempTableName = $tableName . '__temp';
		if ( $fromBlank ) {
			$result .= <<<END

DROP TABLE IF EXISTS `$tempTableName`;
/*!40101 SET @saved_cs_client	 = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `$tempTableName` LIKE `$tableName`;
/*!40101 SET character_set_client = @saved_cs_client */;

END;
		}
		$result .= "\n/*!40000 ALTER TABLE `$tempTableName` DISABLE KEYS */;\n";
		$result .= "\n";

		return $result;
	}

	/**
	 * Get the SQL to
	 *   - restore key checking
	 *   - restore variables
	 */
	private function getEndSql(): string {
		$tableName = $this->type->getTableName();
		$tempTableName = $tableName . '__temp';
		$result = "/*!40000 ALTER TABLE `$tempTableName` ENABLE KEYS */;\n";
		$result .= self::COMMON_SQL_END;
		$result .= "\n";
		return $result;
	}

	public function doConversion(
		string $path,
		string $out,
		bool $fromBlank
	) {
		// these definitions don't do anything but exist to make phan happy
		$inputFile = null;
		$outputFile = null;

		try {
			// extract gzipped file to a temporary file
			$inputFile = gzopen( $path, 'rb' );
			$outputFile = fopen( $out, 'wb' );
			if ( $inputFile === false ) {
				throw new \Exception( 'Could not open dump file for reading' );
			}

			if ( $outputFile === false ) {
				throw new \Exception( 'Could not open temp file for writing' );
			}

			fwrite( $outputFile, $this->getStartSql( $fromBlank ) );

			$id = 0;
			$tableName = $this->type->getTableName();
			$lineStart = "INSERT INTO {$tableName}__temp VALUES";
			$currentLine = $lineStart;

			// Voting history has some DUPLICATES
			$duplicates = [
				// 21-22
				'146561295|KI|KI|2724198|2021-08-03',
				'146738094|KI|KI|3639096|2021-08-03',
				'146847170|KI|KI|3282335|2021-08-03',
				'146941361|KI|KI|12173030|2021-08-03',
				'146980490|KI|KI|3323956|2021-08-03',
				'147327555|KI|KI|2604788|2021-08-03',
				'147403505|KI|KI|3562806|2021-08-03',
				'147506624|KI|KI|2641500|2021-08-03',
				'147537773|KI|KI|11280332|2021-08-03',
				'147648601|KI|KI|8650902|2021-08-03',
				'147666296|KI|KI|3139988|2021-08-03',
				'147928784|KI|KI|2724198|2021-11-02',
				'148326334|KI|KI|2641500|2021-11-02',
				'148357149|KI|KI|2604788|2021-11-02',
				'148684528|KI|KI|3619899|2021-11-02',
				'148806952|KI|KI|3323956|2021-11-02',
				'148951962|KI|KI|3139988|2021-11-02',
				'149097587|KI|KI|11280332|2021-11-02',
				'149097911|KI|KI|3562806|2021-11-02',
				'149236912|KI|KI|3282335|2021-11-02',
				'149349997|KI|KI|2609594|2021-11-02',
				'149362215|KI|KI|8650902|2021-11-02',
				'149425452|KI|KI|3762966|2021-11-02',
				'149692471|KI|KI|11280332|2021-12-07',
				'149790397|KI|KI|2724198|2022-02-08',
				'150027576|KI|KI|11280332|2022-02-08',
				'150132088|KI|KI|3562806|2022-02-08',
				'150297070|KI|KI|3619899|2022-02-08',
				'150525298|KI|KI|3139988|2022-02-08',
				'151038923|KI|KI|2724198|2022-08-02',
				'151478571|KI|KI|2609594|2022-08-02',
				'151805263|KI|KI|3619899|2022-08-02',
				'151819532|KI|KI|12173030|2022-08-02',
				'151836459|KI|KI|3762966|2022-08-02',
				'151876152|KI|KI|3139988|2022-08-02',
				'151943332|KI|KI|3562806|2022-08-02',
				'152167779|KI|KI|2604788|2022-08-02',
				'152396131|KI|KI|3323956|2022-08-02',
				'152667109|KI|KI|11280332|2022-08-02',
				'152730843|KI|KI|12520931|2022-08-02',
				'152840331|KI|KI|3282335|2022-08-02',
				'152995144|KI|KI|2604788|2022-11-08',
				'153130293|KI|KI|3619899|2022-11-08',
				'153146278|KI|KI|3323956|2022-11-08',
				'153196033|KI|KI|2724198|2022-11-08',
				'153357975|KI|KI|3119656|2022-11-08',
				'153397379|KI|KI|3639096|2022-11-08',
				'153964982|KI|KI|2609594|2022-11-08',
				'154185885|KI|KI|2641500|2022-11-08',
				'154394068|KI|KI|3762966|2022-11-08',
				'154593069|KI|KI|3562806|2022-11-08',
				'155295616|KI|KI|11280332|2022-11-08',
				'155698371|KI|KI|3139988|2022-11-08',
				'155756237|KI|KI|8650902|2022-11-08',
				'157020108|KI|KI|12520931|2022-11-08',
				'157020136|KI|KI|12520931|2022-11-08',
				'157020164|KI|KI|12520931|2022-11-08',
				// 23-24
				'159051078|KI|KI|12068347|2023-11-07',
				'164334971|KI|KI|12068347|2024-08-06',
				'167210226|KI|KI|12068347|2024-11-05',
			];
			// use faster array_key_exists
			$duplicates = array_flip( $duplicates );
			while ( !gzeof( $inputFile ) ) {
				$line = gzgets( $inputFile );
				if ( $line === false ) {
					throw new \Exception( 'Error reading dump file' );
				}
				$line = trim( $line );
				if ( $id === 0 ) {
					if ( str_starts_with( $line, 'StateVoterID' ) ) {
						if ( $this->type !== ConversionType::VOTERS ) {
							throw new \Exception( "Wrong start line?" );
						}
						continue;
					}
					if ( str_starts_with( $line, 'VoterHistoryID' ) ) {
						if ( $this->type !== ConversionType::HISTORY ) {
							throw new \Exception( "Wrong start line?" );
						}
						continue;
					}
				}
				$id++;
				if ( array_key_exists( $line, $duplicates ) ) {
					// Not adding custom handling to add the values once
					continue;
				}
				if ( str_contains( $line, "'" ) ) {
					// "130||LEO''S|PL|#|||C|ENUMCLAW|WA|98022"
					$line = str_replace( "'", "''", $line );
				}
				if ( str_contains( $line, "\\" ) ) {
					// "1|UNIT 4 \ 6 PALMER STREET|NAREMBURN NSW 2065||"
					$line = str_replace( "\\", "\\\\", $line );
				}
				// if ( $id >= 5000 ) { break; }
				if ( $currentLine !== $lineStart ) {
					// Comma before new values
					$currentLine .= ",";
				}
				$currentLine .= $this->convertLine( $line );
				if ( $id % $this->batchSize !== 0 ) {
					continue;
				}
				$currentLine .= ";";
				fwrite( $outputFile, $currentLine . "\n" );
				$currentLine = $lineStart;
			}
			if ( $currentLine !== $lineStart ) {
				$currentLine .= ";";
				fwrite( $outputFile, $currentLine . "\n" );
			}

			fwrite( $outputFile, $this->getEndSql() );
		} finally {
			if ( $inputFile !== false && $inputFile !== null ) {
				gzclose( $inputFile );
			}

			if ( $outputFile !== false && $outputFile !== null ) {
				fclose( $outputFile );
			}
		}
	}
}
