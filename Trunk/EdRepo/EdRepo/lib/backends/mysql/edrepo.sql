-- MySQL dump 10.13  Distrib 5.1.46, for mandriva-linux-gnu (x86_64)
--
-- Host: localhost    Database: edrepo
-- ------------------------------------------------------
-- Server version	5.1.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS 'moduleauthors';
CREATE TABLE `moduleauthors` (
	  `ModuleID` int(11) NOT NULL,
	  `AuthorName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(45) DEFAULT NULL,
  `Description` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currentlogins`
--

DROP TABLE IF EXISTS `currentlogins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currentlogins` (
  `CurrentLoginID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `AuthenticationToken` bigint(20) NOT NULL,
  `Expires` datetime NOT NULL,
  PRIMARY KEY (`CurrentLoginID`),
  UNIQUE KEY `AuthenticationToken` (`AuthenticationToken`),
  KEY `fk_currentlogins_1` (`UserID`),
  CONSTRAINT `fk_currentlogins_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currentlogins`
--

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emails` (
  `EmailID` int(10) NOT NULL AUTO_INCREMENT,
  `Subject` varchar(100) NOT NULL,
  `Message` longtext NOT NULL,
  PRIMARY KEY (`EmailID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emails`
--

LOCK TABLES `emails` WRITE;
/*!40000 ALTER TABLE `emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materialcomments`
--

DROP TABLE IF EXISTS `materialcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materialcomments` (
  `MaterialID` int(10) NOT NULL,
  `Comments` varchar(1000) NOT NULL,
  `Subject` varchar(250) NOT NULL,
  `Date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Rating` double NOT NULL,
  `Author` varchar(50) NOT NULL,
  `NumberOfRatings` int(11) DEFAULT NULL,
  KEY `fk_materialcomments_1` (`MaterialID`),
  CONSTRAINT `fk_materialcomments_1` FOREIGN KEY (`MaterialID`) REFERENCES `materials` (`MaterialID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materialcomments`
--

LOCK TABLES `materialcomments` WRITE;
/*!40000 ALTER TABLE `materialcomments` DISABLE KEYS */;
/*!40000 ALTER TABLE `materialcomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials` (
  `MaterialID` int(10) NOT NULL AUTO_INCREMENT,
  `LinkToMaterial` varchar(200) NOT NULL,
  `LinkType` enum('LocalFile','ExternalURL') NOT NULL,
  `ReadableFileName` varchar(50) NOT NULL,
  `Rating` double NOT NULL DEFAULT '0',
  `NumberOfRatings` int(10) unsigned zerofill NOT NULL DEFAULT '0000000000',
  `AccessFlag` int(10) NOT NULL DEFAULT '-1',
  `Type` enum('NotSpecified','Collection','Dataset','Event','Image','InteractiveResource','MovingImage','PhysicalObject','Service','Software','Sound','StillImage','Text') DEFAULT 'NotSpecified',
  `Title` varchar(45) DEFAULT NULL,
  `Rights` text,
  `Language` varchar(25) DEFAULT NULL,
  `Publisher` varchar(45) DEFAULT NULL,
  `Description` text,
  `Creator` varchar(45) DEFAULT NULL,
  `Date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MaterialID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module`
--

DROP TABLE IF EXISTS `module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module` (
  `ModuleID` int(10) NOT NULL AUTO_INCREMENT,
  `Date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Abstract` longtext NOT NULL,
  `LectureSize` varchar(150) DEFAULT NULL,
  `LabSize` varchar(150) DEFAULT NULL,
  `ExerciseSize` varchar(150) DEFAULT NULL,
  `HomeworkSize` varchar(150) DEFAULT NULL,
  `OtherSize` varchar(150) DEFAULT NULL,
  `AuthorComments` longtext,
  `Status` enum('InProgress','PendingModeration','Active','Locked') NOT NULL DEFAULT 'InProgress',
  `MinimumUserType` enum('Unregistered','Viewer','SuperViewer','Submitter','Editor','Admin') NOT NULL DEFAULT 'Viewer',
  `BaseID` int(10) NOT NULL,
  `Version` int(10) NOT NULL,
  `SubmitterUserID` int(11) DEFAULT NULL,
  `CheckInComments` longtext NOT NULL,
  PRIMARY KEY (`ModuleID`),
  UNIQUE KEY `UQ_Module_1__53` (`ModuleID`),
  KEY `FK_Module_ModuleBases` (`BaseID`),
  KEY `fk_module_1` (`SubmitterUserID`),
  CONSTRAINT `fk_module_1` FOREIGN KEY (`SubmitterUserID`) REFERENCES `users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_Module_ModuleBases` FOREIGN KEY (`BaseID`) REFERENCES `modulebases` (`BaseID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module`
--

LOCK TABLES `module` WRITE;
/*!40000 ALTER TABLE `module` DISABLE KEYS */;
/*!40000 ALTER TABLE `module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulebases`
--

DROP TABLE IF EXISTS `modulebases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulebases` (
  `BaseID` int(10) NOT NULL AUTO_INCREMENT,
  `Title` varchar(300) NOT NULL,
  `ModuleIdentifier` varchar(35) DEFAULT NULL,
  PRIMARY KEY (`BaseID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulebases`
--

LOCK TABLES `modulebases` WRITE;
/*!40000 ALTER TABLE `modulebases` DISABLE KEYS */;
/*!40000 ALTER TABLE `modulebases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulecatagories`
--

DROP TABLE IF EXISTS `modulecatagories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulecatagories` (
  `ModuleID` int(10) NOT NULL,
  `CategoryID` int(10) NOT NULL,
  KEY `FK_SEEKCategories_Module` (`ModuleID`),
  CONSTRAINT `FK_SEEKCategories_Module` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulecatagories`
--

LOCK TABLES `modulecatagories` WRITE;
/*!40000 ALTER TABLE `modulecatagories` DISABLE KEYS */;
/*!40000 ALTER TABLE `modulecatagories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulelog`
--

DROP TABLE IF EXISTS `modulelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulelog` (
  `ModuleID` int(10) NOT NULL,
  `Message` longtext NOT NULL,
  `UserID` int(11) NOT NULL,
  KEY `FK_ModuleLog_Module` (`ModuleID`),
  CONSTRAINT `FK_ModuleLog_Module` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulelog`
--

LOCK TABLES `modulelog` WRITE;
/*!40000 ALTER TABLE `modulelog` DISABLE KEYS */;
/*!40000 ALTER TABLE `modulelog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulematerialslink`
--

DROP TABLE IF EXISTS `modulematerialslink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulematerialslink` (
  `ModuleID` int(10) NOT NULL,
  `MaterialID` int(10) NOT NULL,
  `OrderID` int(10) NOT NULL,
  PRIMARY KEY (`ModuleID`,`MaterialID`),
  KEY `IX_ModuleMaterialsLink_OrderID` (`OrderID`),
  KEY `FK_5ccc512d-bef0-47f8-9df6-bbdceb0f07e7` (`MaterialID`),
  CONSTRAINT `FK_5ccc512d-bef0-47f8-9df6-bbdceb0f07e7` FOREIGN KEY (`MaterialID`) REFERENCES `materials` (`MaterialID`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_ca02cfd5-73b1-4089-98fb-364863711387` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulematerialslink`
--

LOCK TABLES `modulematerialslink` WRITE;
/*!40000 ALTER TABLE `modulematerialslink` DISABLE KEYS */;
/*!40000 ALTER TABLE `modulematerialslink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `moduleratings`
--

DROP TABLE IF EXISTS `moduleratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moduleratings` (
  `ModuleID` int(10) NOT NULL,
  `Rating` double NOT NULL DEFAULT '0',
  `NumRatings` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ModuleID`),
  CONSTRAINT `FK_ModuleRatings_Module` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moduleratings`
--

LOCK TABLES `moduleratings` WRITE;
/*!40000 ALTER TABLE `moduleratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `moduleratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `objectives`
--

DROP TABLE IF EXISTS `objectives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `objectives` (
  `ModuleID` int(10) NOT NULL,
  `ObjectiveText` varchar(300) NOT NULL,
  `OrderID` int(10) NOT NULL,
  PRIMARY KEY (`ModuleID`,`ObjectiveText`,`OrderID`),
  KEY `IX_Objectives_OrderID` (`OrderID`),
  CONSTRAINT `FK_8d810522-aa37-4faa-be9b-6adf2a332aea` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `objectives`
--

LOCK TABLES `objectives` WRITE;
/*!40000 ALTER TABLE `objectives` DISABLE KEYS */;
/*!40000 ALTER TABLE `objectives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otherresources`
--

DROP TABLE IF EXISTS `otherresources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otherresources` (
  `ModuleID` int(10) NOT NULL,
  `Description` varchar(400) NOT NULL,
  `ResourceLink` varchar(200) DEFAULT NULL,
  `OrderID` int(10) NOT NULL,
  PRIMARY KEY (`ModuleID`,`OrderID`),
  KEY `IX_OtherResources_OrderID` (`OrderID`),
  CONSTRAINT `FK_c0d00c32-bb7a-47af-8c68-dd5fd47c383c` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otherresources`
--

LOCK TABLES `otherresources` WRITE;
/*!40000 ALTER TABLE `otherresources` DISABLE KEYS */;
/*!40000 ALTER TABLE `otherresources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prereqs`
--

DROP TABLE IF EXISTS `prereqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prereqs` (
  `ModuleID` int(10) NOT NULL,
  `PrerequisiteText` varchar(200) NOT NULL,
  `OrderID` int(10) NOT NULL,
  PRIMARY KEY (`ModuleID`,`OrderID`),
  KEY `IX_Prereqs_OrderID` (`OrderID`),
  CONSTRAINT `FK_37490761-1077-44b7-9a56-8ce3d8eb4e93` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prereqs`
--

LOCK TABLES `prereqs` WRITE;
/*!40000 ALTER TABLE `prereqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `prereqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seealso`
--

DROP TABLE IF EXISTS `seealso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seealso` (
  `ModuleID` int(10) DEFAULT NULL,
  `Description` varchar(200) DEFAULT NULL,
  `ReferencedModuleID` int(11) DEFAULT NULL,
  `OrderID` int(10) DEFAULT NULL,
  KEY `FK_SeeAlso_Module` (`ModuleID`),
  CONSTRAINT `FK_SeeAlso_Module` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seealso`
--

LOCK TABLES `seealso` WRITE;
/*!40000 ALTER TABLE `seealso` DISABLE KEYS */;
/*!40000 ALTER TABLE `seealso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topics` (
  `ModuleID` int(10) NOT NULL,
  `TopicText` varchar(200) NOT NULL,
  `OrderID` int(10) NOT NULL,
  PRIMARY KEY (`ModuleID`,`OrderID`),
  KEY `IX_Topics_OrderID` (`OrderID`),
  CONSTRAINT `FK_ba105067-1457-4ea3-8313-a7036091e2ea` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topics`
--

LOCK TABLES `topics` WRITE;
/*!40000 ALTER TABLE `topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(45) DEFAULT NULL,
  `LastName` varchar(45) DEFAULT NULL,
  `Email` varchar(45) DEFAULT NULL,
  `Password` varchar(45) DEFAULT NULL,
  `Type` enum('Viewer','SuperViewer','Submitter','Editor','Admin','Disabled','Deleted','Pending') DEFAULT 'Viewer',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','User','admin@admin','admin','Admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-08-09 11:33:22
