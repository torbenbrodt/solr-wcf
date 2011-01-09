DROP TABLE IF EXISTS wcf1_solr_reindex;
CREATE TABLE wcf1_solr_reindex (
	status tinyint NOT NULL,
	typeID SMALLINT NOT NULL,
	messageID INT(10) NOT NULL,
	PRIMARY KEY (status, typeID, messageID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
