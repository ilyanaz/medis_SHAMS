-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: medisSHAMS
-- ------------------------------------------------------
-- Server version	8.0.45

CREATE DATABASE IF NOT EXISTS `medisSHAMS` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medisSHAMS`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `annual_audiograph`
--

DROP TABLE IF EXISTS `annual_audiograph`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `annual_audiograph` (
  `annualAudio_id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `audiometry_id` int DEFAULT NULL,
  `baselineAudio_id` int DEFAULT NULL,
  `R_250` int DEFAULT NULL,
  `L_250` int DEFAULT NULL,
  `bone_R250` int DEFAULT NULL,
  `bone_L250` int DEFAULT NULL,
  `R_500` int DEFAULT NULL,
  `L_500` int DEFAULT NULL,
  `bone_R500` int DEFAULT NULL,
  `bone_L500` int DEFAULT NULL,
  `R_1k` int DEFAULT NULL,
  `L_1k` int DEFAULT NULL,
  `bone_R1k` int DEFAULT NULL,
  `bone_L1k` int DEFAULT NULL,
  `R_2k` int DEFAULT NULL,
  `L_2k` int DEFAULT NULL,
  `bone_R2k` int DEFAULT NULL,
  `bone_L2k` int DEFAULT NULL,
  `R_3k` int DEFAULT NULL,
  `L_3k` int DEFAULT NULL,
  `bone_R3k` int DEFAULT NULL,
  `bone_L3k` int DEFAULT NULL,
  `R_4k` int DEFAULT NULL,
  `L_4k` int DEFAULT NULL,
  `bone_R4k` int DEFAULT NULL,
  `bone_L4k` int DEFAULT NULL,
  `R_6k` int DEFAULT NULL,
  `L_6k` int DEFAULT NULL,
  `bone_R6k` int DEFAULT NULL,
  `bone_L6k` int DEFAULT NULL,
  `R_8k` int DEFAULT NULL,
  `L_8k` int DEFAULT NULL,
  `bone_R8k` int DEFAULT NULL,
  `bone_L8k` int DEFAULT NULL,
  PRIMARY KEY (`annualAudio_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annual_audiograph`
--

LOCK TABLES `annual_audiograph` WRITE;
/*!40000 ALTER TABLE `annual_audiograph` DISABLE KEYS */;
INSERT INTO `annual_audiograph` VALUES (1,3,2,1,1,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10),(2,3,2,2,2,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10,15,15,10,10);
/*!40000 ALTER TABLE `annual_audiograph` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audio_comments`
--

DROP TABLE IF EXISTS `audio_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audio_comments` (
  `audioComments_id` int unsigned NOT NULL AUTO_INCREMENT,
  `STS_right` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `STS_left` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `average1_right` decimal(8,2) DEFAULT NULL,
  `average2_right` decimal(8,2) DEFAULT NULL,
  `average1_left` decimal(8,2) DEFAULT NULL,
  `average2_left` decimal(8,2) DEFAULT NULL,
  `standard_analysis` text COLLATE utf8mb4_unicode_ci,
  `audio_recommendation` text COLLATE utf8mb4_unicode_ci,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `employee_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `audiometry_id` int DEFAULT NULL,
  `annualAudio_id` int DEFAULT NULL,
  `baselineAudio_id` int DEFAULT NULL,
  PRIMARY KEY (`audioComments_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audio_comments`
--

LOCK TABLES `audio_comments` WRITE;
/*!40000 ALTER TABLE `audio_comments` DISABLE KEYS */;
INSERT INTO `audio_comments` VALUES (1,'No','No',15.00,15.00,15.00,15.00,'Normal findings from local submit test','Continue annual review','Local audiometry save test',3,1,2,1,1,1),(2,'No','No',15.00,15.00,15.00,15.00,'Normal findings from local submit test','Continue annual review','Local audiometry save test',3,1,2,2,2,2);
/*!40000 ALTER TABLE `audio_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audiometry_pastmedical`
--

DROP TABLE IF EXISTS `audiometry_pastmedical`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audiometry_pastmedical` (
  `audioPastMedical_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ear_infections` tinyint(1) DEFAULT NULL,
  `head_injury` tinyint(1) DEFAULT NULL,
  `ototoxic_drugs` tinyint(1) DEFAULT NULL,
  `prev_earSurgery` tinyint(1) DEFAULT NULL,
  `pre_noiseExposure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `significant_hobbies` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seg` int DEFAULT NULL,
  `otoscopy` tinyint(1) DEFAULT NULL,
  `audio_rinneRight` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio_rinneLeft` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio_weber` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_audiogram` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exposure_lex` decimal(8,2) DEFAULT NULL,
  `peakExposure_Lpeak` decimal(8,2) DEFAULT NULL,
  `maxExposure_Lmax` decimal(8,2) DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  `audiometry_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  PRIMARY KEY (`audioPastMedical_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audiometry_pastmedical`
--

LOCK TABLES `audiometry_pastmedical` WRITE;
/*!40000 ALTER TABLE `audiometry_pastmedical` DISABLE KEYS */;
INSERT INTO `audiometry_pastmedical` VALUES (1,0,0,0,0,'Yes','No',1,1,'Positive','Positive','Center','Annual',85.50,0.00,0.00,3,2,2);
/*!40000 ALTER TABLE `audiometry_pastmedical` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audiometry_test`
--

DROP TABLE IF EXISTS `audiometry_test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audiometry_test` (
  `audiometry_id` int unsigned NOT NULL AUTO_INCREMENT,
  `audioTest_date` date DEFAULT NULL,
  `total_years_working` int DEFAULT NULL,
  `noYears_working` int DEFAULT NULL,
  `audiometer` int DEFAULT NULL,
  `calibration_date` date DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  PRIMARY KEY (`audiometry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audiometry_test`
--

LOCK TABLES `audiometry_test` WRITE;
/*!40000 ALTER TABLE `audiometry_test` DISABLE KEYS */;
INSERT INTO `audiometry_test` VALUES (1,'2026-04-02',5,2,1,'2026-04-02',2,3),(2,'2026-04-02',5,2,1,'2026-04-02',2,3);
/*!40000 ALTER TABLE `audiometry_test` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `baseline_audiograph`
--

DROP TABLE IF EXISTS `baseline_audiograph`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `baseline_audiograph` (
  `baselineAudio_id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `audiometry_id` int DEFAULT NULL,
  `R_250` int DEFAULT NULL,
  `L_250` int DEFAULT NULL,
  `bone_R250` int DEFAULT NULL,
  `bone_L250` int DEFAULT NULL,
  `R_500` int DEFAULT NULL,
  `L_500` int DEFAULT NULL,
  `bone_R500` int DEFAULT NULL,
  `bone_L500` int DEFAULT NULL,
  `R_1k` int DEFAULT NULL,
  `L_1k` int DEFAULT NULL,
  `bone_R1k` int DEFAULT NULL,
  `bone_L1k` int DEFAULT NULL,
  `R_2k` int DEFAULT NULL,
  `L_2k` int DEFAULT NULL,
  `bone_R2k` int DEFAULT NULL,
  `bone_L2k` int DEFAULT NULL,
  `R_3k` int DEFAULT NULL,
  `L_3k` int DEFAULT NULL,
  `bone_R3k` int DEFAULT NULL,
  `bone_L3k` int DEFAULT NULL,
  `R_4k` int DEFAULT NULL,
  `L_4k` int DEFAULT NULL,
  `bone_R4k` int DEFAULT NULL,
  `bone_L4k` int DEFAULT NULL,
  `R_6k` int DEFAULT NULL,
  `L_6k` int DEFAULT NULL,
  `bone_R6k` int DEFAULT NULL,
  `bone_L6k` int DEFAULT NULL,
  `R_8k` int DEFAULT NULL,
  `L_8k` int DEFAULT NULL,
  `bone_R8k` int DEFAULT NULL,
  `bone_L8k` int DEFAULT NULL,
  PRIMARY KEY (`baselineAudio_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `baseline_audiograph`
--

LOCK TABLES `baseline_audiograph` WRITE;
/*!40000 ALTER TABLE `baseline_audiograph` DISABLE KEYS */;
INSERT INTO `baseline_audiograph` VALUES (1,3,2,1,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5),(2,3,2,2,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5,10,10,5,5);
/*!40000 ALTER TABLE `baseline_audiograph` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biological_monitoring`
--

DROP TABLE IF EXISTS `biological_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `biological_monitoring` (
  `bioMonitor_id` int NOT NULL AUTO_INCREMENT,
  `biological_exposure` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `baseline_results` text COLLATE utf8mb4_general_ci,
  `baseline_annual` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`bioMonitor_id`),
  KEY `fk_biomonitor_employee` (`employee_id`),
  KEY `fk_biomonitor_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_biomonitor_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_biomonitor_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biological_monitoring`
--

LOCK TABLES `biological_monitoring` WRITE;
/*!40000 ALTER TABLE `biological_monitoring` DISABLE KEYS */;
INSERT INTO `biological_monitoring` VALUES (1,NULL,'Lead::10','12',3,3);
/*!40000 ALTER TABLE `biological_monitoring` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chemical_information`
--

DROP TABLE IF EXISTS `chemical_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chemical_information` (
  `surveillance_id` int NOT NULL AUTO_INCREMENT,
  `chemicals` text COLLATE utf8mb4_general_ci,
  `examination_type` enum('Pre-Placement','Periodic','Return to Work','Exit') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `examination_date` date DEFAULT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `company_id` int DEFAULT NULL,
  PRIMARY KEY (`surveillance_id`),
  KEY `fk_chemical_employee` (`employee_id`),
  KEY `fk_chemical_doctor` (`doctor_id`),
  CONSTRAINT `fk_chemical_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_chemical_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chemical_information`
--

LOCK TABLES `chemical_information` WRITE;
/*!40000 ALTER TABLE `chemical_information` DISABLE KEYS */;
INSERT INTO `chemical_information` VALUES (1,NULL,NULL,NULL,'Codex Test Company',3,1,2),(2,'Lead (Inorganic & Organic)','Pre-Placement','2026-04-02','Codex Test Company',3,1,2),(3,'Lead (Inorganic & Organic)','Pre-Placement','2026-04-02','Codex Test Company',3,1,2);
/*!40000 ALTER TABLE `chemical_information` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic`
--

DROP TABLE IF EXISTS `clinic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic` (
  `clinic_id` int NOT NULL AUTO_INCREMENT,
  `clinic_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `clinic_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_postcode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_telephone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_fax` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_username` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinic_password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`clinic_id`),
  UNIQUE KEY `clinic_username` (`clinic_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic`
--

LOCK TABLES `clinic` WRITE;
/*!40000 ALTER TABLE `clinic` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_findings`
--

DROP TABLE IF EXISTS `clinical_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_findings` (
  `chHistory_id` int NOT NULL AUTO_INCREMENT,
  `result_clinical_findings` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elaboration` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`chHistory_id`),
  KEY `fk_clinical_employee` (`employee_id`),
  KEY `fk_clinical_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_clinical_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_findings`
--

LOCK TABLES `clinical_findings` WRITE;
/*!40000 ALTER TABLE `clinical_findings` DISABLE KEYS */;
INSERT INTO `clinical_findings` VALUES (1,NULL,NULL,3,3);
/*!40000 ALTER TABLE `clinical_findings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company` (
  `company_id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `mykpp_registration_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_postcode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_telephone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_fax` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_workers` int DEFAULT '0',
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES (1,'Codex Test Company 20260402150254','REG-20260402150254','Test Address','50450','Kuala Lumpur','Wilayah Persekutuan','+60 123456789','codex20260402150254@example.com','1234567',0),(2,'Codex Test Company',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `declaration`
--

DROP TABLE IF EXISTS `declaration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `declaration` (
  `declaration_id` int NOT NULL AUTO_INCREMENT,
  `employee_signature` text COLLATE utf8mb4_general_ci,
  `employee_date` date DEFAULT NULL,
  `doctor_signature` text COLLATE utf8mb4_general_ci,
  `doctor_date` date DEFAULT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_firstName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_lastName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `surveillance_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  PRIMARY KEY (`declaration_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `declaration`
--

LOCK TABLES `declaration` WRITE;
/*!40000 ALTER TABLE `declaration` DISABLE KEYS */;
INSERT INTO `declaration` VALUES (1,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p8VXl8AAAAASUVORK5CYII=','2026-04-02','data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p8VXl8AAAAASUVORK5CYII=','2026-04-02',NULL,NULL,NULL,2,1,2,3),(2,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p8VXl8AAAAASUVORK5CYII=','2026-04-02','data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p8VXl8AAAAASUVORK5CYII=','2026-04-02',NULL,NULL,NULL,3,1,2,3);
/*!40000 ALTER TABLE `declaration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor`
--

DROP TABLE IF EXISTS `doctor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor` (
  `doctor_id` int NOT NULL AUTO_INCREMENT,
  `doctor_firstName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_lastName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_NRIC` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_passportNo` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_DOB` date DEFAULT NULL,
  `doctor_gender` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_postcode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_telephone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_fax` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_ethnicity` enum('Malay','Chinese','Indian','Orang Asli','Others') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_citizenship` enum('Malaysian Citizen','Others') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_martialStatus` enum('Single','Married') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `MMC_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `OHD_registrationNo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_username` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doctor_password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_sign` text COLLATE utf8mb4_general_ci,
  `doctor_picture` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`doctor_id`),
  UNIQUE KEY `doctor_username` (`doctor_username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor`
--

LOCK TABLES `doctor` WRITE;
/*!40000 ALTER TABLE `doctor` DISABLE KEYS */;
INSERT INTO `doctor` VALUES (1,'System','Doctor',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'system_doctor','$2y$12$pRqxOInevRToLHCNtTVa6umfUxzUSoFizjZEnNdlXQhoHRQitEzSu','','');
/*!40000 ALTER TABLE `doctor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee` (
  `employee_id` int NOT NULL AUTO_INCREMENT,
  `employee_firstName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `employee_lastName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `employee_NRIC` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_passportNo` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_DOB` date DEFAULT NULL,
  `employee_gender` enum('Male','Female') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_postcode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_telephone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_ethnicity` enum('Malay','Chinese','Indian','Orang Asli','Others') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_citizenship` enum('Malaysian Citizen','Others') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_martialStatus` enum('Single','Married','Others') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_of_children` int DEFAULT '0',
  `years_married` int DEFAULT '0',
  `employee_sign` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES (1,'Codex','Tester20260402150319',NULL,'P20260402150319','1990-01-01','Male','Test Address','50450','Kuala Lumpur','Wilayah Persekutuan','+60 123456789','employee20260402150319@example.com','Malay','Malaysian Citizen','Single',0,0,NULL),(2,'Codex','Tester20260402150355',NULL,'P20260402150355','1990-01-01','Male','Test Address','50450','Kuala Lumpur','Wilayah Persekutuan','+60 123456789','employee20260402150355@example.com','Malay','Malaysian Citizen','Single',0,0,NULL),(3,'Codex','Tester20260402150523',NULL,'P20260402150523','1990-01-01','Male','Test Address','50450','Kuala Lumpur','Wilayah Persekutuan','+60 123456789','employee20260402150523@example.com','Malay','Malaysian Citizen','Single',0,0,NULL);
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fitness_report`
--

DROP TABLE IF EXISTS `fitness_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fitness_report` (
  `fitnessReport_id` int unsigned NOT NULL AUTO_INCREMENT,
  `result` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `employee_id` int DEFAULT NULL,
  `surveillance_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  PRIMARY KEY (`fitnessReport_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fitness_report`
--

LOCK TABLES `fitness_report` WRITE;
/*!40000 ALTER TABLE `fitness_report` DISABLE KEYS */;
INSERT INTO `fitness_report` VALUES (1,NULL,NULL,3,3,2,1);
/*!40000 ALTER TABLE `fitness_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fitness_respirator`
--

DROP TABLE IF EXISTS `fitness_respirator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fitness_respirator` (
  `fitness_id` int NOT NULL AUTO_INCREMENT,
  `fitness_result` enum('Fit','Not Fit') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fitness_justification` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`fitness_id`),
  KEY `fk_fitness_employee` (`employee_id`),
  KEY `fk_fitness_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_fitness_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fitness_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fitness_respirator`
--

LOCK TABLES `fitness_respirator` WRITE;
/*!40000 ALTER TABLE `fitness_respirator` DISABLE KEYS */;
/*!40000 ALTER TABLE `fitness_respirator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `history_of_health`
--

DROP TABLE IF EXISTS `history_of_health`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `history_of_health` (
  `hoh_id` int NOT NULL AUTO_INCREMENT,
  `breathing_difficulty` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cough` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sore_throat` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sneezing` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chest_pain` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `palpitation` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `limb_oedema` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `drowsiness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dizziness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `headache` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `confusion` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lethargy` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nausea` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vomiting` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `eye_irritations` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blurred_vision` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blisters` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `burns` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itching` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rash` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `redness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `abdominal_pain` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `abdominal_mass` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jaundice` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diarrhoea` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `loss_of_weight` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `loss_of_appetite` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dysuria` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `haematuria` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `others_symptoms` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`hoh_id`),
  KEY `fk_hoh_employee` (`employee_id`),
  KEY `fk_hoh_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_hoh_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hoh_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `history_of_health`
--

LOCK TABLES `history_of_health` WRITE;
/*!40000 ALTER TABLE `history_of_health` DISABLE KEYS */;
INSERT INTO `history_of_health` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,3);
/*!40000 ALTER TABLE `history_of_health` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_history`
--

DROP TABLE IF EXISTS `medical_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_history` (
  `medHistory_id` int NOT NULL AUTO_INCREMENT,
  `diagnosed_history` text COLLATE utf8mb4_general_ci,
  `medication_history` text COLLATE utf8mb4_general_ci,
  `admitted_history` text COLLATE utf8mb4_general_ci,
  `family_history` text COLLATE utf8mb4_general_ci,
  `others_history` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int DEFAULT NULL,
  PRIMARY KEY (`medHistory_id`),
  KEY `fk_medhist_employee` (`employee_id`),
  KEY `fk_medhist_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_medhist_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_medhist_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_history`
--

LOCK TABLES `medical_history` WRITE;
/*!40000 ALTER TABLE `medical_history` DISABLE KEYS */;
INSERT INTO `medical_history` VALUES (1,'None','None','None','None','None',3,NULL);
/*!40000 ALTER TABLE `medical_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ms_findings`
--

DROP TABLE IF EXISTS `ms_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ms_findings` (
  `msFindings_id` int NOT NULL AUTO_INCREMENT,
  `history_of_health` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinical_findings` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CF_work_related` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_organ` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TO_work_related` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `biological_monitoring` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `BM_work_related` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pregnancy_breastFeding` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conclusion_fitness` enum('Fit','Not Fit') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`msFindings_id`),
  KEY `fk_ms_employee` (`employee_id`),
  KEY `fk_ms_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_ms_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ms_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ms_findings`
--

LOCK TABLES `ms_findings` WRITE;
/*!40000 ALTER TABLE `ms_findings` DISABLE KEYS */;
INSERT INTO `ms_findings` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,3);
/*!40000 ALTER TABLE `ms_findings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `occupational_history`
--

DROP TABLE IF EXISTS `occupational_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `occupational_history` (
  `occupHistory_id` int NOT NULL AUTO_INCREMENT,
  `job_title` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employment_duration` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chemical_exposure_duration` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chemical_exposure_incidents` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int DEFAULT NULL,
  PRIMARY KEY (`occupHistory_id`),
  KEY `fk_occup_employee` (`employee_id`),
  KEY `fk_occup_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_occup_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_occup_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `occupational_history`
--

LOCK TABLES `occupational_history` WRITE;
/*!40000 ALTER TABLE `occupational_history` DISABLE KEYS */;
INSERT INTO `occupational_history` VALUES (1,'Operator','Codex Test Company','1 year','1 year','None',3,NULL);
/*!40000 ALTER TABLE `occupational_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_social_history`
--

DROP TABLE IF EXISTS `personal_social_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_social_history` (
  `perSocHistory_id` int NOT NULL AUTO_INCREMENT,
  `smoking_history` enum('Current','Ex-smoker','Non-smoker') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `years_of_smoking` int DEFAULT '0',
  `no_of_cigarettes` int DEFAULT '0',
  `vaping_history` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `years_of_vaping` int DEFAULT '0',
  `hobby` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int DEFAULT NULL,
  PRIMARY KEY (`perSocHistory_id`),
  KEY `fk_persoc_employee` (`employee_id`),
  KEY `fk_persoc_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_persoc_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_persoc_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_social_history`
--

LOCK TABLES `personal_social_history` WRITE;
/*!40000 ALTER TABLE `personal_social_history` DISABLE KEYS */;
INSERT INTO `personal_social_history` VALUES (1,'Non-smoker',0,0,'No',0,'Reading',3,NULL);
/*!40000 ALTER TABLE `personal_social_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physical_examination`
--

DROP TABLE IF EXISTS `physical_examination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physical_examination` (
  `pexamHistory_id` int NOT NULL AUTO_INCREMENT,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `BMI` decimal(5,2) DEFAULT NULL,
  `bp_systolic` int DEFAULT NULL,
  `bp_distolic` int DEFAULT NULL,
  `pulse_rate` int DEFAULT NULL,
  `respiratory_rate` int DEFAULT NULL,
  `general_appearances` text COLLATE utf8mb4_general_ci,
  `s1_s2` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `murmur` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ear_nose_throat` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visual_acuity_right` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visual_acuity_left` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `colour_blindness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gas_tenderness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `abdominal_mass` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lymph_nodes` enum('Palpable','Non-palpable') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `splenomegaly` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kidney_tenderness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ballotable` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jaundice` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hepatomegaly` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `muscle_tone` enum('1','2','3','4','5') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `muscle_tenderness` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `power` enum('1','2','3','4','5') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sensation` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sound` enum('Clear','Rhonchi','Crepitus') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `air_entry` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reproductive` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `skin` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `others` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`pexamHistory_id`),
  KEY `fk_pexam_employee` (`employee_id`),
  KEY `fk_pexam_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_pexam_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pexam_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_examination`
--

LOCK TABLES `physical_examination` WRITE;
/*!40000 ALTER TABLE `physical_examination` DISABLE KEYS */;
INSERT INTO `physical_examination` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,3);
/*!40000 ALTER TABLE `physical_examination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recommendation`
--

DROP TABLE IF EXISTS `recommendation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recommendation` (
  `recommendation_id` int NOT NULL AUTO_INCREMENT,
  `recommencation_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `MRPdate_start` date DEFAULT NULL,
  `MRPdate_end` date DEFAULT NULL,
  `nextReview_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`recommendation_id`),
  KEY `fk_recommend_employee` (`employee_id`),
  KEY `fk_recommend_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_recommend_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recommend_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recommendation`
--

LOCK TABLES `recommendation` WRITE;
/*!40000 ALTER TABLE `recommendation` DISABLE KEYS */;
INSERT INTO `recommendation` VALUES (1,NULL,NULL,NULL,NULL,NULL,3,3);
/*!40000 ALTER TABLE `recommendation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `removal_report`
--

DROP TABLE IF EXISTS `removal_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `removal_report` (
  `removalReport_id` int unsigned NOT NULL AUTO_INCREMENT,
  `removal_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reasons_recommendations` text COLLATE utf8mb4_unicode_ci,
  `fitnessReport_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  `surveillance_id` int DEFAULT NULL,
  PRIMARY KEY (`removalReport_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `removal_report`
--

LOCK TABLES `removal_report` WRITE;
/*!40000 ALTER TABLE `removal_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `removal_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `summary_report`
--

DROP TABLE IF EXISTS `summary_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `summary_report` (
  `summaryReport_id` int NOT NULL AUTO_INCREMENT,
  `summary_id` int DEFAULT NULL,
  `totalNo_workplace` int DEFAULT '0',
  `name_of_workUnit` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_exposedWorkers` int DEFAULT '0',
  `totalNo_examined` int DEFAULT '0',
  `chemical_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CHRA_reportNo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indication_CHRAreport` text COLLATE utf8mb4_general_ci,
  `no_ofWorkersNormal_H` int DEFAULT '0',
  `no_ofWorkersNormal_I` int DEFAULT '0',
  `no_ofWorkersNormal_J` int DEFAULT '0',
  `no_ofWorkersNormal_K` int DEFAULT '0',
  `no_ofWorkersAbormal_OccupationalH` int DEFAULT '0',
  `no_ofWorkersAbormal_OccupationalI` int DEFAULT '0',
  `no_ofWorkersAbormal_nonOccupationalI` int DEFAULT '0',
  `no_ofWorkersAbormal_OccupationalJ` int DEFAULT '0',
  `no_ofWorkersAbormal_nonOccupationalJ` int DEFAULT '0',
  `no_ofWorkersAbormal_OccupationalK` int DEFAULT '0',
  `no_ofWorkersAbormal_nonOccupationalK` int DEFAULT '0',
  `no_ofWorkersRecommended_I` int DEFAULT '0',
  `no_ofWorkersRecommended_J` int DEFAULT '0',
  `no_ofWorkersRecommended_K` int DEFAULT '0',
  `specify_J` text COLLATE utf8mb4_general_ci,
  `specify_K` text COLLATE utf8mb4_general_ci,
  `totalNo_MRP` int DEFAULT '0',
  `name_of_laboratoy` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recommendation` text COLLATE utf8mb4_general_ci,
  `decision` enum('Continue MS','Stop MS') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `justification_decision` text COLLATE utf8mb4_general_ci,
  `date_of_implementation` date DEFAULT NULL,
  `employee_id` int NOT NULL,
  `surveillance_id` int DEFAULT NULL,
  `company_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  PRIMARY KEY (`summaryReport_id`),
  KEY `fk_summary_employee` (`employee_id`),
  KEY `fk_summary_company` (`company_id`),
  KEY `fk_summary_doctor` (`doctor_id`),
  CONSTRAINT `fk_summary_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_summary_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_summary_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `summary_report`
--

LOCK TABLES `summary_report` WRITE;
/*!40000 ALTER TABLE `summary_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `summary_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `target_organ`
--

DROP TABLE IF EXISTS `target_organ`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `target_organ` (
  `target_id` int NOT NULL AUTO_INCREMENT,
  `blood_count` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blood_comments` text COLLATE utf8mb4_general_ci,
  `renal_function` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `renal_comments` text COLLATE utf8mb4_general_ci,
  `liver_function` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `liver_comments` text COLLATE utf8mb4_general_ci,
  `chest_xray` enum('Normal','Abnormal') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chest_comments` text COLLATE utf8mb4_general_ci,
  `spirometry_FEV1` decimal(8,2) DEFAULT NULL,
  `spirometry_FVC` decimal(8,2) DEFAULT NULL,
  `spirometry_FEV_FVC` decimal(8,2) DEFAULT NULL,
  `spirometry_comments` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int NOT NULL,
  PRIMARY KEY (`target_id`),
  KEY `fk_target_employee` (`employee_id`),
  KEY `fk_target_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_target_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_target_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `target_organ`
--

LOCK TABLES `target_organ` WRITE;
/*!40000 ALTER TABLE `target_organ` DISABLE KEYS */;
INSERT INTO `target_organ` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,3);
/*!40000 ALTER TABLE `target_organ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_history`
--

DROP TABLE IF EXISTS `training_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_history` (
  `trainingHistory_id` int NOT NULL AUTO_INCREMENT,
  `handling_of_chemical` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chemical_comments` text COLLATE utf8mb4_general_ci,
  `sign_symptoms` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sign_comments` text COLLATE utf8mb4_general_ci,
  `chemical_poisoning` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `poisoning_comments` text COLLATE utf8mb4_general_ci,
  `proper_PPE` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proper_comments` text COLLATE utf8mb4_general_ci,
  `PPE_usage` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usage_comments` text COLLATE utf8mb4_general_ci,
  `employee_id` int NOT NULL,
  `surveillance_id` int DEFAULT NULL,
  PRIMARY KEY (`trainingHistory_id`),
  KEY `fk_training_employee` (`employee_id`),
  KEY `fk_training_surveillance` (`surveillance_id`),
  CONSTRAINT `fk_training_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_training_surveillance` FOREIGN KEY (`surveillance_id`) REFERENCES `chemical_information` (`surveillance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_history`
--

LOCK TABLES `training_history` WRITE;
/*!40000 ALTER TABLE `training_history` DISABLE KEYS */;
INSERT INTO `training_history` VALUES (1,'Yes','Understands process','Yes','Understands symptoms','Yes','Understands poisoning','Yes','Knows PPE','Yes','Uses PPE',3,NULL);
/*!40000 ALTER TABLE `training_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('Admin','Doctor','Clinic') COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@medis.com.my','$2y$12$4wqLE44Gs.W9xaCQyqkRlu86hQyoby504R8bX.PaSeoEL6UO5RnMC','Admin');
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

-- Dump completed on 2026-04-02 15:36:13





