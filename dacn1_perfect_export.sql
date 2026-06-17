-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: datn_db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_requests`
--

DROP TABLE IF EXISTS `account_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `request_type` varchar(50) NOT NULL,
  `details` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `admin_note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_requests`
--

LOCK TABLES `account_requests` WRITE;
/*!40000 ALTER TABLE `account_requests` DISABLE KEYS */;
INSERT INTO `account_requests` VALUES (1,12,'delete_account','czvzxczx','pending',NULL,'2025-12-25 08:01:54',NULL),(2,13,'update_info','dqefdqef','pending',NULL,'2025-12-29 03:44:54',NULL);
/*!40000 ALTER TABLE `account_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  KEY `idx_activity_logs_action` (`action`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `student_id` int NOT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointments_patient_id` (`patient_id`),
  KEY `idx_appointments_student_id` (`student_id`),
  KEY `idx_appointments_date` (`appointment_date`),
  KEY `idx_appointments_status` (`status`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_signals`
--

DROP TABLE IF EXISTS `call_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_signals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `caller_id` int NOT NULL,
  `callee_id` int NOT NULL,
  `signal_type` varchar(50) NOT NULL,
  `signal_data` text,
  `status` enum('pending','answered','rejected','ended') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_caller` (`caller_id`),
  KEY `idx_callee` (`callee_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_signals`
--

LOCK TABLES `call_signals` WRITE;
/*!40000 ALTER TABLE `call_signals` DISABLE KEYS */;
INSERT INTO `call_signals` VALUES (31,12,21,'call_request',NULL,'ended','2026-02-03 01:16:09','2026-02-03 01:16:32'),(32,12,21,'ice_candidate','{\"candidate\":\"candidate:3348155144 1 udp 2122260223 172.31.160.1 55673 typ host generation 0 ufrag 9Ds4 network-id 4\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(33,12,21,'ice_candidate','{\"candidate\":\"candidate:3738409506 1 udp 2122063615 172.240.25.209 55674 typ host generation 0 ufrag 9Ds4 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(34,12,21,'ice_candidate','{\"candidate\":\"candidate:3348155144 1 udp 2122260223 172.31.160.1 55677 typ host generation 0 ufrag 9Ds4 network-id 4\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(35,12,21,'ice_candidate','{\"candidate\":\"candidate:3132259353 1 udp 2122199807 fd5c:f563:2d82:ade9:a07f:a77:2555:4270 55675 typ host generation 0 ufrag 9Ds4 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(36,12,21,'ice_candidate','{\"candidate\":\"candidate:847681864 1 udp 2122134271 fd5c:f563:2d82:ade9:f09d:9e2d:ad5d:6fd2 55676 typ host generation 0 ufrag 9Ds4 network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(37,12,21,'ice_candidate','{\"candidate\":\"candidate:3738409506 1 udp 2122063615 172.240.25.209 55678 typ host generation 0 ufrag 9Ds4 network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(38,12,21,'ice_candidate','{\"candidate\":\"candidate:3132259353 1 udp 2122199807 fd5c:f563:2d82:ade9:a07f:a77:2555:4270 55679 typ host generation 0 ufrag 9Ds4 network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(39,12,21,'offer','{\"sdp\":\"v=0\\r\\no=- 8016609013839556688 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS c27241b0-a790-4d6c-9a63-48fcac7605e1\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:9Ds4\\r\\na=ice-pwd:mzetWMM70UcFlK80YyB3Tu32\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 0C:F1:90:65:9A:FD:A7:8A:D2:5D:31:F6:B6:E2:67:69:7B:27:54:47:4B:BE:17:AD:2A:13:12:56:20:C4:B4:AD\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:c27241b0-a790-4d6c-9a63-48fcac7605e1 f3db8bfd-6cb4-4647-8429-7ee6b89c323a\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:3658734597 cname:Dh+ugiGdJAW7kgKe\\r\\na=ssrc:3658734597 msid:c27241b0-a790-4d6c-9a63-48fcac7605e1 f3db8bfd-6cb4-4647-8429-7ee6b89c323a\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:9Ds4\\r\\na=ice-pwd:mzetWMM70UcFlK80YyB3Tu32\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 0C:F1:90:65:9A:FD:A7:8A:D2:5D:31:F6:B6:E2:67:69:7B:27:54:47:4B:BE:17:AD:2A:13:12:56:20:C4:B4:AD\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:c27241b0-a790-4d6c-9a63-48fcac7605e1 55566c27-6372-4994-97f9-70fa10acdaad\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 3787665456 2003175930\\r\\na=ssrc:3787665456 cname:Dh+ugiGdJAW7kgKe\\r\\na=ssrc:3787665456 msid:c27241b0-a790-4d6c-9a63-48fcac7605e1 55566c27-6372-4994-97f9-70fa10acdaad\\r\\na=ssrc:2003175930 cname:Dh+ugiGdJAW7kgKe\\r\\na=ssrc:2003175930 msid:c27241b0-a790-4d6c-9a63-48fcac7605e1 55566c27-6372-4994-97f9-70fa10acdaad\\r\\n\",\"type\":\"offer\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(40,12,21,'ice_candidate','{\"candidate\":\"candidate:847681864 1 udp 2122134271 fd5c:f563:2d82:ade9:f09d:9e2d:ad5d:6fd2 55680 typ host generation 0 ufrag 9Ds4 network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(41,12,21,'ice_candidate','{\"candidate\":\"candidate:1209793787 1 udp 1685855999 115.78.128.5 55674 typ srflx raddr 172.240.25.209 rport 55674 generation 0 ufrag 9Ds4 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(42,12,21,'ice_candidate','{\"candidate\":\"candidate:1209793787 1 udp 1685855999 42.113.191.149 55678 typ srflx raddr 172.240.25.209 rport 55678 generation 0 ufrag 9Ds4 network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(43,12,21,'ice_candidate','{\"candidate\":\"candidate:960110492 1 tcp 1518280447 172.31.160.1 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 4\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(44,12,21,'ice_candidate','{\"candidate\":\"candidate:544820918 1 tcp 1518083839 172.240.25.209 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(45,12,21,'ice_candidate','{\"candidate\":\"candidate:1142466701 1 tcp 1518220031 fd5c:f563:2d82:ade9:a07f:a77:2555:4270 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(46,12,21,'ice_candidate','{\"candidate\":\"candidate:3425455580 1 tcp 1518154495 fd5c:f563:2d82:ade9:f09d:9e2d:ad5d:6fd2 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(47,12,21,'ice_candidate','{\"candidate\":\"candidate:960110492 1 tcp 1518280447 172.31.160.1 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 4\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(48,12,21,'ice_candidate','{\"candidate\":\"candidate:544820918 1 tcp 1518083839 172.240.25.209 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(49,12,21,'ice_candidate','{\"candidate\":\"candidate:1142466701 1 tcp 1518220031 fd5c:f563:2d82:ade9:a07f:a77:2555:4270 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(50,12,21,'ice_candidate','{\"candidate\":\"candidate:3425455580 1 tcp 1518154495 fd5c:f563:2d82:ade9:f09d:9e2d:ad5d:6fd2 9 typ host tcptype active generation 0 ufrag 9Ds4 network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"9Ds4\"}','pending','2026-02-03 01:16:15','2026-02-03 01:16:15'),(51,21,12,'answer','{\"sdp\":\"v=0\\r\\no=- 4105345483209051385 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS dbcc04f6-f983-435d-b303-a0faba0260e9\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:cAI3\\r\\na=ice-pwd:VnCGI7aOOpRkizqX+sGlPjCX\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 D8:BD:1F:59:20:F3:AE:EE:EC:3D:CD:DD:90:DC:46:30:FC:20:55:EE:54:2A:EC:96:FD:9D:21:E5:E7:B2:0E:46\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:dbcc04f6-f983-435d-b303-a0faba0260e9 c19a8652-45b1-421c-a579-5bff4b92461c\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:3458314989 cname:/2dayHocQXKTAeTy\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 109 114 115 116 98 99 100 101 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:cAI3\\r\\na=ice-pwd:VnCGI7aOOpRkizqX+sGlPjCX\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 D8:BD:1F:59:20:F3:AE:EE:EC:3D:CD:DD:90:DC:46:30:FC:20:55:EE:54:2A:EC:96:FD:9D:21:E5:E7:B2:0E:46\\r\\na=setup:active\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:dbcc04f6-f983-435d-b303-a0faba0260e9 2483e7b4-9b56-4fbb-9681-b2b2f9bcdbcb\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 970819133 1021963420\\r\\na=ssrc:970819133 cname:/2dayHocQXKTAeTy\\r\\na=ssrc:1021963420 cname:/2dayHocQXKTAeTy\\r\\n\",\"type\":\"answer\"}','pending','2026-02-03 01:16:20','2026-02-03 01:16:20'),(52,21,12,'ice_candidate','{\"candidate\":\"candidate:3206220905 1 udp 1677729535 113.185.81.86 21616 typ srflx raddr 10.69.222.1 rport 64969 generation 0 ufrag cAI3 network-cost 999\",\"sdpMLineIndex\":0,\"sdpMid\":\"0\",\"usernameFragment\":null}','pending','2026-02-03 01:16:20','2026-02-03 01:16:20'),(53,21,12,'ice_candidate','{\"candidate\":\"candidate:1383733672 1 udp 2113939711 2001:ee0:1b16:464c:10b8:1dcb:ca69:364b 60478 typ host generation 0 ufrag cAI3 network-cost 999\",\"sdpMLineIndex\":0,\"sdpMid\":\"0\",\"usernameFragment\":null}','pending','2026-02-03 01:16:20','2026-02-03 01:16:20'),(54,21,12,'ice_candidate','{\"candidate\":\"candidate:2040615955 1 udp 2113937151 10.69.222.1 64969 typ host generation 0 ufrag cAI3 network-cost 999\",\"sdpMLineIndex\":0,\"sdpMid\":\"0\",\"usernameFragment\":null}','pending','2026-02-03 01:16:20','2026-02-03 01:16:20'),(55,21,12,'ice_candidate','{\"candidate\":\"candidate:2615000371 1 udp 1677732095 2001:ee0:1b16:464c:10b8:1dcb:ca69:364b 60478 typ srflx raddr 2001:ee0:1b16:464c:10b8:1dcb:ca69:364b rport 60478 generation 0 ufrag cAI3 network-cost 999\",\"sdpMLineIndex\":0,\"sdpMid\":\"0\",\"usernameFragment\":null}','pending','2026-02-03 01:16:20','2026-02-03 01:16:20');
/*!40000 ALTER TABLE `call_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_conversations`
--

DROP TABLE IF EXISTS `chatbot_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_session` (`user_id`,`session_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `chatbot_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_conversations`
--

LOCK TABLES `chatbot_conversations` WRITE;
/*!40000 ALTER TABLE `chatbot_conversations` DISABLE KEYS */;
INSERT INTO `chatbot_conversations` VALUES (1,8,'chat_69f01fbe818635.73023550','2026-04-28 02:47:26','2026-04-28 02:47:26'),(2,8,'chat_69f01fc85e8808.69836581','2026-04-28 02:47:36','2026-04-28 02:47:36'),(3,8,'chat_69f01ff322b2c8.12349571','2026-04-28 02:48:19','2026-04-28 02:48:19'),(4,8,'chat_69f020297f3834.57106014','2026-04-28 02:49:13','2026-04-28 02:49:13'),(5,8,'chat_69f020a877b0a1.79775006','2026-04-28 02:51:20','2026-04-28 02:51:20'),(6,8,'chat_69f021414eb215.38252981','2026-04-28 02:53:53','2026-04-28 02:53:53'),(7,8,'chat_69f03520196a98.11679019','2026-04-28 04:18:40','2026-04-28 04:18:40'),(8,8,'chat_69f0354bc99010.66029816','2026-04-28 04:19:23','2026-04-28 04:19:23'),(9,8,'chat_69f035a7bc9b35.52928384','2026-04-28 04:20:55','2026-04-28 04:20:55'),(10,8,'chat_69f035e9a5fdc2.92728699','2026-04-28 04:22:01','2026-04-28 04:22:01');
/*!40000 ALTER TABLE `chatbot_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_messages`
--

DROP TABLE IF EXISTS `chatbot_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `role` enum('user','assistant','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`conversation_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `chatbot_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_messages`
--

LOCK TABLES `chatbot_messages` WRITE;
/*!40000 ALTER TABLE `chatbot_messages` DISABLE KEYS */;
INSERT INTO `chatbot_messages` VALUES (1,1,'user','tôi bị đau chân',NULL,'2026-04-28 02:47:26'),(2,2,'user','alo',NULL,'2026-04-28 02:47:36'),(3,3,'user','alo',NULL,'2026-04-28 02:48:19'),(4,3,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 404).','{\"error\": \"Gemini API Error 404: models/gemini-1.5-flash is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.\"}','2026-04-28 02:48:19'),(5,4,'user','alo',NULL,'2026-04-28 02:49:13'),(6,4,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 400).','{\"error\": \"Gemini API Error 400: Invalid JSON payload received. Unknown name \\\"system_instruction\\\": Cannot find field.\"}','2026-04-28 02:49:13'),(7,5,'user','ALO',NULL,'2026-04-28 02:51:20'),(8,5,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 400).','{\"error\": \"Gemini API Error 400: Invalid JSON payload received. Unknown name \\\"system_instruction\\\": Cannot find field.\"}','2026-04-28 02:51:20'),(9,6,'user','ALO',NULL,'2026-04-28 02:53:53'),(10,6,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 400).','{\"error\": \"Gemini API Error 400: Invalid JSON payload received. Unknown name \\\"system_instruction\\\": Cannot find field.\"}','2026-04-28 02:53:54'),(11,7,'user','Tôi bị sốt và đau đầu',NULL,'2026-04-28 04:18:40'),(12,7,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 404).','{\"error\": \"Hugging Face API Error 404: Unknown error\"}','2026-04-28 04:18:40'),(13,8,'user','Đau bụng từ sáng nay',NULL,'2026-04-28 04:19:23'),(14,8,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 404).','{\"error\": \"Hugging Face API Error 404: Unknown error\"}','2026-04-28 04:19:24'),(15,9,'user','Đau bụng từ sáng nay',NULL,'2026-04-28 04:20:55'),(16,9,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 404).','{\"error\": \"Hugging Face API Error 404: Unknown error\"}','2026-04-28 04:20:56'),(17,10,'user','Tôi bị sốt và đau đầu',NULL,'2026-04-28 04:22:01'),(18,10,'assistant','Xin lỗi, tôi đang gặp sự cố kỹ thuật. Lỗi API (Code: 404).','{\"error\": \"Hugging Face API Error 404: Unknown error\"}','2026-04-28 04:22:02');
/*!40000 ALTER TABLE `chatbot_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_recommendations`
--

DROP TABLE IF EXISTS `chatbot_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_recommendations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `recommended_student_id` int DEFAULT NULL,
  `severity_level` enum('normal','moderate','urgent','emergency') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `suggested_specialty` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reasoning` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recommended_student_id` (`recommended_student_id`),
  KEY `idx_conversation` (`conversation_id`),
  CONSTRAINT `chatbot_recommendations_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chatbot_recommendations_ibfk_2` FOREIGN KEY (`recommended_student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_recommendations`
--

LOCK TABLES `chatbot_recommendations` WRITE;
/*!40000 ALTER TABLE `chatbot_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `chatbot_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment_likes`
--

DROP TABLE IF EXISTS `comment_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reaction_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'like',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_comment` (`comment_id`,`user_id`),
  KEY `idx_comment_likes_comment` (`comment_id`),
  KEY `idx_comment_likes_user` (`user_id`),
  CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_likes`
--

LOCK TABLES `comment_likes` WRITE;
/*!40000 ALTER TABLE `comment_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment_reports`
--

DROP TABLE IF EXISTS `comment_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','reviewed','resolved','dismissed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comment_reports_comment` (`comment_id`),
  KEY `idx_comment_reports_reporter` (`reporter_id`),
  KEY `idx_comment_reports_status` (`status`),
  CONSTRAINT `comment_reports_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comment_reports_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_reports`
--

LOCK TABLES `comment_reports` WRITE;
/*!40000 ALTER TABLE `comment_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `parent_id` int DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_post_id` (`post_id`),
  KEY `idx_comments_user_id` (`user_id`),
  KEY `idx_comments_created_at` (`created_at`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user1_id` int NOT NULL,
  `user2_id` int NOT NULL,
  `last_message_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_message_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv_user1_id` (`user1_id`),
  KEY `idx_conv_user2_id` (`user2_id`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (7,8,12,NULL,'2026-01-16 16:21:02','2026-01-16 16:22:09','2026-01-16 16:22:09');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direct_messages`
--

DROP TABLE IF EXISTS `direct_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `direct_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int DEFAULT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dm_sender_id` (`sender_id`),
  KEY `idx_dm_receiver_id` (`receiver_id`),
  KEY `idx_dm_is_read` (`is_read`),
  CONSTRAINT `direct_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `direct_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direct_messages`
--

LOCK TABLES `direct_messages` WRITE;
/*!40000 ALTER TABLE `direct_messages` DISABLE KEYS */;
INSERT INTO `direct_messages` VALUES (7,7,12,8,'Chào Bạn',0,'2026-01-16 16:21:02'),(8,7,8,12,'Chào bạn',0,'2026-01-16 16:22:09');
/*!40000 ALTER TABLE `direct_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email_verifications_user_id` (`user_id`),
  CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
INSERT INTO `email_verifications` VALUES (8,8,'2dbeff8a813b3b4234c2a8ef74128bfa6c47dffa507a666159124a2141ef6dda','2025-12-25 14:49:19',NULL,'2025-12-24 14:49:19'),(9,12,'1ac2d82101e7ab0e4ed47220adb220def3310b7f0f4305491f8b97ab00c6bdae','2025-12-26 07:28:17',NULL,'2025-12-25 07:28:17'),(22,20,'99b61f3cfc092b6a413a8a03aec04e0481240c8513bbd01b1beac9050ed2042d','2026-01-17 16:09:44','2026-01-16 16:14:28','2026-01-16 16:09:44'),(23,21,'9db4d0953c13a5a0762fda6b0376440d7acc5f12325a502189a6611433ac71b6','2026-01-17 16:24:24','2026-01-16 16:25:04','2026-01-16 16:24:24'),(29,22,'7e11a10ad4db9977e538976a67f4d3b6554561a91e4dd75f49bbd29cb3bce661','2026-02-04 01:12:31',NULL,'2026-02-03 01:12:31'),(31,24,'bc423c9238711e257fec3c6b246d4abd862727f81b0a547894e695817d743503','2026-03-14 02:49:29','2026-03-13 02:50:01','2026-03-13 02:49:29');
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_category` (`category`),
  KEY `idx_faqs_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'LÃ m tháº¿ nÃ o Ä‘á»ƒ Ä‘Äƒng kÃ½ tÃ i khoáº£n?','Báº¡n cÃ³ thá»ƒ Ä‘Äƒng kÃ½ tÃ i khoáº£n báº±ng cÃ¡ch click vÃ o nÃºt \"ÄÄƒng kÃ½\" á»Ÿ gÃ³c trÃªn bÃªn pháº£i vÃ  Ä‘iá»n Ä‘áº§y Ä‘á»§ thÃ´ng tin cáº§n thiáº¿t. Sau khi Ä‘Äƒng kÃ½, báº¡n cáº§n xÃ¡c thá»±c email Ä‘á»ƒ kÃ­ch hoáº¡t tÃ i khoáº£n.','TÃ i khoáº£n',1,1,'2025-12-24 14:32:29','2025-12-24 14:32:29'),(2,'Sinh viÃªn y khoa cáº§n lÃ m gÃ¬ Ä‘á»ƒ Ä‘Æ°á»£c xÃ¡c minh?','Sinh viÃªn cáº§n upload tháº» sinh viÃªn vÃ  giáº¥y tá» liÃªn quan (giáº¥y xÃ¡c nháº­n thá»±c táº­p, báº£ng Ä‘iá»ƒm...) Ä‘á»ƒ Ä‘Æ°á»£c admin xÃ¡c minh. QuÃ¡ trÃ¬nh xÃ¡c minh thÆ°á»ng máº¥t 1-2 ngÃ y lÃ m viá»‡c.','XÃ¡c minh',1,2,'2025-12-24 14:32:29','2025-12-24 14:32:29'),(3,'LÃ m sao Ä‘á»ƒ tÃ¬m kiáº¿m bÃ i viáº¿t?','Sá»­ dá»¥ng thanh tÃ¬m kiáº¿m á»Ÿ Ä‘áº§u trang Ä‘á»ƒ tÃ¬m kiáº¿m theo tá»« khÃ³a. Báº¡n cÅ©ng cÃ³ thá»ƒ lá»c theo tráº¡ng thÃ¡i bÃ i viáº¿t (Ä‘ang má»Ÿ, Ä‘Ã£ Ä‘Ã³ng, hoÃ n thÃ nh).','Sá»­ dá»¥ng',1,3,'2025-12-24 14:32:29','2025-12-24 14:32:29'),(4,'LÃ m sao Ä‘á»ƒ liÃªn há»‡ vá»›i sinh viÃªn y khoa?','Báº¡n cÃ³ thá»ƒ gá»­i tin nháº¯n trá»±c tiáº¿p cho sinh viÃªn thÃ´ng qua nÃºt \"Nháº¯n tin\" trÃªn trang há»“ sÆ¡ cá»§a há», hoáº·c bÃ¬nh luáº­n vÃ o bÃ i viáº¿t cá»§a báº¡n Ä‘á»ƒ sinh viÃªn cÃ³ thá»ƒ pháº£n há»“i.','Sá»­ dá»¥ng',1,4,'2025-12-24 14:32:29','2025-12-24 14:32:29'),(5,'ThÃ´ng tin cÃ¡ nhÃ¢n cá»§a tÃ´i cÃ³ Ä‘Æ°á»£c báº£o máº­t khÃ´ng?','ChÃºng tÃ´i cam káº¿t báº£o máº­t thÃ´ng tin cÃ¡ nhÃ¢n cá»§a báº¡n. Báº¡n cÃ³ thá»ƒ Ä‘iá»u chá»‰nh cÃ i Ä‘áº·t quyá»n riÃªng tÆ° trong pháº§n \"CÃ i Ä‘áº·t tÃ i khoáº£n\" Ä‘á»ƒ kiá»ƒm soÃ¡t ai cÃ³ thá»ƒ xem thÃ´ng tin cá»§a báº¡n.','Báº£o máº­t',1,5,'2025-12-24 14:32:29','2025-12-24 14:32:29');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_post` (`user_id`,`post_id`),
  KEY `idx_favorites_user_id` (`user_id`),
  KEY `idx_favorites_post_id` (`post_id`),
  CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friendships`
--

DROP TABLE IF EXISTS `friendships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `friendships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `friend_id` int NOT NULL,
  `status` enum('pending','accepted','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_friendships_user_id` (`user_id`),
  KEY `idx_friendships_friend_id` (`friend_id`),
  KEY `idx_friendships_status` (`status`),
  CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friendships`
--

LOCK TABLES `friendships` WRITE;
/*!40000 ALTER TABLE `friendships` DISABLE KEYS */;
INSERT INTO `friendships` VALUES (5,21,12,'accepted','2026-02-03 01:15:21','2026-02-03 01:15:32'),(6,8,24,'pending','2026-03-13 06:45:32',NULL);
/*!40000 ALTER TABLE `friendships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_posts`
--

DROP TABLE IF EXISTS `knowledge_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` text COLLATE utf8mb4_unicode_ci,
  `is_featured` tinyint(1) DEFAULT '0',
  `view_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_knowledge_posts_user_id` (`user_id`),
  KEY `idx_knowledge_posts_category` (`category`),
  KEY `idx_knowledge_posts_is_featured` (`is_featured`),
  CONSTRAINT `knowledge_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_posts`
--

LOCK TABLES `knowledge_posts` WRITE;
/*!40000 ALTER TABLE `knowledge_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `post_id` int DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_sender_id` (`sender_id`),
  KEY `idx_messages_receiver_id` (`receiver_id`),
  KEY `idx_messages_is_read` (`is_read`),
  KEY `idx_messages_post_id` (`post_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (4,20,8,NULL,NULL,'Yêu cầu đăng bài đã được phê duyệt. ',1,'2026-01-16 16:16:14'),(5,8,12,18,NULL,'Bạn đã được chọn nhận việc cho tin: Sinh viên Y năm 4 tìm cơ hội thực hành và hỗ trợ chăm sóc bệnh nhân. ',1,'2026-01-16 16:22:17'),(6,12,8,17,NULL,'Bạn đã được chọn nhận việc cho tin: Cần sinh viên y chăm sóc. ',1,'2026-01-17 00:37:26'),(7,8,12,26,NULL,'Bạn đã được chọn nhận việc cho tin: sdadsa. ',1,'2026-03-31 06:33:01');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `enable_message` tinyint(1) DEFAULT '1',
  `enable_appointment` tinyint(1) DEFAULT '1',
  `enable_rating` tinyint(1) DEFAULT '1',
  `enable_friend_request` tinyint(1) DEFAULT '1',
  `enable_reminder` tinyint(1) DEFAULT '1',
  `enable_system` tinyint(1) DEFAULT '1',
  `enable_push` tinyint(1) DEFAULT '1',
  `enable_email` tinyint(1) DEFAULT '0',
  `enable_sms` tinyint(1) DEFAULT '0',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_user_unread` (`user_id`,`is_read`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_password_resets_user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (3,8,'59224d1ec81ddeebf522c8f773eb07ee324eeee0bbdb18749fe1a40ab775546b','2025-12-24 16:16:08',NULL,'2025-12-24 15:16:08');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posting_requests`
--

DROP TABLE IF EXISTS `posting_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posting_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_card` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_internship` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_posting_requests_user_id` (`user_id`),
  KEY `idx_posting_requests_status` (`status`),
  CONSTRAINT `posting_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posting_requests`
--

LOCK TABLES `posting_requests` WRITE;
/*!40000 ALTER TABLE `posting_requests` DISABLE KEYS */;
INSERT INTO `posting_requests` VALUES (1,8,'Trầm Tấn Khá','DA22TTD','âêăêâă','aaêâăê','1768579627_TR_____NG_K____THU___T_V___C__NG_NGH____KHOA_C__NG_NGH____TH__NG_TIN__4_.png',NULL,'approved','','2026-01-16 16:07:07','2026-01-16 16:16:14');
/*!40000 ALTER TABLE `posting_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `assigned_to` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('recruitment','application') COLLATE utf8mb4_unicode_ci DEFAULT 'recruitment',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_fullname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recruiter_fullname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suggested_price` int DEFAULT NULL,
  `video_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_description` text COLLATE utf8mb4_unicode_ci,
  `area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('open','closed','completed','inactive','taken') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `card_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_posts_user_id` (`user_id`),
  KEY `idx_posts_status` (`status`),
  KEY `idx_posts_type` (`type`),
  KEY `idx_posts_created_at` (`created_at`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (24,24,NULL,'Thông Tin','ABC','recruitment','Khoa nội','0373406319',NULL,NULL,NULL,'Ngô Kim Tấn',NULL,NULL,'uploads/evidence_images/evidence_24_1773373295.jpg','Ảnh minh chứng tình trạng sức khỏe cần chăm sóc','Vĩnh Long','open','2026-03-13 03:37:22','2026-03-13 06:19:05',NULL),(27,8,NULL,'Sinh Viên Y Khoa','Tôi từng chăm sóc bệnh nhân bị bệnh đau chân tại bệnh viện đa khoa Trà Vinh\n\nKỹ năng nổi bật: đo huyết áp','application','khoa nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uploads/evidence_images/evidence_8_1777367644.jpg','Ảnh thẻ sinh viên hoặc giấy tờ minh chứng','','open','2026-04-28 09:14:04','2026-04-28 09:14:04',NULL);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_sub` (`user_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `push_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `rated_user_id` int NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_rated` (`user_id`,`rated_user_id`),
  KEY `idx_ratings_user_id` (`user_id`),
  KEY `idx_ratings_rated_user_id` (`rated_user_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`rated_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_id` int NOT NULL,
  `reported_user_id` int DEFAULT NULL,
  `post_id` int DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','reviewed','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reports_reporter_id` (`reporter_id`),
  KEY `idx_reports_reported_user_id` (`reported_user_id`),
  KEY `idx_reports_post_id` (`post_id`),
  KEY `idx_reports_status` (`status`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_feedback`
--

DROP TABLE IF EXISTS `user_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `feedback_type` enum('bug','suggestion','complaint','compliment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('new','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_feedback_user_id` (`user_id`),
  KEY `idx_user_feedback_status` (`status`),
  CONSTRAINT `user_feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_feedback`
--

LOCK TABLES `user_feedback` WRITE;
/*!40000 ALTER TABLE `user_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `facebook_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('patient','student','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `is_admin` tinyint(1) DEFAULT '0',
  `can_post` tinyint(1) DEFAULT '0',
  `show_phone` tinyint(1) DEFAULT '1',
  `show_email` tinyint(1) DEFAULT '1',
  `allow_messages` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `address` text COLLATE utf8mb4_unicode_ci,
  `unread_notifications` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_users_facebook_id` (`facebook_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (8,'Trầm Tấn Khá','Trầm Tấn Khá',NULL,'Trầm Tấn Khá','110122087@st.tvu.edu.vn',1,NULL,'$2y$10$kc3PsKnSGkwEn60O9CqIFuzrgjH6tceUocJmh4nkVHN2y2rMRi9V2','student','','','','uploads/avatars/u8_1773382926.jpg','Đại học Trà Vinh','DA22YDK','110122087',1,0,1,1,1,1,'2026-04-30 16:30:16','2026-04-30 16:30:16','2025-12-24 14:36:40',NULL,0),(12,'Nguyễn Phi Hùng','Nguyễn Phi Hùng',NULL,'Nguyễn Phi Hùng','tramtankhatv@gmail.com',1,NULL,'$2y$10$l9dGnXD/Kx5JikJJZddAvesSJZOtx1RElzk/8aZHNwOoy9mVxIW2.','patient','Hùng Phi','bạbasd','0364624795','uploads/avatars/u12_1770080700.jpg',NULL,NULL,NULL,0,0,1,1,1,1,'2026-04-29 10:39:28','2026-04-29 10:39:28','2025-12-25 07:28:17','dfafa',0),(20,'Vũ',NULL,NULL,'Vũ','tramkhatram2@gmail.com',1,NULL,'$2y$10$VGjKlg4XkHVLLMKBEdyhCuXg5WYm2Aq6qd3cLe1wmKW9ZI6nFgCru','patient','','','tramkhatram2@gmail.com','uploads/avatars/u20_1773370711.jpg',NULL,NULL,NULL,0,1,1,1,1,1,'2026-04-30 16:31:26','2026-04-30 16:59:44','2026-01-16 16:09:44',NULL,0),(21,'Đạt',NULL,NULL,'Đạt','tramkhatram1@gmail.com',1,NULL,'$2y$10$m1oIS5c.H3AtAOL0A23kpupbL/1kRSxttYaqgWMNJ9msDeyNeJsTW','patient',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,1,1,1,1,'2026-03-13 03:38:51','2026-03-13 03:53:39','2026-01-16 16:24:24',NULL,0),(22,'Duy',NULL,NULL,'Duy','110122058@st.tvu.edu.vn',0,NULL,'$2y$10$PtGCfaLuzPBw/eTpZWQTY.uPgfW2iAKuva2WgESXlrGtLSqS49V/K','student',NULL,NULL,NULL,NULL,'TRƯỜNG Đại học trà vinh','YDK','110122058',0,0,0,1,1,1,NULL,NULL,'2026-02-03 01:11:02',NULL,0),(24,'Ngô Kim Tấn',NULL,NULL,'Ngô Kim Tấn','tramkhatv@gmail.com',1,NULL,'$2y$10$i1mtwBOwBxHcwM1GrgX.e.B26FWnRm46q/uJR3nMScQ4JImApNKEm','patient','','','','uploads/avatars/u24_1773370311.jpg',NULL,NULL,NULL,0,0,1,1,1,1,'2026-03-18 07:53:44','2026-03-18 07:54:47','2026-03-13 02:49:29',NULL,0),(25,NULL,NULL,'Administrator','admin','admin@example.com',1,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,1,1,1,NULL,NULL,'2026-04-28 08:22:13',NULL,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verifications`
--

DROP TABLE IF EXISTS `verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_card` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_internship` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_verifications_user_id` (`user_id`),
  KEY `idx_verifications_status` (`status`),
  CONSTRAINT `verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verifications`
--

LOCK TABLES `verifications` WRITE;
/*!40000 ALTER TABLE `verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `verifications` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-30 17:00:30
