<?php
declare( strict_types = 1 );

namespace KIElectionsDBApp;

enum ConversionType: string {
	case VOTERS = 'voters';
	case HISTORY = 'history';

	public function getTableName(): string {
		return match ( $this ) {
			ConversionType::VOTERS => 'ki_voters',
			ConversionType::HISTORY => 'ki_voter_history',
		};
	}
}
