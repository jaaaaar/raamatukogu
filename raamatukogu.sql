-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Loomise aeg: Juuni 09, 2025 kell 01:44 PL
-- Serveri versioon: 10.4.32-MariaDB
-- PHP versioon: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Andmebaas: `raamatukogu`
--

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `broneering`
--

CREATE TABLE `broneering` (
  `BroneeringID` int(11) NOT NULL,
  `KasutajaID` int(11) NOT NULL,
  `EksemplarID` int(11) NOT NULL,
  `BroneeringAlgus` datetime NOT NULL,
  `BroneeringLopp` datetime NOT NULL,
  `Staatus` enum('aktiivne','tuhistatud','lopp') DEFAULT 'aktiivne'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Andmete tõmmistamine tabelile `broneering`
--

INSERT INTO `broneering` (`BroneeringID`, `KasutajaID`, `EksemplarID`, `BroneeringAlgus`, `BroneeringLopp`, `Staatus`) VALUES
(1, 4, 4, '2025-06-09 13:09:57', '2025-06-11 13:09:57', 'aktiivne'),
(2, 5, 2, '2025-06-09 13:10:44', '2025-06-11 13:10:44', 'aktiivne');

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `eksemplar`
--

CREATE TABLE `eksemplar` (
  `EksemplarID` int(11) NOT NULL,
  `RaamatID` int(11) NOT NULL,
  `Staatus` enum('vaba','laenutatud','broneeritud') DEFAULT 'vaba'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Andmete tõmmistamine tabelile `eksemplar`
--

INSERT INTO `eksemplar` (`EksemplarID`, `RaamatID`, `Staatus`) VALUES
(1, 1, 'laenutatud'),
(2, 1, 'broneeritud'),
(3, 1, 'laenutatud'),
(4, 2, 'broneeritud'),
(5, 2, 'laenutatud');

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `kasutaja`
--

CREATE TABLE `kasutaja` (
  `KasutajaID` int(11) NOT NULL,
  `Eesnimi` varchar(50) NOT NULL,
  `Perekonnanimi` varchar(50) NOT NULL,
  `Isikukood` char(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Roll` enum('tootaja','kylastaja') NOT NULL,
  `Parool` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Andmete tõmmistamine tabelile `kasutaja`
--

INSERT INTO `kasutaja` (`KasutajaID`, `Eesnimi`, `Perekonnanimi`, `Isikukood`, `Email`, `Roll`, `Parool`) VALUES
(1, 'Mari', 'Tamm', '38001019999', 'mari.tamm@example.com', 'tootaja', 'hash_parool1'),
(2, 'Jaan', 'Kask', '39002020000', 'jaan.kask@example.com', 'kylastaja', 'hash_parool2'),
(3, 'Liis', 'Saar', '40003030000', 'liis.saar@example.com', 'kylastaja', 'hash_parool3'),
(4, 'Roomet', 'Altmäe', '50604170228', 'roomet2006@gmail.com', 'kylastaja', '$2y$10$F0Yg5ODGoKt2BH7GKY/18.VzXsD0EYvIKIMAS2NwDW0d4KmpRTjVW'),
(5, 'Andrus', 'Olen', '12345678911', 'lahe@andrus.ee', 'kylastaja', '$2y$10$A3HwwbZ7EVK5VA8O8nYkpe6XgreyM7dC0LR8.bqj4ydTi579qtt7S'),
(6, 'AAAAAA', 'bbbbbb', '12345678811', 'lahe222@andrus.ee', 'kylastaja', '$2y$10$3et4c2XJvUh6y.p6k6EUYuE8VcOb1sknWGf6YCeAHYKlqu7oAwgqq');

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `laenutus`
--

CREATE TABLE `laenutus` (
  `LaenutusID` int(11) NOT NULL,
  `KasutajaID` int(11) NOT NULL,
  `EksemplarID` int(11) NOT NULL,
  `LaenutusAlgus` datetime NOT NULL,
  `LaenutusLopp` datetime NOT NULL,
  `Tagastatud` tinyint(1) DEFAULT 0,
  `TagastusKuupaev` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Andmete tõmmistamine tabelile `laenutus`
--

INSERT INTO `laenutus` (`LaenutusID`, `KasutajaID`, `EksemplarID`, `LaenutusAlgus`, `LaenutusLopp`, `Tagastatud`, `TagastusKuupaev`) VALUES
(1, 2, 1, '2025-06-09 13:08:27', '2025-06-23 13:08:27', 0, NULL),
(2, 5, 3, '2025-06-09 13:14:43', '2025-06-23 13:14:43', 0, NULL),
(3, 6, 5, '2025-06-09 13:14:57', '2025-06-23 13:14:57', 0, NULL);

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `raamat`
--

CREATE TABLE `raamat` (
  `RaamatID` int(11) NOT NULL,
  `Pealkiri` varchar(255) NOT NULL,
  `Autor` varchar(255) NOT NULL,
  `ISBN` varchar(20) NOT NULL,
  `EksemplarideArv` int(11) NOT NULL CHECK (`EksemplarideArv` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Andmete tõmmistamine tabelile `raamat`
--

INSERT INTO `raamat` (`RaamatID`, `Pealkiri`, `Autor`, `ISBN`, `EksemplarideArv`) VALUES
(1, 'Sõda ja rahu', 'Lev Tolstoi', '978-5-17-112030-5', 2),
(2, 'Kalevipoeg', 'Friedrich Reinhold Kreutzwald', '978-9985-3-2014-0', 1);

--
-- Indeksid tõmmistatud tabelitele
--

--
-- Indeksid tabelile `broneering`
--
ALTER TABLE `broneering`
  ADD PRIMARY KEY (`BroneeringID`),
  ADD KEY `KasutajaID` (`KasutajaID`),
  ADD KEY `EksemplarID` (`EksemplarID`);

--
-- Indeksid tabelile `eksemplar`
--
ALTER TABLE `eksemplar`
  ADD PRIMARY KEY (`EksemplarID`),
  ADD KEY `RaamatID` (`RaamatID`);

--
-- Indeksid tabelile `kasutaja`
--
ALTER TABLE `kasutaja`
  ADD PRIMARY KEY (`KasutajaID`),
  ADD UNIQUE KEY `Isikukood` (`Isikukood`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indeksid tabelile `laenutus`
--
ALTER TABLE `laenutus`
  ADD PRIMARY KEY (`LaenutusID`),
  ADD KEY `KasutajaID` (`KasutajaID`),
  ADD KEY `EksemplarID` (`EksemplarID`);

--
-- Indeksid tabelile `raamat`
--
ALTER TABLE `raamat`
  ADD PRIMARY KEY (`RaamatID`),
  ADD UNIQUE KEY `ISBN` (`ISBN`);

--
-- AUTO_INCREMENT tõmmistatud tabelitele
--

--
-- AUTO_INCREMENT tabelile `broneering`
--
ALTER TABLE `broneering`
  MODIFY `BroneeringID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT tabelile `eksemplar`
--
ALTER TABLE `eksemplar`
  MODIFY `EksemplarID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT tabelile `kasutaja`
--
ALTER TABLE `kasutaja`
  MODIFY `KasutajaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT tabelile `laenutus`
--
ALTER TABLE `laenutus`
  MODIFY `LaenutusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT tabelile `raamat`
--
ALTER TABLE `raamat`
  MODIFY `RaamatID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tõmmistatud tabelite piirangud
--

--
-- Piirangud tabelile `broneering`
--
ALTER TABLE `broneering`
  ADD CONSTRAINT `broneering_ibfk_1` FOREIGN KEY (`KasutajaID`) REFERENCES `kasutaja` (`KasutajaID`),
  ADD CONSTRAINT `broneering_ibfk_2` FOREIGN KEY (`EksemplarID`) REFERENCES `eksemplar` (`EksemplarID`);

--
-- Piirangud tabelile `eksemplar`
--
ALTER TABLE `eksemplar`
  ADD CONSTRAINT `eksemplar_ibfk_1` FOREIGN KEY (`RaamatID`) REFERENCES `raamat` (`RaamatID`);

--
-- Piirangud tabelile `laenutus`
--
ALTER TABLE `laenutus`
  ADD CONSTRAINT `laenutus_ibfk_1` FOREIGN KEY (`KasutajaID`) REFERENCES `kasutaja` (`KasutajaID`),
  ADD CONSTRAINT `laenutus_ibfk_2` FOREIGN KEY (`EksemplarID`) REFERENCES `eksemplar` (`EksemplarID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
