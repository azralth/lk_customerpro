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
// Delete customer validation table
$sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "lk_customer`";
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
