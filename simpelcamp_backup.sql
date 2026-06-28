-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: simpelcamp
-- ------------------------------------------------------
-- Server version	8.4.3
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barang`
--

DROP TABLE IF EXISTS `barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori_id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text,
  `gambar` varchar(255) DEFAULT NULL,
  `harga_per_hari` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stok_total` int NOT NULL DEFAULT '0',
  `stok_tersedia` int NOT NULL DEFAULT '0',
  `status` enum('tersedia','habis','maintenance') NOT NULL DEFAULT 'tersedia',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_barang_kategori` (`kategori_id`),
  KEY `idx_barang_status` (`status`),
  CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang`
--

LOCK TABLES `barang` WRITE;
/*!40000 ALTER TABLE `barang` DISABLE KEYS */;
INSERT INTO `barang` VALUES (1,1,'Tenda Dome Eiger 4P','Tenda dome kapasitas 4 orang, double layer waterproof, frame aluminium kokoh. Cocok untuk camping keluarga.','shelter_1.jpg',50000.00,21,21,'tersedia','2026-06-17 17:27:11','2026-06-28 22:11:50'),(2,1,'Tenda Dome Consina 2P','Tenda dome ringan kapasitas 2 orang, mudah dipasang, cocok pendakian solo/berdua.','shelter_2.jpg',35000.00,18,18,'tersedia','2026-06-17 17:27:11','2026-06-28 22:11:50'),(3,1,'Flysheet 3x4 Meter','Atap tambahan anti-bocor ukuran 3??4 meter. Waterproof coating, tali guy-rope included.','shelter_3.jpg',15000.00,24,24,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(4,1,'Footprint Tenda 2P','Alas tambahan tenda ukuran 2 orang, melindungi dasar tenda dari kelembapan dan batu tajam.','shelter_2.jpg',8000.00,17,17,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(5,1,'Terpal Multifungsi 4x6m','Terpal serbaguna ukuran 4??6 meter, bisa jadi alas atau atap tambahan.','shelter_2.jpg',10000.00,19,19,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(6,1,'Pasak Tenda Cadangan (set 10)','Set 10 pasak tenda aluminium alloy, ringan dan kuat untuk berbagai jenis tanah.','shelter_3.jpg',5000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(7,2,'Sleeping Bag Polar -10??C','Sleeping bag bahan polar tebal, tahan suhu hingga -10??C, cocok dataran tinggi.','tidur_1.jpg',20000.00,21,21,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(8,2,'Sleeping Bag Dacron 0??C','Sleeping bag bahan dacron, tahan suhu 0??C, ringan dan mudah dikompres.','tidur_2.jpg',15000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(9,2,'Matras Spons Gulung','Matras spons gulung tebal 2cm, ringan, isolasi dingin dari tanah.','tidur_1.jpg',8000.00,28,28,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(10,2,'Matras Tiup Ultralight','Matras tiup ultralight dengan built-in pump, tebal 5cm, sangat nyaman.','tidur_1.jpg',18000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(11,2,'Bantal Tiup Travel','Bantal tiup compact, bahan soft-touch, mudah dikempiskan dan disimpan.','tidur_2.jpg',5000.00,34,34,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(12,7,'Selimut Fleece Outdoor','Selimut fleece hangat, ringan, bisa dilipat kecil. Cocok sebagai liner sleeping bag.','tidur_2.jpg',10000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(13,3,'Kompor Portable + Windshield','Kompor gas portable outdoor dengan pelindung angin. Tabung gas TIDAK termasuk.','dapur_1.jpg',15000.00,17,17,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(14,3,'Nesting Cooking Set 3-4P','Set panci, wajan, dan teko stainless steel untuk 3-4 orang, compact stackable.','dapur_2.jpg',20000.00,22,22,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(15,3,'Perlengkapan Makan Set','Set piring, mangkok, gelas, sendok, garpu stainless untuk 4 orang.','dapur_3.jpg',10000.00,30,30,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(16,3,'Cooler Box 28 Liter','Tas pendingin kapasitas 28L, menjaga suhu dingin hingga 24 jam.','dapur_1.jpg',25000.00,11,11,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(17,3,'Teko Camping Aluminium 1.5L','Teko aluminium 1.5 liter, handle lipat tahan panas, cepat mendidih.','dapur_2.jpg',8000.00,15,15,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(18,4,'Headlamp LED 300 Lumen','Headlamp LED 300 lumen, 3 mode cahaya (terang/redup/kedip), tahan air IPX4.','penerangan_1.jpg',8000.00,34,34,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(19,4,'Senter Tangan Tactical','Senter tangan LED tactical 500 lumen, zoom fokus, baterai rechargeable.','penerangan_2.jpg',10000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(20,1,'Lampu Tenda Gantung LED','Lampu LED gantung untuk dalam tenda, 3 mode cahaya, hemat baterai.','penerangan_1.jpg',7000.00,19,19,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(21,4,'Lentera Camping Rechargeable','Lentera LED rechargeable USB, cahaya 360??, bisa jadi powerbank darurat.','penerangan_2.jpg',12000.00,17,17,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(22,4,'Powerbank 20000mAh','Powerbank kapasitas 20.000mAh, dual USB output, casing anti-shock.','penerangan_1.jpg',15000.00,22,22,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(23,5,'Jaket Tahan Angin Waterproof','Jaket windbreaker waterproof, ringan, packable, cocok untuk pendakian.','pakaian_1.jpg',25000.00,22,22,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(24,5,'Jas Hujan Poncho','Poncho jas hujan besar, menutupi badan dan tas carrier sekaligus.','pakaian_2.jpg',10000.00,26,26,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(25,5,'Sepatu Gunung Mid-Cut','Sepatu hiking mid-cut, sol Vibram grip kuat, ankle support, waterproof.','pakaian_1.jpg',30000.00,18,18,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(26,5,'Sandal Gunung Outdoor','Sandal gunung tali adjustable, sol karet anti-slip, nyaman untuk camp.','pakaian_2.jpg',12000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(27,6,'Kotak P3K Lengkap','Kotak P3K berisi perban, antiseptik, plester, obat dasar, minyak kayu putih.','medis_1.jpg',10000.00,15,15,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(28,13,'Trash Bag Set (10 lembar)','Set 10 kantong sampah besar 60L, wajib untuk bawa pulang sampah dari alam.','medis_2.jpg',3000.00,38,38,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(29,6,'Kit Kebersihan Outdoor','Paket sabun travel, sikat gigi, pasta gigi, handuk microfiber compact.','medis_1.jpg',8000.00,22,22,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(30,6,'Sunblock + Lotion Anti Nyamuk','Paket sunblock SPF50 dan losion anti-nyamuk DEET, travel size.','medis_2.jpg',7000.00,34,34,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(31,12,'Kursi Lipat Camping','Kursi lipat aluminium dengan sandaran, kapasitas 120kg, ada cup holder.','pendukung_1.jpg',15000.00,18,18,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(32,12,'Meja Lipat Portable','Meja lipat aluminium portable, compact, cocok untuk memasak atau makan.','pendukung_2.jpg',12000.00,13,13,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(33,11,'Pisau Lipat Serbaguna','Pisau lipat stainless steel dengan pembuka botol, gergaji kecil, dan obeng.','pendukung_1.jpg',8000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(34,13,'Tali Pramuka 10 Meter','Tali paracord 10 meter, kuat 250kg, serbaguna untuk tenda, jemuran, dll.','pendukung_2.jpg',5000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(35,8,'Kompas Prismatik Profesional','Kompas prismatik militer-grade, luminous dial, akurasi tinggi.','navigasi_1.jpg',8000.00,10,10,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(36,8,'Peta Topografi Lokal','Peta topografi laminasi tahan air, skala 1:25.000, area gunung populer.','navigasi_2.jpg',5000.00,24,24,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(37,8,'GPS Genggam Garmin','Perangkat GPS genggam Garmin dengan peta Indonesia, baterai tahan 16 jam.','navigasi_1.jpg',35000.00,8,8,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(38,8,'Altimeter Digital','Altimeter digital dengan barometer dan termometer, akurasi ??1 meter.','navigasi_2.jpg',12000.00,8,8,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(39,9,'Filter Air Portable Sawyer','Filter air portable Sawyer, menyaring 99.99% bakteri, kapasitas 1 juta liter.','air_1.jpg',15000.00,17,17,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(40,9,'Straw Filter Personal','Sedotan pemurni air personal, langsung minum dari sumber air alam.','air_1.jpg',10000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(41,9,'Tablet Pemurnian Air (50 tab)','Tablet purifikasi air isi 50, setiap tablet memurnikan 1 liter air dalam 30 menit.','air_1.jpg',8000.00,33,33,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(42,9,'Jeriken Lipat 10 Liter','Jeriken lipat BPA-free kapasitas 10L, compact saat kosong, keran built-in.','air_2.jpg',10000.00,15,15,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(43,10,'Trekking Pole Aluminium (pair)','Sepasang trekking pole aluminium adjustable 65-135cm, anti-shock, grip EVA foam.','jalan_1.jpg',15000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(44,10,'Carrier 60 Liter','Tas carrier 60L frame internal ergonomis, rain cover included, banyak kompartemen.','jalan_2.jpg',35000.00,15,15,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(45,10,'Carrier 45 Liter','Tas carrier 45L ringan, cocok pendakian 2-3 hari, rain cover included.','jalan_3.jpg',30000.00,18,18,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(46,5,'Gaiter Pelindung Kaki','Gaiter waterproof pelindung celana dan sepatu dari lumpur, pasir, dan pacet.','jalan_1.jpg',8000.00,20,20,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(47,10,'Daypack 25 Liter','Tas daypack 25L untuk summit attack atau day hike, ringan dan compact.','jalan_2.jpg',15000.00,22,22,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(48,11,'Kapak Kecil Camping','Kapak kecil dengan sarung pengaman, untuk memotong dan membelah kayu bakar.','survival_1.jpg',12000.00,11,11,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(49,11,'Gergaji Lipat Portable','Gergaji lipat serbaguna, mata pisau baja karbon, untuk ranting dan kayu kecil.','survival_2.jpg',10000.00,11,11,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(50,11,'Sekop Lipat Mini','Sekop lipat mini multifungsi (sekop + pickaxe), untuk parit air sekitar tenda.','survival_1.jpg',8000.00,15,15,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(51,11,'Pemantik Api Waterproof','Pemantik api tahan air dengan flint stone cadangan, nyala api di segala kondisi.','survival_2.jpg',5000.00,27,27,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(52,11,'Multi-Tools 15-in-1','Multi-tools stainless: tang, obeng, pisau, gunting, pembuka kaleng/botol, dll.','survival_1.jpg',10000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(53,2,'Hammock Ultralight + Tali','Hammock ultralight dengan tali webbing dan carabiner, kapasitas 150kg.','comfort_1.jpg',18000.00,17,17,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(54,1,'Tarp Tent 3x3 Meter','Tarp tent waterproof 3??3m dengan tiang, untuk area ruang tamu outdoor.','comfort_2.jpg',20000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(55,2,'Matras Piknik 200x150cm','Matras piknik waterproof bagian bawah, estetik untuk area duduk bersama.','comfort_2.jpg',10000.00,25,25,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(56,2,'Hammock Stand Portable','Stand hammock portable aluminium, bisa dipasang tanpa pohon.','comfort_2.jpg',25000.00,15,15,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(57,13,'Peluit Darurat Tanpa Bola','Peluit darurat high-pitch tanpa bola, tetap berbunyi saat basah, 120dB.','komunikasi_1.jpg',3000.00,30,30,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(58,13,'Handy Talkie (HT) UHF','Handy talkie UHF jangkauan 5km, baterai rechargeable, cocok area tanpa sinyal.','komunikasi_2.jpg',20000.00,8,8,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05'),(59,6,'Survival Blanket Aluminium','Selimut aluminium foil darurat, memantulkan 90% panas tubuh, mencegah hipotermia.','komunikasi_1.jpg',5000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-21 23:11:52'),(60,13,'Mirror Signal Darurat','Cermin sinyal darurat untuk memantulkan cahaya matahari sebagai SOS visual.','komunikasi_2.jpg',3000.00,16,16,'tersedia','2026-06-17 17:27:11','2026-06-23 17:43:05');
/*!40000 ALTER TABLE `barang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_reservasi`
--

DROP TABLE IF EXISTS `detail_reservasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_reservasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservasi_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `harga_satuan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `reservasi_id` (`reservasi_id`),
  KEY `barang_id` (`barang_id`),
  CONSTRAINT `detail_reservasi_ibfk_1` FOREIGN KEY (`reservasi_id`) REFERENCES `reservasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `detail_reservasi_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_reservasi`
--

LOCK TABLES `detail_reservasi` WRITE;
/*!40000 ALTER TABLE `detail_reservasi` DISABLE KEYS */;
INSERT INTO `detail_reservasi` VALUES (3,2,2,1,35000.00,35000.00),(4,2,1,1,50000.00,50000.00);
/*!40000 ALTER TABLE `detail_reservasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text,
  `icon` varchar(50) DEFAULT 'bi-box',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

LOCK TABLES `kategori` WRITE;
/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
INSERT INTO `kategori` VALUES (1,'Shelter & Tenda','Perlindungan dari cuaca: tenda dome, flysheet, footprint, terpal, dan pasak cadangan.','bi-house-door','2026-06-17 17:27:11'),(2,'Perlengkapan Tidur','Sistem tidur outdoor: sleeping bag, matras, bantal tiup, dan selimut.','bi-moon-stars','2026-06-17 17:27:11'),(3,'Dapur Lapangan','Logistik & alat masak: kompor portable, cooking set, perlengkapan makan, cooler box.','bi-fire','2026-06-17 17:27:11'),(4,'Penerangan & Elektronik','Sumber cahaya: headlamp, senter, lampu tenda, lentera, baterai, powerbank.','bi-lightbulb','2026-06-17 17:27:11'),(5,'Pakaian & Alas Kaki','Jaket tahan angin, baju dry-fit, jas hujan, sepatu dan sandal gunung.','bi-person-arms-up','2026-06-17 17:27:11'),(6,'Keselamatan & Medis','Kotak P3K, trash bag, perlengkapan mandi, sunblock, losion anti-nyamuk.','bi-heart-pulse','2026-06-17 17:27:11'),(7,'Peralatan Pendukung','Kursi lipat, meja lipat, pisau lipat serbaguna, tali pramuka/tenda.','bi-tools','2026-06-17 17:27:11'),(8,'Navigasi & Orientasi','Kompas fisik, peta topografi, GPS genggam, altimeter.','bi-compass','2026-06-17 17:27:11'),(9,'Pemurnian Air','Filter air portable, straw filter, tablet pemurnian, jeriken lipat.','bi-droplet','2026-06-17 17:27:11'),(10,'Alat Bantu Jalan','Trekking pole, tas carrier/backpack, gaiter pelindung.','bi-signpost-2','2026-06-17 17:27:11'),(11,'Pertukangan & Survival','Kapak kecil, gergaji lipat, sekop lipat, pemantik waterproof, multi-tools.','bi-hammer','2026-06-17 17:27:11'),(12,'Kenyamanan Camp','Hammock, tarp tent tambahan, matras piknik untuk area bersama.','bi-tree','2026-06-17 17:27:11'),(13,'Komunikasi & Keamanan','Peluit darurat, handy talkie (HT), survival blanket aluminium foil.','bi-broadcast','2026-06-17 17:27:11');
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `konten`
--

DROP TABLE IF EXISTS `konten`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `konten` (
  `id` int NOT NULL AUTO_INCREMENT,
  `section` varchar(50) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_section_key` (`section`,`key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `konten`
--

LOCK TABLES `konten` WRITE;
/*!40000 ALTER TABLE `konten` DISABLE KEYS */;
INSERT INTO `konten` VALUES (9,'hero','badge','?????? Platform Perlengkapan Outdoor #1',NULL),(10,'hero','title','Petualangan Dimulai Dari Persiapan Terbaik',NULL),(11,'hero','subtitle','Sewa alat camping & pendakian berkualitas, booking online, harga transparan ??? siap untuk petualangan berikutnya?',NULL),(12,'about','title','Tentang SIMPEL-CAMP',NULL),(13,'about','description','Platform sewa alat camping dan pendakian terlengkap. Kami menyediakan peralatan berkualitas tinggi untuk memastikan petualangan alam Anda aman dan nyaman.',NULL),(14,'footer','whatsapp','+62 812-3456-7890',NULL),(15,'footer','email','info@simpelcamp.com',NULL),(16,'footer','alamat','Jl. Pegunungan No. 123, Kota Petualang, Indonesia',NULL),(17,'footer','instagram','#',NULL),(18,'footer','facebook','#',NULL),(19,'footer','tiktok','#',NULL);
/*!40000 ALTER TABLE `konten` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_aktivitas`
--

DROP TABLE IF EXISTS `log_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `aksi` varchar(100) NOT NULL,
  `detail` text,
  `tabel` varchar(50) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  KEY `idx_log_created` (`created_at`),
  CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_aktivitas`
--

LOCK TABLES `log_aktivitas` WRITE;
/*!40000 ALTER TABLE `log_aktivitas` DISABLE KEYS */;
INSERT INTO `log_aktivitas` VALUES (13,2,'approve_reservasi','Menyetujui reservasi RSV-001 - Budi Santoso',NULL,NULL,'127.0.0.1',NULL,'2026-06-01 09:00:00'),(14,2,'approve_reservasi','Menyetujui reservasi RSV-002 - Siti Rahayu',NULL,NULL,'127.0.0.1',NULL,'2026-06-03 09:30:00'),(15,2,'approve_reservasi','Menyetujui reservasi RSV-003 - Reza Rahadian',NULL,NULL,'127.0.0.1',NULL,'2026-06-05 08:15:00'),(16,2,'approve_reservasi','Menyetujui reservasi RSV-005 - Eko Prasetyo',NULL,NULL,'127.0.0.1',NULL,'2026-06-10 08:45:00'),(17,2,'reject_reservasi','Menolak reservasi RSV-013 - stok habis',NULL,NULL,'127.0.0.1',NULL,'2026-06-15 11:00:00'),(18,2,'proses_pengembalian','Memproses pengembalian TRX-001 - kondisi baik',NULL,NULL,'127.0.0.1',NULL,'2026-06-04 14:00:00'),(19,2,'proses_pengembalian','Memproses pengembalian TRX-003 - rusak ringan, denda Rp 15.000',NULL,NULL,'127.0.0.1',NULL,'2026-06-10 15:30:00'),(20,2,'approve_perpanjangan','Menyetujui perpanjangan RSV-009 +1 hari',NULL,NULL,'127.0.0.1',NULL,'2026-06-21 10:00:00'),(21,3,'buat_reservasi','Membuat reservasi RSV-008 - 2 barang',NULL,NULL,'192.168.1.5',NULL,'2026-06-14 20:00:00'),(22,5,'buat_reservasi','Membuat reservasi RSV-010',NULL,NULL,'192.168.1.10',NULL,'2026-06-18 19:30:00'),(23,2,'login','Admin login ke dashboard',NULL,NULL,'127.0.0.1',NULL,'2026-06-21 15:29:34'),(24,2,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 22:38:05'),(26,2,'Update Barang','Mengupdate barang: Tenda Dome Eiger 4P (ID: 1)','barang',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 23:22:38'),(27,2,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 00:39:11'),(28,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:25:39'),(29,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 15:56:10'),(30,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 16:58:57'),(31,2,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:00:14'),(32,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:32:19'),(33,2,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:33:50'),(34,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:34:44'),(35,2,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:35:54'),(36,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:38:30'),(37,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 18:36:44'),(38,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 18:37:24'),(39,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 19:37:50'),(40,3,'LOGIN','User login berhasil',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 22:36:03'),(41,3,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 20:42:53'),(42,2,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 20:54:34'),(43,1,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 20:55:21'),(44,2,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 20:56:42'),(45,2,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-28 19:26:01'),(46,3,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-28 19:36:09'),(47,2,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-28 21:29:33'),(48,1,'LOGIN','User login berhasil',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-28 22:34:14');
/*!40000 ALTER TABLE `log_aktivitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_level`
--

DROP TABLE IF EXISTS `member_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `level` enum('regular','bronze','silver','gold') NOT NULL DEFAULT 'regular',
  `total_transaksi` int NOT NULL DEFAULT '0',
  `total_sewa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `poin` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `member_level_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_level`
--

LOCK TABLES `member_level` WRITE;
/*!40000 ALTER TABLE `member_level` DISABLE KEYS */;
INSERT INTO `member_level` VALUES (1,3,'regular',0,0.00,0,'2026-06-17 17:12:11',NULL),(2,4,'regular',0,0.00,0,'2026-06-17 17:12:11',NULL);
/*!40000 ALTER TABLE `member_level` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifikasi`
--

DROP TABLE IF EXISTS `notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifikasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` varchar(50) NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_read` (`user_id`,`is_read`),
  CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifikasi`
--

LOCK TABLES `notifikasi` WRITE;
/*!40000 ALTER TABLE `notifikasi` DISABLE KEYS */;
INSERT INTO `notifikasi` VALUES (1,1,'Pesanan Baru + Pembayaran','Pesanan RSV-GYDEF6UR dari Budi Santoso menunggu konfirmasi pembayaran.','reservasi','?page=pembayaran',0,'2026-06-23 19:07:34'),(2,2,'Pesanan Baru + Pembayaran','Pesanan RSV-GYDEF6UR dari Budi Santoso menunggu konfirmasi pembayaran.','reservasi','?page=pembayaran',0,'2026-06-23 19:07:34'),(3,3,'Pembayaran Lunas & Barang Diambil','Pembayaran untuk reservasi Anda telah dikonfirmasi dan status pesanan menjadi Aktif.','transaksi','?page=pesanan',0,'2026-06-28 19:39:48'),(4,3,'Penyewaan Selesai','Terima kasih telah menyewa! Barang untuk reservasi RSV-GYDEF6UR telah dikembalikan dengan sukses.','transaksi','?page=pesanan',0,'2026-06-28 22:11:50');
/*!40000 ALTER TABLE `notifikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembayaran`
--

DROP TABLE IF EXISTS `pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembayaran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaksi_id` int NOT NULL,
  `metode` enum('transfer','ewallet','qris','cash') NOT NULL,
  `jumlah` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `status` enum('pending','dikonfirmasi','ditolak') NOT NULL DEFAULT 'pending',
  `catatan` text,
  `tanggal_bayar` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksi_id` (`transaksi_id`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembayaran`
--

LOCK TABLES `pembayaran` WRITE;
/*!40000 ALTER TABLE `pembayaran` DISABLE KEYS */;
INSERT INTO `pembayaran` VALUES (1,2,'cash',85000.00,NULL,'dikonfirmasi',NULL,'2026-06-28 19:35:07',NULL);
/*!40000 ALTER TABLE `pembayaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan`
--

DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text,
  `kategori` varchar(50) NOT NULL DEFAULT 'umum',
  `deskripsi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan`
--

LOCK TABLES `pengaturan` WRITE;
/*!40000 ALTER TABLE `pengaturan` DISABLE KEYS */;
INSERT INTO `pengaturan` VALUES (1,'nama_toko','SIMPEL-CAMP','umum','Nama toko/bisnis'),(2,'alamat_toko','Jl. Pegunungan No. 123, Kota Petualang, Indonesia','umum','Alamat toko'),(3,'no_telp_toko','+62 812-3456-7890','umum','Nomor telepon toko'),(4,'email_toko','info@simpelcamp.com','umum','Email toko'),(5,'jam_buka','08:00','umum','Jam buka toko'),(6,'jam_tutup','21:00','umum','Jam tutup toko'),(7,'deposit_persen','30','sewa','Persentase deposit dari total biaya sewa'),(8,'denda_per_hari_persen','10','sewa','Persentase denda per hari keterlambatan dari total biaya'),(9,'max_perpanjangan','7','sewa','Maksimal perpanjangan sewa (hari)'),(10,'min_sewa_hari','1','sewa','Minimal durasi sewa (hari)'),(11,'max_sewa_hari','30','sewa','Maksimal durasi sewa (hari)'),(12,'bronze_min_transaksi','5','member','Minimal transaksi untuk level Bronze'),(13,'silver_min_transaksi','15','member','Minimal transaksi untuk level Silver'),(14,'gold_min_transaksi','30','member','Minimal transaksi untuk level Gold'),(15,'bronze_diskon','5','member','Diskon (%) untuk member Bronze'),(16,'silver_diskon','10','member','Diskon (%) untuk member Silver'),(17,'gold_diskon','15','member','Diskon (%) untuk member Gold'),(18,'maintenance_mode','0','sistem','Mode maintenance (0=off, 1=on)'),(19,'registrasi_aktif','1','sistem','Registrasi pelanggan baru (0=off, 1=on)');
/*!40000 ALTER TABLE `pengaturan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengeluaran`
--

DROP TABLE IF EXISTS `pengeluaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengeluaran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori_pengeluaran` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `jumlah` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tanggal` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengeluaran`
--

LOCK TABLES `pengeluaran` WRITE;
/*!40000 ALTER TABLE `pengeluaran` DISABLE KEYS */;
INSERT INTO `pengeluaran` VALUES (1,'Pembelian Stok','Restok tenda dome 4P (2 unit)',500000.00,'2026-01-15','2026-06-22 00:50:08','2026-06-22 00:50:08'),(2,'Perawatan','Cuci dan perbaikan sleeping bag',150000.00,'2026-01-20','2026-06-22 00:50:08','2026-06-22 00:50:08'),(3,'Operasional','Listrik dan internet bulanan Januari',200000.00,'2026-01-28','2026-06-22 00:50:08','2026-06-22 00:50:08'),(4,'Pembelian Stok','Beli carrier 60L baru (1 unit)',350000.00,'2026-02-10','2026-06-22 00:50:08','2026-06-22 00:50:08'),(5,'Perawatan','Service kompor portable (3 unit)',120000.00,'2026-02-18','2026-06-22 00:50:08','2026-06-22 00:50:08'),(6,'Operasional','Listrik dan internet bulanan Februari',200000.00,'2026-02-28','2026-06-22 00:50:08','2026-06-22 00:50:08'),(7,'Pembelian Stok','Restok matras tiup ultralight (3 unit)',400000.00,'2026-03-05','2026-06-22 00:50:08','2026-06-22 00:50:08'),(8,'Perawatan','Cuci tenda dan flysheet (5 unit)',175000.00,'2026-03-15','2026-06-22 00:50:08','2026-06-22 00:50:08'),(9,'Operasional','Listrik dan internet bulanan Maret',200000.00,'2026-03-28','2026-06-22 00:50:08','2026-06-22 00:50:08'),(10,'Lainnya','Biaya marketing online',100000.00,'2026-03-30','2026-06-22 00:50:08','2026-06-22 00:50:08'),(11,'Pembelian Stok','Beli headlamp LED baru (5 unit)',250000.00,'2026-04-08','2026-06-22 00:50:08','2026-06-22 00:50:08'),(12,'Perawatan','Perbaikan carrier rusak (2 unit)',180000.00,'2026-04-20','2026-06-22 00:50:08','2026-06-22 00:50:08'),(13,'Operasional','Listrik dan internet bulanan April',200000.00,'2026-04-28','2026-06-22 00:50:08','2026-06-22 00:50:08'),(14,'Lainnya','Cetak brosur dan stiker',80000.00,'2026-04-25','2026-06-22 00:50:08','2026-06-22 00:50:08'),(15,'Pembelian Stok','Restok sleeping bag polar (3 unit)',450000.00,'2026-05-07','2026-06-22 00:50:08','2026-06-22 00:50:08'),(16,'Perawatan','Service lentera dan lampu tenda',100000.00,'2026-05-15','2026-06-22 00:50:08','2026-06-22 00:50:08'),(17,'Operasional','Listrik dan internet bulanan Mei',200000.00,'2026-05-28','2026-06-22 00:50:08','2026-06-22 00:50:08'),(18,'Lainnya','Transportasi antar barang ke pelanggan',120000.00,'2026-05-20','2026-06-22 00:50:08','2026-06-22 00:50:08'),(22,'Lainnya','Packing supplies (plastik, tali, label)',75000.00,'2026-06-18','2026-06-22 00:50:08','2026-06-22 00:50:08');
/*!40000 ALTER TABLE `pengeluaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengembalian`
--

DROP TABLE IF EXISTS `pengembalian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengembalian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaksi_id` int NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `hari_terlambat` int NOT NULL DEFAULT '0',
  `kondisi_barang` enum('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik',
  `denda` decimal(12,2) NOT NULL DEFAULT '0.00',
  `catatan` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaksi_id` (`transaksi_id`),
  CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengembalian`
--

LOCK TABLES `pengembalian` WRITE;
/*!40000 ALTER TABLE `pengembalian` DISABLE KEYS */;
/*!40000 ALTER TABLE `pengembalian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perpanjangan`
--

DROP TABLE IF EXISTS `perpanjangan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perpanjangan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservasi_id` int NOT NULL,
  `tanggal_lama` date NOT NULL,
  `tanggal_baru` date NOT NULL,
  `tambahan_hari` int NOT NULL DEFAULT '0',
  `biaya_tambahan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `metode_bayar` enum('transfer','ewallet','qris') NOT NULL,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `alasan` text,
  `status` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `alasan_tolak` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservasi_id` (`reservasi_id`),
  CONSTRAINT `perpanjangan_ibfk_1` FOREIGN KEY (`reservasi_id`) REFERENCES `reservasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perpanjangan`
--

LOCK TABLES `perpanjangan` WRITE;
/*!40000 ALTER TABLE `perpanjangan` DISABLE KEYS */;
/*!40000 ALTER TABLE `perpanjangan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo`
--

DROP TABLE IF EXISTS `promo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tipe` enum('persentase','nominal') NOT NULL DEFAULT 'persentase',
  `nilai` decimal(12,2) NOT NULL DEFAULT '0.00',
  `min_transaksi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `mulai` date NOT NULL,
  `selesai` date NOT NULL,
  `kuota` int NOT NULL DEFAULT '0',
  `gambar` varchar(255) DEFAULT NULL,
  `terpakai` int NOT NULL DEFAULT '0',
  `status` enum('aktif','nonaktif','expired') NOT NULL DEFAULT 'aktif',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo`
--

LOCK TABLES `promo` WRITE;
/*!40000 ALTER TABLE `promo` DISABLE KEYS */;
INSERT INTO `promo` VALUES (1,'CAMP20','Diskon Camping Spesial','persentase',20.00,50000.00,'2026-06-01','2026-07-31',100,NULL,0,'aktif','2026-06-23 18:44:19'),(2,'HEMAT15','Hemat 15% Paket Pendakian','persentase',15.00,75000.00,'2026-06-15','2026-07-15',50,NULL,0,'aktif','2026-06-23 18:44:19'),(3,'NEWUSER','Welcome Bonus Pelanggan Baru','nominal',25000.00,100000.00,'2026-06-01','2026-08-31',200,NULL,0,'aktif','2026-06-23 18:44:19'),(4,'WEEKEND','Promo Weekend Seru','persentase',10.00,30000.00,'2026-06-20','2026-07-20',80,NULL,0,'aktif','2026-06-23 18:44:19');
/*!40000 ALTER TABLE `promo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservasi`
--

DROP TABLE IF EXISTS `reservasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_reservasi` varchar(20) NOT NULL,
  `user_id` int NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `total_biaya` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deposit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `promo_id` int DEFAULT NULL,
  `diskon` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','disetujui','ditolak','aktif','selesai','batal') NOT NULL DEFAULT 'pending',
  `catatan` text,
  `alasan_tolak` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_reservasi` (`kode_reservasi`),
  KEY `idx_reservasi_user` (`user_id`),
  KEY `idx_reservasi_status` (`status`),
  KEY `idx_reservasi_tanggal` (`tanggal_mulai`,`tanggal_selesai`),
  CONSTRAINT `reservasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservasi`
--

LOCK TABLES `reservasi` WRITE;
/*!40000 ALTER TABLE `reservasi` DISABLE KEYS */;
INSERT INTO `reservasi` VALUES (2,'RSV-GYDEF6UR',3,'2026-06-23','2026-06-25',85000.00,25500.00,NULL,0.00,'selesai','',NULL,'2026-06-23 19:07:34','2026-06-28 22:11:50');
/*!40000 ALTER TABLE `reservasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `reservasi_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `tipe` enum('online','walk_in') NOT NULL DEFAULT 'online',
  `total_bayar` decimal(12,2) NOT NULL DEFAULT '0.00',
  `denda` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('menunggu_bayar','dibayar','aktif','selesai') NOT NULL DEFAULT 'menunggu_bayar',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `reservasi_id` (`reservasi_id`),
  KEY `idx_transaksi_user` (`user_id`),
  KEY `idx_transaksi_status` (`status`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`reservasi_id`) REFERENCES `reservasi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
INSERT INTO `transaksi` VALUES (2,'TRX-O00HS4GM',2,3,'walk_in',85000.00,0.00,'selesai','2026-06-23 19:07:34','2026-06-28 22:11:50');
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text,
  `foto` varchar(255) DEFAULT NULL,
  `role` enum('pelanggan','admin','superadmin') NOT NULL DEFAULT 'pelanggan',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Administrator','superadmin@simpelcamp.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G','081234567890','Jl. Admin No. 1, Kota Petualang',NULL,'superadmin','aktif','2026-06-17 17:12:11','2026-06-28 22:34:14','2026-06-28 22:34:14'),(2,'Admin Toko','admin@simpelcamp.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G','081234567891','Jl. Admin No. 2, Kota Petualang',NULL,'admin','aktif','2026-06-17 17:12:11','2026-06-28 21:29:33','2026-06-28 21:29:33'),(3,'Budi Santoso','budi@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G','081234567892','Jl. Pendaki No. 10, Bandung',NULL,'pelanggan','aktif','2026-06-17 17:12:11','2026-06-28 19:36:09','2026-06-28 19:36:09'),(4,'Siti Rahayu','siti@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G','081234567893','Jl. Gunung No. 5, Malang',NULL,'pelanggan','aktif','2026-06-17 17:12:11','2026-06-21 22:37:33',NULL),(5,'Reza Rahadian','reza@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G',NULL,NULL,NULL,'pelanggan','aktif','2026-06-21 22:26:52','2026-06-21 22:37:33',NULL),(6,'Maya Sari','maya@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G',NULL,NULL,NULL,'pelanggan','aktif','2026-06-21 22:26:52','2026-06-21 22:37:33',NULL),(7,'Eko Prasetyo','eko@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G',NULL,NULL,NULL,'pelanggan','aktif','2026-06-21 22:26:52','2026-06-21 22:37:33',NULL),(8,'Dewi Lestari','dewi@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G',NULL,NULL,NULL,'pelanggan','aktif','2026-06-21 22:26:52','2026-06-21 22:37:33',NULL),(9,'Ahmad Yani','ahmad@email.com','$2y$10$DD3xfVVpST4/i6feFZeWSeZHvC.szASD5ztvbhqjcYVavFmPhnl.G',NULL,NULL,NULL,'pelanggan','aktif','2026-06-21 22:26:52','2026-06-21 22:37:33',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_barang` (`user_id`,`barang_id`),
  KEY `barang_id` (`barang_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (3,3,2,1,'2026-06-23','2026-06-24','2026-06-23 19:12:32','2026-06-23 19:12:55');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-28 22:36:02

