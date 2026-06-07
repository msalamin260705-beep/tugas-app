-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 07, 2026 at 04:53 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aplikasi_tugas`
--
CREATE DATABASE IF NOT EXISTS `aplikasi_tugas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `aplikasi_tugas`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `nama`, `email`, `password`, `foto`) VALUES
(1, 'Admin Pusat', 'admin@gmail.com', '$2y$10$V.8apZToCf.oXHPZPdRt8uI.f3eNElQfvcD32JLAHb75Z1tY3xQ6e', '');

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int NOT NULL,
  `nidn` varchar(30) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `mata_kuliah` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `nidn`, `nama`, `email`, `password`, `mata_kuliah`, `foto`) VALUES
(1, '235367876332', 'M. Salamin S.Kom, M.Kom,.', 'dosen@gmail.com', '$2y$10$H2Gi3PAGasncDrzPMfyPsuFOE0DDAnHLkV7vwmpFNf8Q96c8v4fHu', 'BASIS DATA LANJUT', '1780037221WhatsApp Image 2026-01-09 at 19.57.36.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int NOT NULL,
  `nim` varchar(30) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `semester` int NOT NULL,
  `kelas` varchar(5) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `nama`, `semester`, `kelas`, `email`, `password`, `foto`) VALUES
(1, '202469040058', 'M.SALMAN', 4, 'B', 'msalamin260705@gmail.com', '$2y$10$rpMBKKX6h3L4131iolGS7OYIDzHDj0tRPy/Q8c1kdQD.8FVOPomR2', '1780062896_WhatsApp Image 2026-01-09 at 19.52.24.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `pengumpulan`
--

CREATE TABLE `pengumpulan` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `tugas_id` int DEFAULT NULL,
  `file_tugas` varchar(255) DEFAULT NULL,
  `tanggal_pengumpulan` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id` int NOT NULL,
  `dosen_id` int DEFAULT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `mata_kuliah` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `semester` int DEFAULT NULL,
  `kelas` varchar(10) DEFAULT NULL,
  `deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tugas`
--

INSERT INTO `tugas` (`id`, `dosen_id`, `judul`, `mata_kuliah`, `deskripsi`, `semester`, `kelas`, `deadline`) VALUES
(1, 1, 'membuat video tutorial', 'BASIS DATA LANJUT', 'individu', 4, 'B', '2026-05-29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tugas_id` (`tugas_id`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD CONSTRAINT `pengumpulan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumpulan_ibfk_2` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE;
--
-- Database: `aswoja_app`
--
CREATE DATABASE IF NOT EXISTS `aswoja_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `aswoja_app`;

-- --------------------------------------------------------

--
-- Table structure for table `identitas`
--

CREATE TABLE `identitas` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nim` varchar(30) NOT NULL,
  `prodi` varchar(100) NOT NULL,
  `jenjang` varchar(30) NOT NULL,
  `universitas` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `identitas`
--

INSERT INTO `identitas` (`id`, `user_id`, `nim`, `prodi`, `jenjang`, `universitas`, `created_at`) VALUES
(1, 2, '202469040058', 'Teknik informatika', 'S1', 'Universitas Yudharta Pasuruan', '2026-05-10 23:03:30'),
(2, 3, '202469040058', 'Teknik Informatika', 'S1S1', 'Universitas Yudharta Pasuruan', '2026-05-10 23:28:52');

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliahs`
--

CREATE TABLE `mata_kuliahs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nama_mk` varchar(120) NOT NULL,
  `dosen` varchar(120) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mata_kuliahs`
--

INSERT INTO `mata_kuliahs` (`id`, `user_id`, `nama_mk`, `dosen`, `semester`, `created_at`) VALUES
(1, 2, 'ASWAJA', 'PAK SAMSUL', '2', '2026-05-10 23:04:19'),
(2, 3, 'Aswaja', 'Pak Samsul', '2', '2026-05-10 23:33:32'),
(3, 2, 'RPL', 'Pak Walidin', '2', '2026-05-10 23:59:32');

-- --------------------------------------------------------

--
-- Table structure for table `tugas_kuliahs`
--

CREATE TABLE `tugas_kuliahs` (
  `id` int NOT NULL,
  `mata_kuliah_id` int NOT NULL,
  `judul` varchar(140) NOT NULL,
  `deskripsi` text NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('belum','selesai') NOT NULL DEFAULT 'belum',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tugas_kuliahs`
--

INSERT INTO `tugas_kuliahs` (`id`, `mata_kuliah_id`, `judul`, `deskripsi`, `deadline`, `status`, `created_at`) VALUES
(1, 1, 'debat', 'debat', '2026-05-10', 'belum', '2026-05-10 23:05:31'),
(2, 2, 'Tugas tentang apa', 'dikumpulkan di kelas', '2026-05-11', 'belum', '2026-05-10 23:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@aswoja.test', '$2y$10$b8KS0Ol47qfMMBNeXcpENupM0IzCEmNOphgPojhJ/W0A/ctlQhf1a', 'admin', '2026-05-10 22:52:15'),
(2, 'M. SALAMIN', 'msalamin260705@gmail.com', '$2y$10$pnpD74O4VDgiCRjnn7a2AO55lEf2c7CMc4hGCHxXjPiI6VPAjWHDq', 'user', '2026-05-10 23:03:18'),
(3, 'Budi Uji', 'budiuji@test.local', '$2y$10$08xho.bhcSETazPLvHFUhO1hXoJTpQS/zWGOdQ7ueVTrxkRfo8IZe', 'user', '2026-05-10 23:23:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `identitas`
--
ALTER TABLE `identitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mata_kuliahs`
--
ALTER TABLE `mata_kuliahs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tugas_kuliahs`
--
ALTER TABLE `tugas_kuliahs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mata_kuliah_id` (`mata_kuliah_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `identitas`
--
ALTER TABLE `identitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mata_kuliahs`
--
ALTER TABLE `mata_kuliahs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tugas_kuliahs`
--
ALTER TABLE `tugas_kuliahs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `identitas`
--
ALTER TABLE `identitas`
  ADD CONSTRAINT `identitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mata_kuliahs`
--
ALTER TABLE `mata_kuliahs`
  ADD CONSTRAINT `mata_kuliahs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas_kuliahs`
--
ALTER TABLE `tugas_kuliahs`
  ADD CONSTRAINT `tugas_kuliahs_ibfk_1` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliahs` (`id`) ON DELETE CASCADE;
--
-- Database: `db_basisdata_lanjut`
--
CREATE DATABASE IF NOT EXISTS `db_basisdata_lanjut` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `db_basisdata_lanjut`;

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` int NOT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nama_mahasiswa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `nim`, `nama_mahasiswa`) VALUES
(1, '202469040083', 'Ahmad'),
(2, '202469040064', 'Budi'),
(3, '202469040086', 'Citra'),
(4, '202469040076', 'Dian'),
(5, '202469040050', 'Eko'),
(6, '202469040099', 'Farhan');

-- --------------------------------------------------------

--
-- Table structure for table `materi_sql`
--

CREATE TABLE `materi_sql` (
  `id_materi` int NOT NULL,
  `nama_materi` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `materi_sql`
--

INSERT INTO `materi_sql` (`id_materi`, `nama_materi`) VALUES
(1, 'Where'),
(2, 'Operator logika'),
(3, 'Like dan Not Like'),
(4, 'Group By'),
(5, 'Inner Join'),
(6, 'Right Join');

-- --------------------------------------------------------

--
-- Table structure for table `pembagian_tugas`
--

CREATE TABLE `pembagian_tugas` (
  `id_tugas` int NOT NULL,
  `id_mahasiswa` int DEFAULT NULL,
  `id_materi` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembagian_tugas`
--

INSERT INTO `pembagian_tugas` (`id_tugas`, `id_mahasiswa`, `id_materi`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 4, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`);

--
-- Indexes for table `materi_sql`
--
ALTER TABLE `materi_sql`
  ADD PRIMARY KEY (`id_materi`);

--
-- Indexes for table `pembagian_tugas`
--
ALTER TABLE `pembagian_tugas`
  ADD PRIMARY KEY (`id_tugas`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`),
  ADD KEY `id_materi` (`id_materi`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `materi_sql`
--
ALTER TABLE `materi_sql`
  MODIFY `id_materi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pembagian_tugas`
--
ALTER TABLE `pembagian_tugas`
  MODIFY `id_tugas` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pembagian_tugas`
--
ALTER TABLE `pembagian_tugas`
  ADD CONSTRAINT `pembagian_tugas_ibfk_1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`),
  ADD CONSTRAINT `pembagian_tugas_ibfk_2` FOREIGN KEY (`id_materi`) REFERENCES `materi_sql` (`id_materi`);
--
-- Database: `db_tugas`
--
CREATE DATABASE IF NOT EXISTS `db_tugas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `db_tugas`;

-- --------------------------------------------------------

--
-- Table structure for table `informasi`
--

CREATE TABLE `informasi` (
  `id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `admin_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `poster` varchar(255) DEFAULT NULL,
  `warna_bg` varchar(20) DEFAULT '#1a3a5c',
  `tipe` enum('teks','poster') DEFAULT 'teks'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `informasi`
--

INSERT INTO `informasi` (`id`, `judul`, `isi`, `admin_id`, `created_at`, `poster`, `warna_bg`, `tipe`) VALUES
(1, 'UJIAN AKHIR SEMESTER PADA TANGGAL 14 JUNI 2026 - 20 JUNI 2026', 'Dimohon kepada seluruh mahasiswa untuk melunasi pembayaran secepat mungkin', 4, '2026-06-03 02:42:14', 'poster_1780678418.jpeg', '#1a3a5c', 'poster');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int NOT NULL,
  `kelas_id` int NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `ruangan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `kelas_id`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`) VALUES
(1, 2, 'Senin', '10:00:00', '13:00:00', 'Lab. Jarkom'),
(2, 3, 'Kamis', '09:00:00', '11:00:00', 'Lab. Jarkom');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int NOT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `kode_matkul` varchar(20) NOT NULL,
  `semester` int NOT NULL,
  `kelas` varchar(5) NOT NULL,
  `sks` int DEFAULT '3',
  `tahun_ajaran` varchar(20) NOT NULL,
  `dosen_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `kode_matkul`, `semester`, `kelas`, `sks`, `tahun_ajaran`, `dosen_id`) VALUES
(2, 'Basis Data', 'PNS100', 4, 'B', 3, '2026/2027', 5),
(3, 'Jaringan Komputer', 'PNS101', 2, 'B', 2, '2026/2027', 6);

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa_kelas`
--

CREATE TABLE `mahasiswa_kelas` (
  `id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `kelas_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa_kelas`
--

INSERT INTO `mahasiswa_kelas` (`id`, `mahasiswa_id`, `kelas_id`) VALUES
(2, 8, 2),
(3, 8, 3);

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_semester`
--

CREATE TABLE `pengajuan_semester` (
  `id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `semester_asal` int NOT NULL,
  `semester_tujuan` int NOT NULL,
  `kelas_asal` varchar(10) NOT NULL,
  `pesan` text,
  `status` enum('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  `catatan_admin` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengajuan_semester`
--

INSERT INTO `pengajuan_semester` (`id`, `mahasiswa_id`, `semester_asal`, `semester_tujuan`, `kelas_asal`, `pesan`, `status`, `catatan_admin`, `created_at`, `updated_at`) VALUES
(1, 8, 4, 5, 'B', 'sudah frs min', 'menunggu', NULL, '2026-06-07 05:09:21', '2026-06-07 05:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `nama_kampus` varchar(50) DEFAULT 'Kampus Kita',
  `nama_hima` varchar(30) DEFAULT 'HIMA Informatika',
  `logo` varchar(255) DEFAULT NULL,
  `deskripsi` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_kampus`, `nama_hima`, `logo`, `deskripsi`) VALUES
(1, 'Universitas Yudharta Pasuruan', 'Teknik Informatika', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int NOT NULL,
  `tugas_id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `catatan` text,
  `nilai` int DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `tugas_id`, `mahasiswa_id`, `file_path`, `catatan`, `nilai`, `submitted_at`) VALUES
(1, 1, 8, 'tugas_8_1_1780534823.docx', 'M SALAMIN-202469040058', 90, '2026-06-04 01:00:23'),
(2, 2, 8, 'tugas_8_2_1780600453.jpg', '', 80, '2026-06-04 19:14:13'),
(3, 3, 8, 'tugas_8_3_1780636527.mp4', '', 50, '2026-06-05 05:15:27');

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text,
  `deadline` datetime NOT NULL,
  `kelas_id` int NOT NULL,
  `dosen_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tugas`
--

INSERT INTO `tugas` (`id`, `judul`, `deskripsi`, `deadline`, `kelas_id`, `dosen_id`, `created_at`) VALUES
(1, 'membuat ppt tentang operator aritmatika', 'individu', '2026-06-06 12:00:00', 2, 5, '2026-06-04 00:56:07'),
(2, 'membuat video tutorial', 'kelompok', '2026-06-05 12:30:00', 3, 6, '2026-06-04 19:12:29'),
(3, 'membuat artikel', 'individu', '2026-06-06 00:00:00', 2, 5, '2026-06-05 03:59:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','dosen','mahasiswa') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `nim_nidn` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `semester` int DEFAULT NULL,
  `kelas` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `foto`, `nim_nidn`, `created_at`, `semester`, `kelas`) VALUES
(4, 'TEKNIK INFORMATIKA', 'admin@kampus.com', '0192023a7bbd73250516f069df18b500', 'admin', 'foto_4_1780812581.jpg', 'ADM001', '2026-06-03 02:31:15', NULL, NULL),
(5, 'Dr. Budi Santoso', 'budi@kampus.com', 'd5bbfb47ac3160c31fa8c247827115aa', 'dosen', 'foto_5_1780812772.jpeg', 'NIDN12345', '2026-06-03 02:31:15', NULL, NULL),
(6, 'Siti Aisyah', 'siti@kampus.com', 'd5bbfb47ac3160c31fa8c247827115aa', 'dosen', NULL, 'NIDN67890', '2026-06-03 02:31:15', NULL, NULL),
(8, 'M.SALMAN', 'msalamin260705@gmail.com', 'eb4ca1415881c79611313707ba48d1af', 'mahasiswa', 'foto_8_1780812555.jpeg', '202469040058', '2026-06-04 00:28:13', 4, 'B');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `informasi`
--
ALTER TABLE `informasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `mahasiswa_kelas`
--
ALTER TABLE `mahasiswa_kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `pengajuan_semester`
--
ALTER TABLE `pengajuan_semester`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tugas_id` (`tugas_id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kelas_id` (`kelas_id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `informasi`
--
ALTER TABLE `informasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mahasiswa_kelas`
--
ALTER TABLE `mahasiswa_kelas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengajuan_semester`
--
ALTER TABLE `pengajuan_semester`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `informasi`
--
ALTER TABLE `informasi`
  ADD CONSTRAINT `informasi_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `mahasiswa_kelas`
--
ALTER TABLE `mahasiswa_kelas`
  ADD CONSTRAINT `mahasiswa_kelas_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `mahasiswa_kelas_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);

--
-- Constraints for table `pengajuan_semester`
--
ALTER TABLE `pengajuan_semester`
  ADD CONSTRAINT `pengajuan_semester_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`),
  ADD CONSTRAINT `tugas_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`);
--
-- Database: `db_tugas_mahasiswa`
--
CREATE DATABASE IF NOT EXISTS `db_tugas_mahasiswa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `db_tugas_mahasiswa`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `nama`, `email`, `password`, `foto`) VALUES
(3, 'TEKNIK INFORMATIKA', 'admin@gmail.com', '$2y$10$6mURxF8EwOMGFp0nk7qXqOdoG1htYsu64SHiXjLQJ72rxXj9x1KrG', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int NOT NULL,
  `nidn` varchar(30) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `mata_kuliah` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `nidn`, `nama`, `email`, `password`, `foto`, `mata_kuliah`) VALUES
(4, '202466090751', 'M.Salamin, S.Kom', 'dosen@gmail.com', '$2y$10$LH1IAq5QAZj1fbHRI6qIV.E.kK0NyUxmQs1a2jzk/tvxoIVixXcuS', '1779685899_WhatsApp Image 2026-01-09 at 19.57.36.jpeg', 'Jaringan Komputer');

-- --------------------------------------------------------

--
-- Table structure for table `pengumpulan`
--

CREATE TABLE `pengumpulan` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `tugas_id` int DEFAULT NULL,
  `file_tugas` varchar(255) DEFAULT NULL,
  `tanggal_pengumpulan` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id` int NOT NULL,
  `dosen_id` int DEFAULT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `mata_kuliah` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `semester` int DEFAULT NULL,
  `kelas` varchar(10) DEFAULT NULL,
  `deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `nim` varchar(30) DEFAULT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `kelas` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tugas_id` (`tugas_id`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD CONSTRAINT `pengumpulan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumpulan_ibfk_2` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE;
--
-- Database: `kampus`
--
CREATE DATABASE IF NOT EXISTS `kampus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `kampus`;

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int NOT NULL,
  `nama` varchar(50) DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  `tugas` int DEFAULT NULL,
  `uts` int DEFAULT NULL,
  `uas` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
--
-- Database: `teknik_informatika`
--
CREATE DATABASE IF NOT EXISTS `teknik_informatika` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `teknik_informatika`;

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int NOT NULL,
  `nama` varchar(50) DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  `tugas` int DEFAULT NULL,
  `uts` int DEFAULT NULL,
  `uas` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nama`, `nilai`, `tugas`, `uts`, `uas`) VALUES
(1, 'Fakhris', 80, 85, 78, 90),
(2, 'Aziz', 70, 75, 80, 85),
(3, 'Salamin', 95, 90, 88, 92);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Database: `tugas_harian_mahasiswa`
--
CREATE DATABASE IF NOT EXISTS `tugas_harian_mahasiswa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `tugas_harian_mahasiswa`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'Administrator', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Database: `tugas_mahasiswa`
--
CREATE DATABASE IF NOT EXISTS `tugas_mahasiswa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `tugas_mahasiswa`;
--
-- Database: `tugas_mhs`
--
CREATE DATABASE IF NOT EXISTS `tugas_mhs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `tugas_mhs`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
