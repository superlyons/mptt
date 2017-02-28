/*
Navicat MySQL Data Transfer

Source Server         : localhost_3306
Source Server Version : 50714
Source Host           : localhost:3306
Source Database       : abc

Target Server Type    : MYSQL
Target Server Version : 50714
File Encoding         : 65001

Date: 2017-02-15 23:15:55
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `mptt`
-- ----------------------------
DROP TABLE IF EXISTS `mptt`;
CREATE TABLE `mptt` (
  `id` bigint(20) unsigned NOT NULL,
  `root` bigint(20) unsigned DEFAULT NULL,
  `lft` int(11) unsigned NOT NULL,
  `rgt` int(11) unsigned NOT NULL,
  `level` smallint(6) unsigned NOT NULL,
  `parent` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `data` text,
  `type` varchar(10) DEFAULT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `seo_description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `optimistic_lock` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `root` (`root`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `level` (`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mptt
-- ----------------------------
INSERT INTO `mptt` VALUES ('4641937028538177630', '4641937028538177630', '1', '44', '1', '0', '程序语言', '', null, '', '', '', '', '', null, null, '1486745872', '1486815420', '1');
INSERT INTO `mptt` VALUES ('4641937160163824707', '4641937028538177630', '2', '25', '2', '4641937028538177630', 'PHP', '', '{\r\n  \"name\":\"lyons\",\r\n  \"age\":28,\r\n  \"obj\":{\r\n      \"pro1\":\"value\"\r\n  }\r\n}', '', '', '', '', '', null, null, '1486745903', '1487171592', '3');
INSERT INTO `mptt` VALUES ('4641937284113895469', '4641937028538177630', '3', '10', '3', '4641937160163824707', 'YiiFramework', '', null, '', 'Yii Framework Summary', 'SEO Title', 'SEO keywords', '', null, null, '1486745933', '1486745933', '0');
INSERT INTO `mptt` VALUES ('4641937378166970569', '4641937028538177630', '4', '5', '4', '4641937284113895469', 'YiiSite-1', '/language/php/yii?id=4641937378166970569&param=value#authorID', null, '', '', '', '', '', null, null, '1486745955', '1486811856', '3');
INSERT INTO `mptt` VALUES ('4641937474191367422', '4641937028538177630', '11', '18', '3', '4641937160163824707', 'CodeIgniter', '/language/php/ci', null, '', 'CodeIgniter Summary', '', '', '', null, null, '1486745978', '1486795367', '3');
INSERT INTO `mptt` VALUES ('4641937556982734480', '4641937028538177630', '19', '24', '3', '4641937160163824707', 'ThinkPHP', '', null, '', '', '', '', '', null, null, '1486745998', '1486745998', '0');
INSERT INTO `mptt` VALUES ('4641937648716354695', '4641937028538177630', '6', '7', '4', '4641937284113895469', 'YiiSite-2', '', null, '', '', '', '', '', null, null, '1486746020', '1486746020', '0');
INSERT INTO `mptt` VALUES ('4641937707562441019', '4641937028538177630', '8', '9', '4', '4641937284113895469', 'YiiSite-3', '', null, '', '', '', '', '', null, null, '1486746034', '1486746034', '0');
INSERT INTO `mptt` VALUES ('4641937818476617455', '4641937028538177630', '12', '13', '4', '4641937474191367422', 'CISite-1', '', null, '', '', '', '', '', null, null, '1486746060', '1486746060', '0');
INSERT INTO `mptt` VALUES ('4641937894057972771', '4641937028538177630', '14', '15', '4', '4641937474191367422', 'CISite-2', '', null, '', '', '', '', '', null, null, '1486746078', '1486746078', '0');
INSERT INTO `mptt` VALUES ('4641937943391377873', '4641937028538177630', '16', '17', '4', '4641937474191367422', 'CISite-3', '', null, '', '', '', '', '', null, null, '1486746090', '1486746090', '0');
INSERT INTO `mptt` VALUES ('4641938042649582613', '4641937028538177630', '20', '21', '4', '4641937556982734480', 'ThinkPHPSite-1', '', null, '', '', '', '', '', null, null, '1486746114', '1486746114', '0');
INSERT INTO `mptt` VALUES ('4641938087906123536', '4641937028538177630', '22', '23', '4', '4641937556982734480', 'ThinkPHPSite-2', '', null, '', '', '', '', '', null, null, '1486746125', '1486746125', '0');
INSERT INTO `mptt` VALUES ('4641938165081316771', '4641937028538177630', '26', '35', '2', '4641937028538177630', 'Java', '', null, '', '', '', '', '', null, null, '1486746143', '1486746143', '0');
INSERT INTO `mptt` VALUES ('4641938273780896019', '4641937028538177630', '36', '43', '2', '4641937028538177630', 'JavaScript', '', null, '', '', '', '', '', null, null, '1486746169', '1486746169', '0');
INSERT INTO `mptt` VALUES ('4641938359818656292', '4641937028538177630', '37', '38', '3', '4641938273780896019', 'JQuery', '/any/node', null, '', '', '', '', '', null, null, '1486746189', '1486795521', '1');
INSERT INTO `mptt` VALUES ('4641938424960390253', '4641937028538177630', '41', '42', '3', '4641938273780896019', 'Bootstrap', '', null, '', '', '', '', '', null, null, '1486746205', '1486778819', '3');
INSERT INTO `mptt` VALUES ('4641938488902554043', '4641937028538177630', '39', '40', '3', '4641938273780896019', 'Node.js', '', null, '', '', '', '', '', null, null, '1486746220', '1486746220', '0');
INSERT INTO `mptt` VALUES ('4641938542627393917', '4641937028538177630', '27', '30', '3', '4641938165081316771', 'Spring', '/language/java/spring', null, '', '', '', '', '', null, null, '1486746233', '1486795255', '1');
INSERT INTO `mptt` VALUES ('4641938622461779532', '4641937028538177630', '28', '29', '4', '4641938542627393917', 'SpringSite-1', '/language/java/spring/springsite-1', null, '', '', '', '', '', null, null, '1486746252', '1486795286', '1');
INSERT INTO `mptt` VALUES ('4641938695258118383', '4641937028538177630', '31', '32', '3', '4641938165081316771', 'Hibernate ', '/language/java/hibernate', null, '', '', '', '', '', null, null, '1486746269', '1486795322', '1');
INSERT INTO `mptt` VALUES ('4641938769753151329', '4641937028538177630', '33', '34', '3', '4641938165081316771', 'Shiro', 'http://shiro.com', null, '', '', '', '', '', null, null, '1486746287', '1486815746', '2');
INSERT INTO `mptt` VALUES ('4641938999529709462', '4641938999529709462', '1', '18', '1', '0', 'Software', '', null, '', '', '', '', '', null, null, '1486746342', '1486746342', '0');
INSERT INTO `mptt` VALUES ('4641939060015764639', '4641938999529709462', '2', '11', '2', '4641938999529709462', 'Database ', '', null, '', '', '', '', '', null, null, '1486746356', '1486746653', '2');
INSERT INTO `mptt` VALUES ('4641939096053223600', '4641938999529709462', '12', '17', '2', '4641938999529709462', 'Language ', '', null, '', '', '', '', '', null, null, '1486746365', '1486795575', '2');
INSERT INTO `mptt` VALUES ('4641940720553302501', '4641938999529709462', '3', '6', '3', '4641939060015764639', 'MySQL', '/database/mysql', null, '', '', '', '', '', null, null, '1486746752', '1486795436', '2');
INSERT INTO `mptt` VALUES ('4641940804997224696', '4641938999529709462', '13', '14', '3', '4641939096053223600', 'PHPLanguage', '/any/node', null, '', '', '', '', '', null, null, '1486746772', '1486746806', '1');
INSERT INTO `mptt` VALUES ('4641941058010223764', '4641938999529709462', '4', '5', '4', '4641940720553302501', 'MYSQL 5.0', '', null, '', '', '', '', '', null, null, '1486746833', '1486746833', '0');
INSERT INTO `mptt` VALUES ('4641941136112361409', '4641938999529709462', '7', '10', '3', '4641939060015764639', 'ORACLE', '', null, '', '', '', '', '', null, null, '1486746851', '1486746851', '0');
INSERT INTO `mptt` VALUES ('4641941242563795940', '4641938999529709462', '8', '9', '4', '4641941136112361409', 'Oracle 9i', '', null, '', '', '', '', '', null, null, '1486746877', '1486746877', '0');
INSERT INTO `mptt` VALUES ('4641941330602235668', '4641938999529709462', '15', '16', '3', '4641939096053223600', 'JavaLanguage', '', null, '', '', '', '', '', null, null, '1486746898', '1486746898', '0');
