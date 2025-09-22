-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2025 at 11:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `comp1044_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `Customer_ID` int(11) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `First_Name` varchar(50) NOT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Address` varchar(50) DEFAULT NULL,
  `Company` varchar(50) NOT NULL,
  `Phone_Number` varchar(20) DEFAULT NULL,
  `Staff_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`Customer_ID`, `Last_Name`, `First_Name`, `Email`, `Address`, `Company`, `Phone_Number`, `Staff_ID`) VALUES
(1, 'Goh', 'Stephanie', 'stephanie.goh@gmail.com', '11 Jalan USJ 9/5T, Subang Jaya', 'InnovaZ Solutions', '+60146543289', 4),
(2, 'Raj', 'Viknesh', 'viknesh.raj@gmail.com', '22 Jalan Tunku Abdul Rahman, Kuala Lumpur', 'TrinitySoft', '+60149981023', 4),
(3, 'Lee', 'Amanda', 'amanda.lee@gmail.com', '87 Jalan Kuchai Lama, Kuala Lumpur', 'KL Fashions', '+60125543322', 4),
(4, 'Teo', 'Kelvin', 'kelvin.teo@gmail.com', '34 Jalan Sri Hartamas, Mont Kiara', 'Teo Electronics', '+60129565456', 4),
(5, 'Hassan', 'Nurul', 'nurul.hassan@gmail.com', '65 Jalan Merdeka, Shah Alam', 'BizSpark Asia', '+60192108991', 4),
(6, 'Kumar', 'Priya', 'priya.kumar@yahoo.com', '73 Jalan Tun Ismail, Kuantan', 'Synergy Plus', '+60165053030', 5),
(7, 'Foo', 'Benjamin', 'ben.foo@gmail.com', '9 Jalan Sultan, Klang', 'CreoTek', '+60128787687', 5),
(8, 'Liew', 'Jolene', 'jolene.liew@eduspace.edu.my', '60 Jalan Kemajuan, Petaling Jaya', 'EduSpace College', '+60183314544', 5),
(9, 'Mohd', 'Shafiq', 'shafiq.mohd@yahoo.com', '89 Jalan Rasah, Seremban', 'TekForce Systems', '+601115248567', 5),
(10, 'Cheong', 'Melissa', 'melissa.cheong@gmail.com', '10 Jalan Kepong, Kuala Lumpur', 'Revo Digital', '+60147776666', 5),
(11, 'Chong', 'Eric', 'eric.chong.my@gmail.com', '88 Jalan Mahkota, Cheras', 'EC Logistics', '+60145551911', 6),
(12, 'Halim', 'Sofia', 'sofia.halim23@yahoo.com', '63 Jalan Melati, Klang', 'Boutique by Sofia', '+60183129944', 6),
(13, 'Liew', 'Daniel', 'danliew@gmail.com', '101 Jalan Selasih, Bukit Mertajam', 'iGrow FarmTech', '+601130337766', 6),
(14, 'Zainal', 'Amir', 'amir.zainal123@yahoo.com', '45 Jalan Taman Ria, Sungai Petani', 'AmirTech', '+60122827181', 6),
(15, 'Tan', 'Angela', 'angelatan.my@gmail.com', '89 Jalan Skudai, Johor Bahru', 'Angela Wellness', '+60194012277', 6),
(16, 'Mohan', 'Rajesh', 'rajmohan.my@gmail.com', '27 Jalan Imbi, Kuala Lumpur', 'Raj Technologies', '+60138419494', 7),
(17, 'Yap', 'Christine', 'christineyap82@yahoo.com', '16 Jalan Kelawei, George Town', 'Yap & Partners', '+60123759367', 7),
(18, 'Farid', 'Hanafi', 'hanafi.farid@gmail.com', '35 Jalan Merpati, Taiping', 'H&F PrintHouse', '+60169807773', 7),
(19, 'Sim', 'Natalie', 'natalie.sim@yahoo.com', '67 Jalan Gombak, Gombak', 'Sim Design', '+60125096030', 7),
(20, 'Ismail', 'Azrul', 'azrulismail.my@gmail.com', '84 Jalan Laksamana, Melaka', 'AutoPlus Workshop', '+60108089981', 7),
(21, 'Cheah', 'Vincent', 'vincentcheah.my@gmail.com', '12 Jalan Desa Cemerlang, Johor', 'VSync Solutions', '+601128765432', 8),
(22, 'Phang', 'Julian', 'julianphang88@gmail.com', '102 Jalan Lagenda, Alor Setar', 'DreamTech Lab', '+601129806543', 8),
(23, 'Roslan', 'Iman', 'iman.roslan.my@yahoo.com', '40 Jalan Puncak Alam, Selangor', 'LocalMart', '+60128906543', 8),
(24, 'Taco', 'Tappatio', 'taco23@yahoo.com', 'Mex 56, Loud Ah', 'Guacamolli', '+524569872112', 4);

-- --------------------------------------------------------

--
-- Table structure for table `interaction`
--

CREATE TABLE `interaction` (
  `Interaction_ID` int(11) NOT NULL,
  `Interaction_Type` varchar(30) NOT NULL,
  `Description` varchar(50) DEFAULT NULL,
  `Interaction_Date` date NOT NULL,
  `Staff_ID` int(11) DEFAULT NULL,
  `Customer_ID` int(11) DEFAULT NULL,
  `Lead_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interaction`
--

INSERT INTO `interaction` (`Interaction_ID`, `Interaction_Type`, `Description`, `Interaction_Date`, `Staff_ID`, `Customer_ID`, `Lead_ID`) VALUES
(1, 'Meeting', 'Wants integration with existing ERP.', '2025-04-22', 4, 1, NULL),
(2, 'Meeting', 'Looking for CRM with role-based access.', '2025-04-23', 4, 2, NULL),
(3, 'Call', 'Wants customer loyalty program features.', '2025-04-22', 4, 3, NULL),
(4, 'Call', 'Requested product roadmap.', '2025-04-23', 4, 4, NULL),
(5, 'Meeting', 'Following up after tech conference meeting.', '2025-04-25', 4, 5, NULL),
(6, 'Email', 'Potential for enterprise plan upsell.', '2025-04-24', 5, 10, NULL),
(7, 'Email', 'Still comparing vendors.', '2025-04-30', 5, 6, NULL),
(8, 'Meeting', 'Needs follow-up after price quotation.', '2025-04-17', 5, 7, NULL),
(9, 'Call', 'Inquired about student CRM adaptation.', '2025-04-16', 5, 8, NULL),
(10, 'Email', 'Wants analytics dashboard feature.', '2025-05-01', 5, 9, NULL),
(11, 'Meeting', 'Requesting CRM for delivery team.', '2025-05-08', 6, 11, NULL),
(12, 'Email', 'Asked about SMS notification features.', '2025-04-24', 6, 12, NULL),
(13, 'Meeting', 'Needs CRM for agriculture outreach.', '2025-05-09', 6, 13, NULL),
(14, 'Meeting', 'Asked for performance tracking feature.', '2025-04-24', 6, 14, NULL),
(15, 'Email', 'Wants client notes encryption.', '2025-05-01', 6, 15, NULL),
(16, 'Call', 'Looking into white-label CRM option.', '2025-04-17', 7, 16, NULL),
(17, 'Meeting', 'Wants call log sync with CRM.', '2025-04-23', 7, 17, NULL),
(18, 'Meeting', 'Demo scheduled on 2nd May.', '2025-05-02', 7, 18, NULL),
(19, 'Email', 'CRM needed for client segmentation.', '2025-05-02', 7, 19, NULL),
(20, 'Call', 'Wants customer birthday reminders.', '2025-05-06', 7, 20, NULL),
(21, 'Meeting', 'Requested case study document.', '2025-04-30', 8, 21, NULL),
(22, 'Email', 'Remember to send email to Yoong Shen', '2025-04-23', 1, 1, NULL),
(23, 'Email', 'Purchase the newest model', '2025-04-24', 1, 21, NULL),
(24, 'Call', 'Emergency purchase of product', '2025-04-24', 1, NULL, 7),
(25, 'Meeting', 'Business deal', '2025-04-24', 5, NULL, 4),
(27, 'Email', 'Emergency meeting with the CTO of the company', '2025-04-28', 8, NULL, 14);

-- --------------------------------------------------------

--
-- Table structure for table `lead`
--

CREATE TABLE `lead` (
  `Lead_ID` int(11) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `First_Name` varchar(50) NOT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Address` varchar(50) DEFAULT NULL,
  `Company` varchar(50) NOT NULL,
  `Notes` varchar(100) DEFAULT NULL,
  `Status` varchar(50) NOT NULL,
  `Phone_Number` varchar(20) DEFAULT NULL,
  `Staff_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead`
--

INSERT INTO `lead` (`Lead_ID`, `Last_Name`, `First_Name`, `Email`, `Address`, `Company`, `Notes`, `Status`, `Phone_Number`, `Staff_ID`) VALUES
(1, 'Tan', 'Wei Jie', 'weijie.tan@gmail.com', '123 Jalan Bunga Raya, Taman Melati, 53100 Kuala Lu', 'TechNova Solutions Sdn Bhd', 'Interested in CRM system demo. Follow up next week.', 'Contacted', '+60123456789', 5),
(2, 'Lim', 'Jia Wen', 'jiawen.lim@gmail.com', '56 Jalan SS2/5, Petaling Jaya, Selangor', 'GreenByte Technologies', 'Requested pricing for premium plan.', 'In Progress', '+601123459876', 4),
(3, 'Ahmad', 'Faizal', 'faizal.ahmad@gmail.com', '78 Persiaran Wawasan, Putrajaya', 'MyGovTech', 'Interested in government contract integration.', 'New', '+60136789012', 4),
(4, 'Wong', 'Mei Ling', 'meiling.wong@gmail.com', '21 Lorong Kenari 10, Bayan Lepas, Penang', 'Freelance Consultant', 'Needs a custom CRM module.', 'New', '+60194561234', 4),
(5, 'Chia', 'Raymond', 'raymond@gmail.com', '99 Jalan Bakar Batu, Johor Bahru', 'Chia Enterprise', 'Scheduled demo for 24th April.', 'In Progress', '+60172228989', 4),
(7, 'Nor', 'Aina', 'aina.nor@gmail.com', '12 Jalan Dato Onn, Johor Bahru', 'EcommHub', 'Interested in CRM training program.', 'New', '+60174458760', 5),
(8, 'Chin', 'Patrick', 'patrick.chin@gmail.com', '30 Jalan Tun Razak, Kuala Lumpur', 'MalTrade Ventures', 'Asked for API documentation.', 'Closed', '+601110102020', 5),
(9, 'Yap', 'Serena', 'serena.yap@finefoods.com.my', '49 Jalan Perak, Ipoh, Perak', 'FineFoods Malaysia', 'Wants invoice automation in system.', 'New', '+60105437891', 5),
(10, 'Tan', 'Joel', 'joel.tan@gmail.com', '28 Jalan Anggerik, Seremban', 'Tan Bros Holdings', 'Potential for enterprise plan upsell.', 'In Progress', '+60138774482', 5),
(11, 'Ibrahim', 'Khalid', 'khalid.ibrahim@gmail.my', '5 Jalan Langat, Banting', 'Halalan Foods', 'Interested in POS integration module.', 'New', '+60131192929', 6),
(12, 'Lee', 'Michelle', 'michelle.lee87@gmail.com', '32 Jalan Meranti, Bandar Utama, Selangor', 'ML Craftworks', 'Asked for discount on yearly plan.', 'Contacted', '+60132208876', 6),
(14, 'Ng', 'Jason', 'jason.ng88@gmail.com', '14 Jalan Kampung Pandan, Ampang', 'Freelance Designer', 'Looking for free-tier plan.', 'Closed', '+60167816609', 6),
(15, 'Lim', 'Sharon', 'sharon.lim95@yahoo.com', '21 Jalan Ampang Jaya, Selangor', 'Sweet Delights', 'Wants invoice automation feature.', 'In Progress', '+60191028820', 6),
(16, 'Gopal', 'Nisha', 'nishagopal87@yahoo.com', '39 Jalan Merbok, Ipoh', 'Nisha Events', 'Needs event CRM with reminders.', 'New', '+60109094323', 7),
(17, 'Lau', 'Ken', 'kenlau2020@gmail.com', '33 Jalan Desa Pandan, Kuala Lumpur', 'NextGen Supplies', 'Inquired about multi-user support.', 'Contacted', '+60182458391', 7),
(18, 'Ibrahim', 'Aina', 'ainaibrahim03@yahoo.com', '58 Jalan Pudu, Kuala Lumpur', 'Aina Consultancy', 'Wants simplified UI for elderly users.', 'In Progress', '+60176639302', 7),
(19, 'Choo', 'Alvin', 'alvinchoo7@gmail.com', '19 Jalan Sri Pinang, Penang', 'Alvin Photography', 'Asked for analytics dashboard demo.', 'Contacted', '+60143327788', 7),
(20, 'Tan', 'Li Wei', 'liwei.tan01@yahoo.com', '70 Jalan Telawi, Bangsar', 'Tech Haven', 'Wants student database import feature.', 'Closed', '+601160901231', 7),
(21, 'Koh', 'Darren', 'darrenkoh123@yahoo.com', '55 Jalan Kenari, Puchong', 'Koh Hardware', 'Looking for CRM with inventory support.', 'Contacted', '+60137746033', 8),
(22, 'Nordin', 'Faiza', 'faizanordin@yahoo.com', '29 Jalan Seri, Kajang', 'SmartKids Playhouse', 'Looking for attendance tracking module.', 'New', '+60189874032', 8);

-- --------------------------------------------------------

--
-- Table structure for table `reminder`
--

CREATE TABLE `reminder` (
  `Reminder_ID` int(11) NOT NULL,
  `Reminder_Type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminder`
--

INSERT INTO `reminder` (`Reminder_ID`, `Reminder_Type`) VALUES
(1, 'Read'),
(2, 'Unread');

-- --------------------------------------------------------

--
-- Table structure for table `reminder_record`
--

CREATE TABLE `reminder_record` (
  `Reminder_Record_ID` int(11) NOT NULL,
  `Event_Date` date NOT NULL,
  `Reminder_ID` int(11) DEFAULT NULL,
  `Lead_ID` int(11) DEFAULT NULL,
  `Customer_ID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Staff_ID` int(11) DEFAULT NULL,
  `reminder_date` datetime DEFAULT NULL,
  `Event_Time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminder_record`
--

INSERT INTO `reminder_record` (`Reminder_Record_ID`, `Event_Date`, `Reminder_ID`, `Lead_ID`, `Customer_ID`, `Description`, `Staff_ID`, `reminder_date`, `Event_Time`) VALUES
(1, '2025-04-26', 1, 1, NULL, 'Interested in CRM system demo. Follow up next week.', 4, '2025-04-26 17:00:00', '18:00:00'),
(2, '2025-04-23', 1, 2, NULL, 'Requested pricing for premium plan.', 4, '2025-04-22 01:30:00', '01:30:00'),
(3, '2025-04-25', 1, NULL, 1, 'Wants integration with existing ERP.', 4, '2025-04-25 18:45:00', '21:45:00'),
(4, '2025-04-24', 1, NULL, 2, 'Looking for CRM with role-based access.', 4, '2025-04-24 09:00:00', '10:00:00'),
(5, '2025-05-08', 2, NULL, 3, 'Wants customer loyalty program features.', 4, '2025-05-01 13:30:00', '13:30:00'),
(6, '2025-04-29', 2, NULL, 6, 'Still comparing vendors.', 5, '2025-04-27 11:00:00', '11:00:00'),
(7, '2025-04-30', 2, NULL, 8, 'Inquired about student CRM adaptation.', 5, '2025-04-30 08:30:00', '09:30:00'),
(9, '2025-05-30', 2, 9, NULL, 'Wants invoice automation in system', 5, '2025-05-29 12:30:00', '12:30:00'),
(10, '2025-06-06', 2, 7, NULL, 'Interested in CRM training program. (IMPORTANT)', 5, '2025-06-05 10:00:00', '10:00:00'),
(11, '2025-05-19', 2, NULL, 11, 'Requesting CRM for delivery team.', 6, '2025-05-19 14:00:00', '16:00:00'),
(12, '2025-04-30', 2, NULL, 14, 'Asked for performance tracking feature.', 6, '2025-04-29 12:00:00', '12:00:00'),
(13, '2025-03-27', 1, 11, NULL, 'Interested in POS integration module.', 6, '2025-03-27 14:30:00', '15:30:00'),
(15, '2025-03-20', 1, 15, NULL, 'Wants invoice automation feature.', 6, '2025-03-20 12:30:00', '14:30:00'),
(16, '2025-04-22', 1, NULL, 16, 'Looking into white-label CRM option.', 7, '2025-04-22 05:00:00', '07:00:00'),
(17, '2025-05-13', 2, 16, NULL, 'Needs event CRM with reminders.', 7, '2025-05-13 12:15:00', '14:15:00'),
(18, '2025-05-22', 2, 17, NULL, 'Inquired about multi-user support.', 7, '2025-05-22 12:30:00', '13:30:00'),
(19, '2025-04-26', 1, 18, NULL, 'Wants simplified UI for elderly users.', 7, '2025-04-26 12:45:00', '14:45:00'),
(20, '2025-05-01', 2, NULL, 18, 'Demo scheduled on 2nd May.', 7, '2025-05-01 05:45:00', '07:45:00'),
(21, '2025-04-22', 1, NULL, 21, 'Requested case study document.', 8, '2025-04-22 05:30:00', '06:30:00'),
(48, '2025-04-28', 2, NULL, 19, 'Meeting with important client', 7, '2025-04-28 16:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `Role_ID` int(11) NOT NULL,
  `Role_Title` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`Role_ID`, `Role_Title`) VALUES
(1, 'Admin'),
(2, 'Sales Representative');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `Staff_ID` int(11) NOT NULL,
  `Role_ID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `First_Name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Address` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`Staff_ID`, `Role_ID`, `Username`, `Password`, `Email`, `First_Name`, `Last_Name`, `Address`) VALUES
(1, 1, 'Admin 1', '123', 'ivan@gmail.com', 'Ivan', 'Char', 'Seremban'),
(2, 1, 'Admin 2', '123', 'cham@gmail.com', 'Jin Jie', 'Cham', 'Seremban'),
(3, 1, 'Admin 3', '123', 'felimy@gmail.com', 'Felimy', 'Lai', 'Sarawak'),
(4, 2, 'SalesRep 1', '123', 'ngys@gmail.com', 'Yoong Shen', 'Ng', 'Puchong'),
(5, 2, 'SalesRep 2', '123', 'pius@gmail.com', 'Pius', 'Lau', 'Petaling Jaya'),
(6, 2, 'SalesRep 3', '123', 'weijun@gmail.com', 'Wei Jun', 'Lim', 'Kota Damansara'),
(7, 2, 'SalesRep 4', '123', 'gohys@gmail.com', 'Yi Siang', 'Goh', 'Seremban'),
(8, 2, 'SalesRep 5', '123', 'shirley@gmail.com', 'Shirley', 'Ng', 'Ipoh'),
(11, 2, 'SalesRep 6', '123', 'chyeCheah@gmail.com', 'Tan', 'Chye Cheah', 'Penang');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`Customer_ID`),
  ADD KEY `fk_customer_staff` (`Staff_ID`);

--
-- Indexes for table `interaction`
--
ALTER TABLE `interaction`
  ADD PRIMARY KEY (`Interaction_ID`),
  ADD KEY `Staff_ID` (`Staff_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `fk_lead_id` (`Lead_ID`);

--
-- Indexes for table `lead`
--
ALTER TABLE `lead`
  ADD PRIMARY KEY (`Lead_ID`),
  ADD KEY `fk_lead_staff` (`Staff_ID`);

--
-- Indexes for table `reminder`
--
ALTER TABLE `reminder`
  ADD PRIMARY KEY (`Reminder_ID`);

--
-- Indexes for table `reminder_record`
--
ALTER TABLE `reminder_record`
  ADD PRIMARY KEY (`Reminder_Record_ID`),
  ADD KEY `Notification_ID` (`Reminder_ID`),
  ADD KEY `Lead_ID` (`Lead_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `fk_staff` (`Staff_ID`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`Role_ID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`Staff_ID`),
  ADD KEY `Role_ID_id` (`Role_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `interaction`
--
ALTER TABLE `interaction`
  MODIFY `Interaction_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `lead`
--
ALTER TABLE `lead`
  MODIFY `Lead_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `reminder`
--
ALTER TABLE `reminder`
  MODIFY `Reminder_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reminder_record`
--
ALTER TABLE `reminder_record`
  MODIFY `Reminder_Record_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `Role_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `Staff_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `fk_customer_staff` FOREIGN KEY (`Staff_ID`) REFERENCES `staff` (`Staff_ID`);

--
-- Constraints for table `interaction`
--
ALTER TABLE `interaction`
  ADD CONSTRAINT `fk_lead_id` FOREIGN KEY (`Lead_ID`) REFERENCES `lead` (`Lead_ID`),
  ADD CONSTRAINT `interaction_ibfk_1` FOREIGN KEY (`Staff_ID`) REFERENCES `staff` (`Staff_ID`),
  ADD CONSTRAINT `interaction_ibfk_3` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`);

--
-- Constraints for table `lead`
--
ALTER TABLE `lead`
  ADD CONSTRAINT `fk_lead_staff` FOREIGN KEY (`Staff_ID`) REFERENCES `staff` (`Staff_ID`);

--
-- Constraints for table `reminder_record`
--
ALTER TABLE `reminder_record`
  ADD CONSTRAINT `fk_staff` FOREIGN KEY (`Staff_ID`) REFERENCES `staff` (`Staff_ID`),
  ADD CONSTRAINT `reminder_record_ibfk_1` FOREIGN KEY (`Reminder_ID`) REFERENCES `reminder` (`Reminder_ID`),
  ADD CONSTRAINT `reminder_record_ibfk_3` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `Role_ID_id` FOREIGN KEY (`Role_ID`) REFERENCES `role` (`Role_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
