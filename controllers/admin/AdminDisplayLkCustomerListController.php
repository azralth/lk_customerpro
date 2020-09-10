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
require_once _PS_MODULE_DIR_ . '/lk_customerpro/classes/LkCustomerPro.php';
class AdminDisplayLkCustomerListController extends ModuleAdminController
{
    protected $_pagination = array(20, 50, 100, 300, 1000);
    protected $_default_pagination = 20;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = LkCustomerPro::class;;
        $this->identifier = LkCustomerPro::$definition['primary'];;
        $this->module = 'lk_customerpro';
        $this->table = LkCustomerPro::$definition['table'];;

        parent::__construct();

        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->l('Id'),
                'width' => 40,
                'type' => 'text',
            ),
            'email' => array(
                'title' => $this->l('Customer Email'),
                'width' => 80,
                'type' => 'text',
            ),
            'date_add' => array(
                'title' => $this->l('Sign in Date'),
                'width' => 80,
                'type' => 'text',
            ),
            'date_upd' => array(
                'title' => $this->l('Confirmation Date'),
                'width' => 80,
                'type' => 'text',
            ),
            'active' => array(
                'title' => $this->l('State'),
                'width' => 40,
                'type' => 'bool',
                'validation' => 'isBool',
                'cast' => 'intval',
                'active' => 'status',
            ),
        );
    }
}
