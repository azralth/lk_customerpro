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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Lk_CustomerPro extends Module
{
    /** @var array $LkCustomerProSettings array of config value */
    private $LkCustomerProSettings;

    /** @var array $LkCustomerProCmsPagesId array of config cms page id */
    private $LkCustomerProCmsPagesId = array();

    /** @var int $LkIdGroup id pro group */
    private $LkIdGroup;

    /** @var string Name of ModuleAdminController used for configuration */
    const MODULE_ADMIN_CONTROLLER = 'AdminDisplayLkCustomerList';

    public function __construct()
    {
        $this->name = 'lk_customerpro';
        $this->author = 'Lk Interactive';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->tab = 'front_office_features';

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Lk interactive - Lk customer Pro');
        $this->description = $this->l('Register customer in pro group if siret is set on customer account creation');
        $this->getConfigFormValues();
    }

    /**
     * Install module process
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        include dirname(__FILE__) . '/sql/install.php';
        return parent::install() &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionAuthentication') &&
            $this->registerHook('actionFrontControllerSetVariables') &&
            $this->installCmsPage() &&
            $this->installGroup() &&
            $this->installFixtures() &&
            $this->installTabs();
    }

    /**
     * Uninstall module process
     * @return bool
     */
    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';
        Configuration::deleteByName('LK_CUSTOMER_PRO_SETTINGS');
        return parent::uninstall() && $this->uninstallTab();
    }

    /**
     * Install default config Value
     * @return bool
     */
    protected function installFixtures()
    {
        if (Configuration::get('LK_CUSTOMER_PRO_SETTINGS') === false) {
            $this->LkCustomerProSettings->LkCustomerProCmsNotify_ID = $this->LkCustomerProCmsPagesId['lk-account-notify'];
            $this->LkCustomerProSettings->LkCustomerProCmsNotActivated_ID = $this->LkCustomerProCmsPagesId['lk-account-disable'];;
            $this->LkCustomerProSettings->LkCustomerProGroup_ID = $this->LkIdGroup;;
            $this->LkCustomerProSettings->LkCustomerProEnableValidAccount = false;
        } else {
            $this->LkCustomerProSettings->LkCustomerProCmsNotify_ID = Tools::getValue('LkCustomerProCmsNotify_ID');
            $this->LkCustomerProSettings->LkCustomerProCmsNotActivated_ID = Tools::getValue('LkCustomerProCmsNotActivated_ID');
            $this->LkCustomerProSettings->LkCustomerProGroup_ID = Tools::getValue('LkCustomerProGroup_ID');
            $this->LkCustomerProSettings->LkCustomerProEnableValidAccount = Tools::getValue('LkCustomerProEnableValidAccount');
        }

        if (Configuration::updateValue('LK_CUSTOMER_PRO_SETTINGS', json_encode($this->LkCustomerProSettings))) {
            return true;
        }
        return false;
    }

    /**
     * Install controller admin
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('DEFAULT');
        $tab->icon = 'settings_applications';
        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Lk Customer Pro');
        }
        try {
            $tab->save();
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return true;
    }


    /**
     * Uninstall controller admin
     * @return boolean
     */
    protected function uninstallTab()
    {
        $idTab = (int)Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);
        if ($idTab) {
            $tab = new Tab($idTab);
            try {
                $tab->delete();
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
        return true;
    }

    /**
     * Install default cms Page
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function installCmsPage()
    {
        $pages = array(
            'lk-account-notify' => array(
                'link_rewrite' => 'lk-account-notify',
                'title' => 'Registration pending validation',
                'content' => '<p>Thank you for your registration.</p><p>Your registration is subject to the control of administrator.</p><p>A confirmation email will be sent to you once your account is active.</p>'
            ),
            'lk-account-disable' => array(
                'link_rewrite' => 'lk-account-disable',
                'title' => 'Account not enable',
                'content' => '<p>Your account has not yet been activated.</p><p>We invite you to come back a little later.</p>'
            ),
        );

        foreach ($pages as $page) {
            $result = DB::getInstance()->getRow('SELECT id_cms FROM ' . _DB_PREFIX_ . 'cms_lang WHERE link_rewrite LIKE "' . $page['link_rewrite'] . '"');
            if (!$result) {
                $Cms = new CMS(null, $this->context->language->id);
                $Cms->link_rewrite = $page['link_rewrite'];
                $Cms->meta_title = $page['title'];
                $Cms->head_seo_title = $page['title'];
                $Cms->content = $page['content'];
                $Cms->id_cms_category = 1;
                $Cms->active = 1;
                $Cms->add();
                $this->LkCustomerProCmsPagesId[$page['link_rewrite']] = $Cms->id;
            } else {
                $this->LkCustomerProCmsPagesId[$page['link_rewrite']] = $result['id_cms'];
            }
        }
        return true;
    }

    /**
     * Create default group pro
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function installGroup()
    {
        $result = DB::getInstance()->getRow('SELECT id_group FROM ' . _DB_PREFIX_ . 'group_lang WHERE name LIKE "Pro"');
        if (!$result) {
            $Group = new Group(null, $this->context->language->id);
            $Group->name = 'Pro';
            $Group->price_display_method = 0;
            $Group->show_prices = 1;
            $Group->add();
            $this->LkIdGroup = $Group->id;
        } else {
            $this->LkIdGroup = $result['id_group'];
        }
        return true;
    }

    /**
     * Show admin configuration form
     * @return string
     */
    public function getContent()
    {
        $displayNotification = '';
        if (((bool)Tools::isSubmit('submitLkCustomerProConf')) == true || Tools::getIsset('id_lk_customer')) {
            $displayNotification = $this->postProcess();
        }
        return $displayNotification . $this->renderForm();
    }

    /**
     * Action when config are submit
     * @return string
     */
    protected function postProcess()
    {
        $notification = '';
        if (Tools::isSubmit('submitLkCustomerProConf')) {
            if ($this->installFixtures()) {
                $notification = $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
            } else {
                $notification = $this->displayError($this->trans('An error occur on update field value.', array(), 'Admin.Notifications.Success'));
            }
        }
        if (Tools::getIsset('id_lk_customer') && Tools::getValue('id_lk_customer') != 0) {
            if ($this->lkCustomerValidCustomer(Tools::getValue('id_lk_customer'))) {
                $notification = $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
            }
        }
        return $notification;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLkCustomerProConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     * @return array[]
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Enable validate account', array(), 'Modules.LkCustomerPro.Admin'),
                        'name' => 'LkCustomerProEnableValidAccount',
                        'is_bool' => true,
                        'desc' => $this->trans('Say true if you want to activate manually new account that are placed in pro group', array(), 'Modules.LkCustomerPro.Admin'),
                        'values' => array(
                            array(
                                'id' => 'enable_validate_account',
                                'value' => true,
                                'label' => $this->trans('Enable', array(), 'Modules.LkCustomerPro.Admin')
                            ),
                            array(
                                'id' => 'disable_validate_account',
                                'value' => false,
                                'label' => $this->trans('Disable', array(), 'Modules.LkCustomerPro.Admin')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Id CMS Page Validate Account waiting', array(), 'Modules.LkCustomerPro.Admin'),
                        'name' => 'LkCustomerProCmsNotify_ID',
                        'desc' => $this->trans('ID CMS page where validate account waiting text is set', array(), 'Modules.LkCustomerPro.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Id CMS Page Account not enable', array(), 'Modules.LkCustomerPro.Admin'),
                        'name' => 'LkCustomerProCmsNotActivated_ID',
                        'desc' => $this->trans('ID CMS page where the user is redirect if he try to login and his account are not longer enable', array(), 'Modules.LkCustomerPro.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Id Group Pro', array(), 'Modules.LkCustomerPro.Admin'),
                        'name' => 'LkCustomerProGroup_ID',
                        'desc' => $this->trans('ID Group pro', array(), 'Modules.LkCustomerPro.Admin'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Return config module value
     * @return array
     */
    protected function getConfigFormValues()
    {
        $fields = array();
        $this->LkCustomerProSettings = json_decode(Configuration::get('LK_CUSTOMER_PRO_SETTINGS'));
        if ($this->LkCustomerProSettings != false) {
            $fields['LkCustomerProCmsNotify_ID'] = $this->LkCustomerProSettings->LkCustomerProCmsNotify_ID;
            $fields['LkCustomerProCmsNotActivated_ID'] = $this->LkCustomerProSettings->LkCustomerProCmsNotActivated_ID;
            $fields['LkCustomerProEnableValidAccount'] = $this->LkCustomerProSettings->LkCustomerProEnableValidAccount;
            $fields['LkCustomerProGroup_ID'] = $this->LkCustomerProSettings->LkCustomerProGroup_ID;
        }
        return $fields;
    }

    /**
     * Add customer to pro group if siret is not empty
     * @param type $params
     */
    public function hookActionCustomerAccountAdd($params)
    {
        $customer = $params['newCustomer'];
        $siret = Tools::getValue('siret');
        $groupsToAdd = array();

        //Check if siret is valid
        if ($siret && Validate::isSiret($siret)) {
            array_push($groupsToAdd, 3, $this->LkCustomerProSettings->LkCustomerProGroup_ID);
            $customer->cleanGroups();
            $customer->addGroups($groupsToAdd);
            $customer->id_default_group = $this->LkCustomerProSettings->LkCustomerProGroup_ID;
            $customer->save();

            // Check if manually validate account is enable
            if ($this->LkCustomerProSettings->LkCustomerProEnableValidAccount) {
                // Send mail
                $CustId = $params['newCustomer']->id;
                $CustEmail = $params['newCustomer']->email;
                $email = (string)Configuration::get('PS_SHOP_EMAIL');
                $id_lang = $this->context->language->id;

                // Insert in db
                if ($this->addNewInvitation($CustId, $CustEmail)) {
                    $this->sendAccountCreateMail($params, $email);
                    $this->context->controller->success[] = $this->l('Registration successfully. Your account need to be activated. You will receive a confirmation soon');
                    $params['newCustomer']->logout();
                    /* On le redirige sur la page CMS de notification */
                    Tools::Redirect($this->context->link->getCMSLink(
                        (int)$this->LkCustomerProSettings->LkCustomerProCmsNotify_ID,
                        null,
                        null,
                        (int)$id_lang
                    )
                    );
                }
            }
        }
    }

    /**
     * Send email notification to admin CE and new customer
     *
     * @param $params
     * @param $email
     */
    public function sendAccountCreateMail($params, $email)
    {
        /* Get current id lang*/
        $id_lang = (int)$this->context->language->id;

        /* We send an email to a customer */
        Mail::Send(
            (int)$id_lang,
            'pendingaccount',
            $this->l('Welcome!'),
            array(
                '{firstname}' => (string)$params['newCustomer']->firstname,
                '{lastname}' => (string)$params['newCustomer']->lastname,
                '{email}' => (string)$params['newCustomer']->email,
                '{passwd}' => ''),
            (string)$params['newCustomer']->email,
            (string)$params['newCustomer']->firstname . ' ' . (string)$params['newCustomer']->lastname,
            (string)Configuration::get('PS_SHOP_EMAIL'),
            (string)Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/mails/'
        );

        /* We send email ton admin shop */
        Mail::Send(
            (int)Configuration::get('PS_LANG_DEFAULT'),
            'newaccount',
            $this->l('A new account need to be activated !'),
            array(
                '{firstname}' => (string)$params['newCustomer']->firstname,
                '{lastname}' => (string)$params['newCustomer']->lastname,
                '{email}' => (string)$params['newCustomer']->email,
                '{passwd}' => ''),
            (string)$email,
            (string)Configuration::get('PS_SHOP_NAME'),
            (string)Configuration::get('PS_SHOP_EMAIL'),
            (string)Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/mails/'
        );
    }

    /**
     * Add new customer in lk customer active table
     * @param $idcustomer
     * @param $email
     * @param int $active
     * @return bool
     */
    public function addNewInvitation($idcustomer, $email, $active = 0)
    {
        // Test first if user is already in db
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'lk_customer` WHERE `email` = "' . (string)$email . '"';
        if (!Db::getInstance()->getRow($sql)) {
            $date = date('Y-m-d H:i:s');
            $sql = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'lk_customer` (`id_customer`, `email`, `date_add`, `active`)
                VALUES(' . (int)$idcustomer . ', "' . $email . '", "' . $date . '", ' . (int)$active . ')');
            return $sql;
        } else {
            $result = Db::getInstance()->update('lk_customer', array(
                'id_customer' => $idcustomer,
                'active' => 0,
                'date_upd' => date('Y-m-d H:i:s'),
            ), 'email = "' . $email . '"', 1, true);
            return $result;
        }
    }

    /**
     *
     * Check if user is enable to login
     *
     * @return type
     */
    public function hookActionAuthentication()
    {
        if ($this->LkCustomerProSettings->LkCustomerProEnableValidAccount) {
            if ((int)$this->context->cookie->id_customer != '') {
                $sql = 'SELECT a.active AS `active` FROM `' . _DB_PREFIX_ . 'lk_customer` a WHERE a.`id_customer` = '
                    . (int)$this->context->cookie->id_customer;
                $result = Db::getInstance()->getValue($sql);
                if (!(int)$result) {
                    /* Logout customer */
                    $this->context->cookie->logout();
                    /* Redirect to notification page */
                    Tools::Redirect($this->context->link->getCMSLink(
                            (int)$this->LkCustomerProSettings->LkCustomerProCmsNotActivated_ID,
                            null,
                            null,
                            (int)$this->context->language->id
                        )
                    );
                }
            }
        }
    }

    /**
     * Add new global smarty variable isprocustomer.
     * @return bool[]
     */
    public function hookActionFrontControllerSetVariables()
    {
        $is_pro = $this->context->customer->id_default_group == $this->LkCustomerProSettings->LkCustomerProGroup_ID;
        $this->context->smarty->assign(array('isprocustomer' => $is_pro));
    }

    /**
     * Enable / disable customer
     * @param $idcustomer
     * @return bool
     */
    public function lkCustomerValidCustomer($idcustomer)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . "lk_customer` SET `active` = NOT `active`, `date_upd` = '" . date('Y-m-d H:i:s')
            . "' WHERE `id_customer` = " . (int)$idcustomer;
        Db::getInstance()->Execute($sql);
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'lk_customer` WHERE `id_customer` = ' . (int)$idcustomer;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return false;
        }
        if ($result['active'] == 1) {
            $customer = $customer = new Customer($result['id_customer']);
            /* On envoi le mail d'activation de compte au client */
            return Mail::Send(
                (int)Configuration::get('PS_LANG_DEFAULT'),
                'validatedaccount',
                $this->l('Your account has been activated !'),
                array(
                    '{firstname}' => (string)$customer->firstname,
                    '{lastname}' => (string)$customer->lastname,
                    '{email}' => (string)$customer->email),
                (string)$customer->email,
                (string)$customer->firstname . ' ' . (string)$customer->lastname,
                (string)Configuration::get('PS_SHOP_EMAIL'),
                (string)Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                dirname(__FILE__) . '/mails/'
            );
        }
    }
}
