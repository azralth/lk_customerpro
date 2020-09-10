<?php
/**
 *  Copyright (C) Lk Interactive - All Rights Reserved.
 *
 *  This is proprietary software therefore it cannot be distributed or reselled.
 *  Unauthorized copying of this file, via any medium is strictly prohibited.
 *  Proprietary and confidential.
 *
 * @author    Lk Interactive <contact@lk-interactive.fr>
 * @copyright 2007.
 * @license   Commercial license
 */

$sql = array();
// Install table for customer validation
$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "lk_customer` (
          `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_customer` int(10) unsigned NOT NULL DEFAULT '0',
          `email`       VARCHAR(128) NOT NULL,
          `date_add`    datetime NOT NULL,
          `date_upd`    datetime NOT NULL,
          `active`      tinyint(1) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=UTF8";

foreach ($sql as $query) {
	if (Db::getInstance()->execute($query) == false) {
		return $sql;
	}
}
