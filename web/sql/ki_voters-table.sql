-- List of registered voters, field names are from the
-- "Washington State Voter Registration Database"
CREATE TABLE ki_voters (
    StateVoterID INT UNSIGNED NOT NULL,
    FName VARCHAR(255) DEFAULT '' NOT NULL,
    MName VARCHAR(255) DEFAULT '' NOT NULL,
    LName VARCHAR(255) DEFAULT '' NOT NULL,
    NameSuffix VARCHAR(255) DEFAULT '' NOT NULL,
    YearofBirth INT UNSIGNED NOT NULL, -- YYYY
    Gender VARCHAR(1) DEFAULT '' NOT NULL, -- 'M', 'F', 'U', or 'O'
    RegStNum VARCHAR(255) DEFAULT '' NOT NULL,
    RegStFrac VARCHAR(255) DEFAULT '' NOT NULL,
    RegStName VARCHAR(255) DEFAULT '' NOT NULL,
    RegStType VARCHAR(255) DEFAULT '' NOT NULL,
    RegUnitType VARCHAR(255) DEFAULT '' NOT NULL,
    RegStPreDirection VARCHAR(255) DEFAULT '' NOT NULL,
    RegStPostDirection VARCHAR(255) DEFAULT '' NOT NULL,
    RegUnitNum VARCHAR(255) DEFAULT '' NOT NULL,
    RegCity VARCHAR(255) DEFAULT '' NOT NULL,
    RegState VARCHAR(255) DEFAULT '' NOT NULL,
    RegZipCode VARCHAR(255) DEFAULT '' NOT NULL,
    CountyCode VARCHAR(2) DEFAULT '' NOT NULL, -- 2 letter codes
    PrecinctCode VARCHAR(255) DEFAULT '' NOT NULL,
    PrecinctPart VARCHAR(255) DEFAULT '' NOT NULL,
    LegislativeDistrict INT UNSIGNED NOT NULL, -- seem to always be numbers
    CongressionalDistrict INT UNSIGNED NOT NULL, -- same
    Mail1 VARCHAR(255) DEFAULT '' NOT NULL,
    Mail2 VARCHAR(255) DEFAULT '' NOT NULL,
    Mail3 VARCHAR(255) DEFAULT '' NOT NULL,
    MailCity VARCHAR(255) DEFAULT '' NOT NULL,
    MailZip VARCHAR(255) DEFAULT '' NOT NULL,
    MailState VARCHAR(255) DEFAULT '' NOT NULL,
    MailCountry VARCHAR(255) DEFAULT '' NOT NULL,
    Registrationdate VARCHAR(8) DEFAULT '' NOT NULL, -- YYYYMMDD
    LastVoted VARCHAR(8) DEFAULT '' NOT NULL, -- YYYYMMDD
    StatusCode VARCHAR(18) DEFAULT '' NOT NULL, -- longest status is 18

    PRIMARY KEY(StateVoterID)
)