<?php


if (!defined('_PS_VERSION_')) {
    exit;
}

class MyModule extends Module
{
    public function __construct()
    {
        $this->name = 'mymodule';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Firstname Lastname';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Test module for Altopromo');
        $this->description = $this->l('Setting min and max prices of products');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('displayHeader') ||
            !$this->registerHook('displayFooter') ||
            !Configuration::updateValue('MYMODULE_NAME', 'my friend')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Storing a serialized array.
        Configuration::updateValue('MYMODULE_SETTINGS', serialize(array(true, true, false)));

        if (!parent::uninstall() ||
            !Configuration::deleteByName('MYMODULE_NAME')
        ) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $minValue = (int)(Tools::getValue('MIN_VALUE'));
            $maxValue = (int)(Tools::getValue('MAX_VALUE'));

            if (
                $minValue<0 || $maxValue<0 ||
                !is_numeric($minValue) || !is_numeric($maxValue)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } elseif($minValue > $maxValue) {
                $output .= $this->displayError($this->l('Min value cannot be larger than Max value'));
            } else {
                Configuration::updateValue('MIN_VALUE', (int) $minValue);
                Configuration::updateValue('MAX_VALUE', (int) $maxValue);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Min price'),
                    'name' => 'MIN_VALUE',
                    'size' => 5,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Max price'),
                    'name' => 'MAX_VALUE',
                    'size' => 5,
                    'required' => true
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm(array($fieldsForm));
    }

    public function getConfigFieldsValues()
    {
        return [
            'MIN_VALUE' => Tools::getValue('MIN_VALUE', Configuration::get('MIN_VALUE')),
            'MAX_VALUE' => Tools::getValue('MAX_VALUE', Configuration::get('MAX_VALUE')),
        ];
    }

    public function hookDisplayHeader()
    {
        $minValue = Configuration::get('MIN_VALUE');
        $maxValue = Configuration::get('MAX_VALUE');
        $query = 'SELECT COUNT(*) FROM `ps_product` WHERE `price` BETWEEN ' . $minValue . ' AND ' . $maxValue;

        $productsCount = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        $this->context->smarty->assign([
            'min_value' => $minValue,
            'max_value' => $maxValue,
            'product_count' => $productsCount[0]['COUNT(*)']
        ]);


        return $this->display(__FILE__, 'module.tpl');
        //return print_r($productsCount[0]['COUNT(*)']);
    }

}

