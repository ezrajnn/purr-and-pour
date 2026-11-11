CREATE DATABASE IF NOT EXISTS `purr_and_pour` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `purr_and_pour`;

DROP TABLE IF EXISTS `cafe_settings`;
CREATE TABLE `cafe_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cafe_settings` VALUES 
('support_email','purr&pour@gmail.com'),
('support_hours','Mon - Sun, 8:00 AM - 9:00 PM'),
('support_phone','0967 6767 6767');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'customer',
  `avatar` varchar(255) DEFAULT NULL,
  `notif_email` tinyint(1) DEFAULT 1,
  `notif_site` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES 
(1,'admin','admin@gmail.com','$2y$10$kJDiqwfB8za8lc9uvLWLPO3ekmwcg16JhvfvKmn.Q2NPo35wyzYpC','admin',NULL,1,1),
(2,'ski','ski@gmail.com','$2y$10$gTWLWT0dJf/fMFzgRncgge.EVNAQeLzuhI4eufdx7OUYazuf8lD5u','customer',NULL,1,1),
(3,'testuser','test@gmail.com','$2y$10$4GiqgsgDA3ZdtH0lJvDfGenazlBex0zijnracQrlIVznoa/PlnPrm','customer','/purr-and-pour/uploads/avatars/avatar_3_1793975177.jpg',1,1),
(4,'ezra','ezrajeant@gmail.com','$2y$10$aqDLrqqcDszF2pMiaN85s.34/ksLe8lDUzun6yJm/fST4Fz3HUIle','customer',NULL,1,1);

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Drinks',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) NOT NULL DEFAULT 'drink_1.png',
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` VALUES 
(1,'Latte Coffee','Hot Drinks',5.00,6,'drink_1.png','Rich espresso with smooth steamed milk and a light foam layer.','2026-09-09 13:03:13'),
(2,'Hot Americano','Hot Drinks',4.50,20,'drink_2.png','Freshly pulled espresso shots diluted with hot water for a crisp taste.','2026-09-09 13:03:13'),
(3,'Espresso Coffee','Hot Drinks',4.00,19,'drink_3.png','Bold and concentrated shot of our signature roasted coffee beans.','2026-09-09 13:03:13'),
(4,'Hot Mocha','Hot Drinks',5.50,15,'drink_4.png','Rich espresso combined with decadent chocolate and velvety milk.','2026-09-09 13:03:13'),
(5,'Vanilla Latte','Hot Drinks',6.00,20,'drink_5.png','Espresso and steamed milk infused with natural French vanilla aroma.','2026-09-09 13:03:13'),
(6,'Macchiato Latte','Hot Drinks',10.00,18,'drink_6.png','Layered espresso beverage marked with rich caramel and steamed foam.','2026-09-09 13:03:13'),
(7,'Ube Shake','Cold Drinks',6.50,15,'drink_7.png','Creamy purple yam shake topped with whipped cream and sweet drizzle.','2026-09-09 13:03:13'),
(8,'Caramel Latte','Cold Drinks',6.00,19,'drink_8.png','Iced coffee blended with rich buttery caramel syrup and fresh milk.','2026-09-09 13:03:13'),
(9,'Iced Matcha','Cold Drinks',6.00,15,'drink_9.png','Ceremonial Japanese matcha whisked with fresh milk and served over ice.','2026-09-09 13:03:13'),
(10,'Strawberry Latte','Cold Drinks',6.50,20,'drink_10.png','Real strawberry puree swirled with whole milk and light espresso.','2026-09-09 13:03:13'),
(11,'Iced Mocha','Cold Drinks',5.50,14,'drink_11.png','Chilled chocolate espresso concoction served over clear ice cubes.','2026-09-09 13:03:13'),
(12,'Milktea','Cold Drinks',5.00,15,'drink_12.png','Classic black tea brewed with creamy milk and slow-cooked tapioca pearls.','2026-09-09 13:03:13'),
(13,'Croissant','Pastries & Desserts',3.50,15,'pastry_1.png','Flaky, buttery Parisian-style pastry baked fresh every morning.','2026-09-09 13:03:13'),
(14,'Chocolate Cake','Pastries & Desserts',5.00,15,'pastry_2.png','Moist double-fudge chocolate cake slice with rich dark chocolate frosting.','2026-09-09 13:03:13'),
(15,'Chocolate Brownie','Pastries & Desserts',4.00,15,'pastry_3.png','Fudgy, chewy cocoa square packed with chocolate chunks and walnuts.','2026-09-09 13:03:13'),
(16,'Chocolate Cookie','Pastries & Desserts',3.00,15,'pastry_4.png','Golden baked cookie studded generously with melted Belgian chocolate chips.','2026-09-09 13:03:13'),
(17,'Choco Pie','Pastries & Desserts',4.50,15,'pastry_5.png','Fluffy marshmallow center sandwiched between soft cakes coated in chocolate.','2026-09-09 13:03:13'),
(18,'Tiramisu','Pastries & Desserts',5.50,15,'pastry_6.png','Classic Italian ladyfingers dipped in espresso and layered with mascarpone.','2026-09-09 13:03:13'),
(19,'Spamsilog','Rice Meals',8.50,15,'meal_1.png','Crispy fried luncheon meat slices served with garlic fried rice and sunny egg.','2026-09-09 13:03:13'),
(20,'Tocilog','Rice Meals',7.50,15,'meal_2.png','Sweet cured pork tenderloin served with sinangag garlic rice and fried egg.','2026-09-09 13:03:13'),
(21,'CornSilog','Rice Meals',7.00,15,'meal_2.png','Savory sauteed corned beef with golden garlic fried rice and sunny egg.','2026-09-09 13:03:13'),
(22,'Carbonara','Pasta & Mains',8.00,15,'meal_4.png','Creamy pasta tossed with crispy bacon bits, egg yolks, and parmesan cheese.','2026-09-09 13:03:13'),
(23,'Spaghetti','Pasta & Mains',8.00,20,'meal_5.png','Sweet and savory meat sauce spaghetti loaded with cheese and hotdog slices.','2026-09-09 13:03:13'),
(24,'Teriyaki Bowl','Rice Meals',8.50,15,'meal_6.png','Grilled glazed chicken teriyaki served over steaming jasmine rice with sesame.','2026-09-09 13:03:13');

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cart_user` (`user_id`),
  KEY `fk_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cart` VALUES (17,2,1,1);

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash on Pickup',
  `reference_number` varchar(100) DEFAULT NULL,
  `order_notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `orders` VALUES 
(1,1,'tiger','tiger@gmail.com',NULL,'Credit / Debit Card',NULL,'',10.00,'Confirmed','2026-09-09 12:38:04'),
(2,1,'tiger','tiger@gmail.com',NULL,'Cash on Pickup',NULL,'',13.50,'Confirmed','2026-09-09 12:41:16'),
(3,1,'tiger','tiger@gmail.com',NULL,'Cash on Pickup',NULL,'',27.50,'Confirmed','2026-09-09 12:43:12'),
(4,2,'ski','ski@gmail.com',NULL,'Cash on Pickup',NULL,'',5.00,'Confirmed','2026-09-09 13:28:30'),
(5,1,'admin','admin@gmail.com',NULL,'Cash on Pickup',NULL,'',4.50,'Confirmed','2026-09-09 13:30:57'),
(6,2,'ski','ski@gmail.com',NULL,'Cash on Pickup',NULL,'',5.00,'Approved','2026-09-09 13:34:12'),
(7,2,'ski','ski@gmail.com',NULL,'Cash on Pickup',NULL,'',5.00,'Approved','2026-09-09 13:46:50'),
(8,2,'ski','ski@gmail.com',NULL,'Cash on Pickup',NULL,'daghanag sugar pls',5.00,'Approved','2026-09-09 13:53:32'),
(9,3,'testuser','test@gmail.com',NULL,'Cash on Pickup',NULL,NULL,5.00,'Approved','2026-09-09 15:10:43'),
(10,3,'testuser','test@gmail.com',NULL,'Cash on Pickup',NULL,NULL,5.00,'Approved','2026-09-09 15:28:40'),
(11,3,'testuser','test@gmail.com',NULL,'Cash on Pickup',NULL,NULL,13.50,'Cancelled','2026-09-10 09:49:50'),
(12,3,'testuser','test@gmail.com',NULL,'GCash',NULL,NULL,18.50,'Approved','2026-09-10 09:50:13'),
(13,3,'testuser','test@gmail.com',NULL,'Credit / Debit Card',NULL,NULL,4.50,'Approved','2026-09-10 09:52:15'),
(14,3,'testuser','test@gmail.com','dumaguete','GCash','1002 0909 9129 1219',NULL,105.00,'Approved','2026-09-10 12:26:21'),
(15,3,'testuser','test@gmail.com','bacong','GCash','1111 1111 1111 1111',NULL,112.00,'Cancelled','2026-09-10 13:47:12'),
(16,3,'testuser','test@gmail.com','dgte','GCash','hjkkgsd',NULL,4.50,'Approved','2026-09-10 13:47:39'),
(17,3,'testuser','test@gmail.com','dumaguete','GCash','111111111111',NULL,5.00,'Cancelled','2026-09-10 14:07:17'),
(18,3,'testuser','test@gmail.com','sibulan','GCash','122222222222',NULL,156.50,'Approved','2026-09-10 14:10:08'),
(19,3,'testuser','test@gmail.com','secert','GCash','333333333333',NULL,100.00,'Approved','2026-09-15 10:09:28'),
(20,3,'testuser','test@gmail.com','dgte','GCash','000000000000',NULL,4.00,'Approved','2026-09-17 10:27:43'),
(21,4,'ezra','ezrajeant@gmail.com','','GCash','002222222222',NULL,50.00,'Approved','2026-09-17 11:26:59'),
(22,3,'testuser','test@gmail.com','','Cash on Pickup',NULL,NULL,4.00,'Approved','2026-11-06 22:29:36');

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `order_items` VALUES 
(1,1,1,'Latte Coffee',5.00,2,10.00),
(2,2,2,'Hot Americano',4.50,3,13.50),
(3,3,4,'Hot Mocha',5.50,5,27.50),
(4,4,1,'Latte Coffee',5.00,1,5.00),
(5,5,2,'Hot Americano',4.50,1,4.50),
(6,6,1,'Latte Coffee',5.00,1,5.00),
(7,7,1,'Latte Coffee',5.00,1,5.00),
(8,8,1,'Latte Coffee',5.00,1,5.00),
(9,9,1,'Latte Coffee',5.00,1,5.00),
(10,10,1,'Latte Coffee',5.00,1,5.00),
(11,11,1,'Latte Coffee',5.00,1,5.00),
(12,11,2,'Hot Americano',4.50,1,4.50),
(13,11,3,'Espresso Coffee',4.00,1,4.00),
(14,12,11,'Iced Mocha',5.50,1,5.50),
(15,12,23,'Spaghetti',8.00,1,8.00),
(16,12,1,'Latte Coffee',5.00,1,5.00),
(17,13,2,'Hot Americano',4.50,1,4.50),
(18,14,1,'Latte Coffee',5.00,21,105.00),
(19,15,23,'Spaghetti',8.00,14,112.00),
(20,16,2,'Hot Americano',4.50,1,4.50),
(21,17,1,'Latte Coffee',5.00,1,5.00),
(22,18,8,'Caramel Latte',6.00,22,132.00),
(23,18,2,'Hot Americano',4.50,1,4.50),
(24,18,1,'Latte Coffee',5.00,4,20.00),
(25,19,1,'Latte Coffee',5.00,20,100.00),
(26,20,3,'Espresso Coffee',4.00,1,4.00),
(27,21,1,'Latte Coffee',5.00,10,50.00),
(28,22,3,'Espresso Coffee',4.00,1,4.00);
