/*
Navicat MySQL Data Transfer
Source Host     : localhost:3306
Source Database : opengateway
Target Host     : localhost:3306
Target Database : opengateway
Date: 2009-11-04 21:51:33
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for clients
-- ----------------------------
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL AUTO_INCREMENT,
  `api_id` varchar(100) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  PRIMARY KEY (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of clients
-- ----------------------------
