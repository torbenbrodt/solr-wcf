DROP TABLE IF EXISTS wcf1_solr_index;
CREATE TABLE wcf1_solr_index (
	typeID SMALLINT NOT NULL,
	messageID INT(10) NOT NULL,
	PRIMARY KEY (typeID, messageID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
