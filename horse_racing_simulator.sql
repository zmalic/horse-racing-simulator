SET NAMES utf8 ;

--
-- Table structure for table `horse`
--

DROP TABLE IF EXISTS `horse`;
 SET character_set_client = utf8mb4 ;
CREATE TABLE `horse` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `horseName` varchar(20) NOT NULL,
  `speed` float unsigned NOT NULL,
  `strength` float unsigned NOT NULL,
  `endurance` float unsigned NOT NULL,
  `inRace` tinyint(4) unsigned zerofill NOT NULL,
  `deleted` tinyint(4) unsigned zerofill NOT NULL DEFAULT 0,
  `deletedAt` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
--
-- Dumping data for table `horse`
--

LOCK TABLES `horse` WRITE;
INSERT INTO `horse` VALUES (1,'Hero',7.789,2.9,8.1,0,0,NULL),(2,'Quick Stallion',6.21,7.7,2.19,0,0,NULL),(3,'Rage',1.188,7.4,9.9,0,0,NULL),(4,'Edgy',3.11,8.12,3.4,0,0,NULL),(5,'Pericles',3.4,7.9,2.6,0,0,NULL),(6,'Dalton',7.2,4.7,4.7,0,0,NULL),(7,'Shade Sparks',1.8,8,7.9,0,0,NULL),(8,'Kashmire',9.1,1.98,3.6,0,0,NULL),(9,'Sparkleheart',4.87,3.5,7.3,0,0,NULL),(10,'Autumn',3.67,7.17,8.2,0,0,NULL),(11,'Heath',7.9,3.18,3.1,0,0,NULL),(12,'Moonlight',6,2.5,6.234,0,0,NULL),(13,'Nobleflame',9.42,7.09,8.3,0,0,NULL),(14,'Misty',9.44,5.01,7.11,0,0,NULL),(15,'Flicker',3.6,2.234,4.7,0,0,NULL),(16,'Trapper',2.15,4.78,5.098,0,0,NULL),(17,'Lucus',8.15,7.8,5.2,0,0,NULL),(18,'Flight Stallion',5.5,8.1,1.111,0,0,NULL),(19,'Lightningmane',0.3,4.98,2.765,0,0,NULL),(20,'Ginger',0.9,3.1,5.7,0,0,NULL),(21,'Sparklefeet',5.2,7.1,1.33,0,0,NULL),(22,'Nobleflash',5.3,6.6,5.77,0,0,NULL),(23,'Jackpot',7.1,9.123,8.2,0,0,NULL),(24,'Inka',0.2,6.896,4.8,0,0,NULL),(25,'Moon Sparks',4.6,5.678,1,0,0,NULL);
UNLOCK TABLES;

--
-- Table structure for table `horseInRace`
--

DROP TABLE IF EXISTS `horseInRace`;
 SET character_set_client = utf8mb4 ;
CREATE TABLE `horseInRace` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `raceId` int(10) unsigned DEFAULT NULL,
  `horseId` int(10) unsigned DEFAULT NULL,
  `metersCrossed` float unsigned zerofill NOT NULL DEFAULT 0,
  `finishTime` float unsigned zerofill NOT NULL DEFAULT 0,
  `deleted` tinyint(4) unsigned zerofill NOT NULL DEFAULT 0,
  `deletedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `horse_in_race_idx` (`horseId`),
  KEY `horse_in_race_race_idx` (`raceId`),
  CONSTRAINT `horse_in_race_horse` FOREIGN KEY (`horseId`) REFERENCES `horse` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `horse_in_race_race` FOREIGN KEY (`raceId`) REFERENCES `race` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `race`
--

DROP TABLE IF EXISTS `race`;
 SET character_set_client = utf8mb4 ;
CREATE TABLE `race` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned zerofill NOT NULL,
  `finished` tinyint(4) unsigned zerofill NOT NULL,
  `deleted` tinyint(4) unsigned zerofill DEFAULT 0,
  `deletedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;