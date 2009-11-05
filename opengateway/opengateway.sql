/*
Navicat MySQL Data Transfer
Source Host     : localhost:3306
Source Database : opengateway
Target Host     : localhost:3306
Target Database : opengateway
Date: 2009-11-04 22:36:37
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of clients
-- ----------------------------
INSERT INTO `clients` VALUES ('1', '123456789123456789', 'dsdf324854s2d1f8s5g43sd21f');
