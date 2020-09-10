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

class LkCustomerPro extends ObjectModel
{
    /** @var int $id */
    public $id;

    /** @var int $id_customer */
    public $id_customer;

    /** @var string $email */
    public $email;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object creation date */
    public $date_upd;

    /** @var bool Object active */
    public $active;

    public static $definition = [
        'table' => 'lk_customer',
        'primary' => 'id',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255, 'required' => true],
            'date_add' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ],
    ];
}
