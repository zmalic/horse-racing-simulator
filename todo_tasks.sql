DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL,
  `description` varchar(300) DEFAULT '',
  `priority` tinyint(4) NOT NULL,
  `done` tinyint(4) NOT NULL DEFAULT '0',
  `dueDate` date NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `completedAt` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT '0',
  `deletedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
