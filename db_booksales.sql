-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 20, 2026 at 11:42 AM
-- Server version: 8.0.30
-- PHP Version: 8.5.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_booksales`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ProsesCheckoutAman` (IN `p_order_number` VARCHAR(255), IN `p_customer_id` INT, IN `p_book_id` INT)   BEGIN
    DECLARE v_harga DECIMAL(10,2);
    DECLARE v_stok INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        SELECT 'GAGAL: Terjadi kesalahan sistem. Pastikan ID Pelanggan atau ID Buku valid!' AS Pesan;
    END;

    SELECT price, stock INTO v_harga, v_stok 
    FROM books 
    WHERE id = p_book_id;
    
    IF v_stok > 0 THEN
        START TRANSACTION;
        
        INSERT INTO transactions (order_number, customer_id, book_id, total_amount)
        VALUES (p_order_number, p_customer_id, p_book_id, v_harga);
        
        UPDATE books 
        SET stock = stock - 1 
        WHERE id = p_book_id;
        
        COMMIT;
        SELECT 'SUKSES: Transaksi berhasil dan stok telah dikurangi.' AS Pesan;
        
    ELSE
        SELECT 'GAGAL: Stok buku sudah habis.' AS Pesan;
    END IF;
    
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `TambahStokBuku` (IN `p_book_id` INT, IN `p_tambahan_stok` INT)   BEGIN
    UPDATE books 
    SET stock = stock + p_tambahan_stok 
    WHERE id = p_book_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bio` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `name`, `photo`, `bio`) VALUES
(1, 'Andrea Hirata', 'andrea.jpg', 'Penulis novel Laskar Pelangi.'),
(2, 'Tere Liye', 'tere.jpg', 'Penulis buku fiksi populer Indonesia.'),
(3, 'J.K. Rowling', 'jk.jpg', 'Penulis seri Harry Potter.'),
(4, 'Pramoedya Ananta Toer', 'pram.jpg', 'Sastrawan besar Indonesia.'),
(5, 'Raditya Dika', 'raditya.jpg', 'Penulis buku komedi dan sutradara.');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `genre_id` int DEFAULT NULL,
  `author_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `description`, `price`, `stock`, `cover_photo`, `genre_id`, `author_id`) VALUES
(1, 'Laskar Pelangi', 'Kisah 10 anak di Belitung.', 80000.00, 29, 'laskar.jpg', 1, 1),
(2, 'Bumi', 'Petualangan Raib di dunia paralel.', 85000.00, 15, 'bumi.jpg', 2, 2),
(3, 'Harry Potter dan Batu Bertuah', 'Awal kisah penyihir cilik.', 150000.00, 20, 'hp1.jpg', 2, 3),
(4, 'Bumi Manusia', 'Kisah cinta Minke dan Annelies.', 120000.00, 8, 'bumimanusia.jpg', 5, 4),
(5, 'Kambing Jantan', 'Catatan harian pelajar bodoh.', 60000.00, 24, 'kambing.jpg', 3, 5);

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `name`, `description`) VALUES
(1, 'Fiksi', 'Cerita rekaan yang tidak berdasarkan kenyataan sejarah.'),
(2, 'Fantasi', 'Genre yang menggunakan sihir atau elemen supranatural.'),
(3, 'Komedi', 'Genre yang bertujuan untuk memancing tawa pembaca.'),
(4, 'Biografi', 'Kisah perjalanan hidup seseorang tokoh.'),
(5, 'Sejarah', 'Cerita yang berlatar belakang peristiwa sejarah masa lampau.');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `customer_id` int DEFAULT NULL,
  `book_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `order_number`, `customer_id`, `book_id`, `total_amount`, `created_at`, `updated_at`) VALUES
(1, 'ORD-001', 1, 1, 75000.00, '2026-04-11 02:19:48', '2026-04-11 02:19:48'),
(2, 'ORD-002', 2, 3, 150000.00, '2026-04-11 02:19:48', '2026-04-11 02:19:48'),
(3, 'ORD-003', 4, 2, 85000.00, '2026-04-11 02:19:48', '2026-04-11 02:19:48'),
(4, 'ORD-004', 5, 5, 60000.00, '2026-04-11 02:19:48', '2026-04-11 02:19:48'),
(6, 'ORD-006', 1, 1, 75000.00, '2026-04-16 04:24:54', '2026-04-16 04:24:54'),
(8, 'ORD-999', 4, 5, 60000.00, '2026-04-16 04:33:23', '2026-04-16 04:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL,
  `last_access` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `last_access`) VALUES
(1, 'Budi Santoso', 'budi@email.com', 'password123', 'customer', '2026-04-11 02:19:48'),
(2, 'Siti Aminah', 'siti@email.com', 'rahasia321', 'customer', '2026-04-11 02:19:48'),
(3, 'Admin Utama', 'admin@toko.com', 'adminpass', 'admin', '2026-04-11 02:19:48'),
(4, 'Rina Melati', 'rina@email.com', 'passrina', 'customer', '2026-04-11 02:19:48'),
(5, 'Andi Wijaya', 'andi@email.com', 'andi1234', 'customer', '2026-04-11 02:19:48');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_authors_and_their_books`
-- (See below for the actual view)
--
CREATE TABLE `view_authors_and_their_books` (
`author_name` varchar(255)
,`bio` text
,`book_title` varchar(255)
,`stock` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_books_sales_history`
-- (See below for the actual view)
--
CREATE TABLE `view_books_sales_history` (
`book_title` varchar(255)
,`order_number` varchar(255)
,`price` decimal(10,2)
,`transaction_date` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_books_with_genres`
-- (See below for the actual view)
--
CREATE TABLE `view_books_with_genres` (
`book_title` varchar(255)
,`genre_name` varchar(255)
,`price` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_user_transactions`
-- (See below for the actual view)
--
CREATE TABLE `view_user_transactions` (
`order_number` varchar(255)
,`total_amount` decimal(10,2)
,`user_id` int
,`user_name` varchar(255)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `genre_id` (`genre_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Structure for view `view_authors_and_their_books`
--
DROP TABLE IF EXISTS `view_authors_and_their_books`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_authors_and_their_books`  AS SELECT `b`.`title` AS `book_title`, `b`.`stock` AS `stock`, `a`.`name` AS `author_name`, `a`.`bio` AS `bio` FROM (`authors` `a` left join `books` `b` on((`b`.`author_id` = `a`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_books_sales_history`
--
DROP TABLE IF EXISTS `view_books_sales_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_books_sales_history`  AS SELECT `t`.`order_number` AS `order_number`, `t`.`created_at` AS `transaction_date`, `b`.`title` AS `book_title`, `b`.`price` AS `price` FROM (`books` `b` left join `transactions` `t` on((`t`.`book_id` = `b`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_books_with_genres`
--
DROP TABLE IF EXISTS `view_books_with_genres`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_books_with_genres`  AS SELECT `b`.`title` AS `book_title`, `b`.`price` AS `price`, `g`.`name` AS `genre_name` FROM (`books` `b` left join `genres` `g` on((`b`.`genre_id` = `g`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_user_transactions`
--
DROP TABLE IF EXISTS `view_user_transactions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_user_transactions`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `user_name`, `t`.`order_number` AS `order_number`, `t`.`total_amount` AS `total_amount` FROM (`users` `u` left join `transactions` `t` on((`u`.`id` = `t`.`customer_id`))) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `books_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
