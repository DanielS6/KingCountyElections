-- List of voting history, field names are from the
-- "Washington State Voter Registration Database"
CREATE TABLE ki_voter_history (
    VotingHistoryId INT UNSIGNED NOT NULL,
    CountyCode VARCHAR(2) DEFAULT '' NOT NULL, -- 2 letter county code
    CountyCode_Voting VARCHAR(2) DEFAULT '' NOT NULL, -- same
    StateVoterID INT UNSIGNED NOT NULL,
    ElectionDate VARCHAR(8) DEFAULT '' NOT NULL, -- YYYYMMDD

    PRIMARY KEY(VotingHistoryId)
)