<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class BlockNewProducts extends Module
{
	protected static $cache_new_products;

	public function __construct()
	{
		$this->name = 'blocknewproducts';
		$this->tab = 'front_office_features';
		$this->version = '1.10.0';
		$this->author = 'PrestaShop';
		$this->need_instance = 0;

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('New products block');
		$this->description = $this->l('Displays a block featuring your store\'s newest products.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		$success = (parent::install()
			&& $this->registerHook('header')
			&& $this->registerHook('leftColumn')
			&& $this->registerHook('addproduct')
			&& $this->registerHook('updateproduct')
			&& $this->registerHook('deleteproduct')
			&& Configuration::updateValue('NEW_PRODUCTS_NBR', 5)
			&& $this->registerHook('displayHomeTab')
			&& $this->registerHook('displayHomeTabContent')
		);

		$this->_clearCache('*');

		return $success;
	}

	public function uninstall()
	{
		$this->_clearCache('*');

		return parent::uninstall();
	}

	public function getContent()
	{
		$output = '';
		if (Tools::isSubmit('submitBlockNewProducts'))
		{
			if (!($productNbr = Tools::getValue('NEW_PRODUCTS_NBR')) || empty($productNbr))
				$output .= $this->displayError($this->l('Please complete the "products to display" field.'));
			elseif ((int)($productNbr) == 0)
				$output .= $this->displayError($this->l('Invalid number.'));
			else
			{
				Configuration::updateValue('PS_NB_DAYS_NEW_PRODUCT', (int)(Tools::getValue('PS_NB_DAYS_NEW_PRODUCT')));
				Configuration::updateValue('PS_BLOCK_NEWPRODUCTS_DISPLAY', (int)(Tools::getValue('PS_BLOCK_NEWPRODUCTS_DISPLAY')));
				Configuration::updateValue('NEW_PRODUCTS_NBR', (int)($productNbr));
				$this->_clearCache('*');
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}
		return $output.$this->renderForm();
	}

	protected function getNewProducts()
	{
		if (!Configuration::get('NEW_PRODUCTS_NBR'))
			return;
		$newProducts = false;
		if (Configuration::get('PS_NB_DAYS_NEW_PRODUCT'))
			$newProducts = getNewProducts((int) $this->context->language->id, 0, (int)Configuration::get('NEW_PRODUCTS_NBR'));

		if (!$newProducts && Configuration::get('PS_BLOCK_NEWPRODUCTS_DISPLAY'))
			return;
		return $newProducts;
	}

	public function hookRightColumn($params)
	{
		if (!$this->isCached('blocknewproducts.tpl', $this->getCacheId()))
		{
			if (!isset(BlockNewProducts::$cache_new_products[$params["cart"]->id_lang]))
				BlockNewProducts::$cache_new_products[$params["cart"]->id_lang] = $this->getNewProducts();

			$this->smarty->assign(array(
				'new_products' => BlockNewProducts::$cache_new_products[$params["cart"]->id_lang],
				'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
				'homeSize' => Image::getSize(ImageType::getFormatedName('home'))
			));
		}

		if (BlockNewProducts::$cache_new_products[$params["cart"]->id_lang] === false)
			return false;

		return $this->display(__FILE__, 'blocknewproducts.tpl', $this->getCacheId());
	}

	protected function getCacheId($name = null)
	{
		if ($name === null)
			$name = 'blocknewproducts';
		return parent::getCacheId($name.'|'.date('Ymd'));
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	public function hookdisplayHomeTab($params)
	{
		if (!$this->isCached('tab.tpl', $this->getCacheId('blocknewproducts-tab')))
			BlockNewProducts::$cache_new_products[$params["cart"]->id_lang] = $this->getNewProducts();

		if (BlockNewProducts::$cache_new_products[$params["cart"]->id_lang] === false)
			return false;

		return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blocknewproducts-tab'));
	}

	public function hookdisplayHomeTabContent($params)
	{
		if (!$this->isCached('blocknewproducts_home.tpl', $this->getCacheId('blocknewproducts-home')))
		{
			$this->smarty->assign(array(
				'new_products' => BlockNewProducts::$cache_new_products[$params["cart"]->id_lang],
				'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
				'homeSize' => Image::getSize(ImageType::getFormatedName('home'))
			));
		}

		if (BlockNewProducts::$cache_new_products[$params["cart"]->id_lang] === false)
			return false;

		return $this->display(__FILE__, 'blocknewproducts_home.tpl', $this->getCacheId('blocknewproducts-home'));
	}

	public function hookHeader($params)
	{
		if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index')
			$this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');

		$this->context->controller->addCSS($this->_path.'blocknewproducts.css', 'all');
	}

	public function hookAddProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookUpdateProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookDeleteProduct($params)
	{
		$this->_clearCache('*');
	}

	public function _clearCache($template, $cache_id = NULL, $compile_id = NULL)
	{
		parent::_clearCache('blocknewproducts.tpl');
		parent::_clearCache('blocknewproducts_home.tpl', 'blocknewproducts-home');
		parent::_clearCache('tab.tpl', 'blocknewproducts-tab');
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Products to display'),
						'name' => 'NEW_PRODUCTS_NBR',
						'class' => 'fixed-width-xs',
						'desc' => $this->l('Define the number of products to be displayed in this block.')
					),
					array(
						'type'  => 'text',
						'label' => $this->l('Number of days for which the product is considered \'new\''),
						'name'  => 'PS_NB_DAYS_NEW_PRODUCT',
						'class' => 'fixed-width-xs',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Always display this block'),
						'name' => 'PS_BLOCK_NEWPRODUCTS_DISPLAY',
						'desc' => $this->l('Show the block even if no new products are available.'),
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitBlockNewProducts';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'PS_NB_DAYS_NEW_PRODUCT' => Tools::getValue('PS_NB_DAYS_NEW_PRODUCT', Configuration::get('PS_NB_DAYS_NEW_PRODUCT')),
			'PS_BLOCK_NEWPRODUCTS_DISPLAY' => Tools::getValue('PS_BLOCK_NEWPRODUCTS_DISPLAY', Configuration::get('PS_BLOCK_NEWPRODUCTS_DISPLAY')),
			'NEW_PRODUCTS_NBR' => Tools::getValue('NEW_PRODUCTS_NBR', Configuration::get('NEW_PRODUCTS_NBR')),
		);
	}
}



/**
* Get new products
*
* @param integer $id_lang Language id
* @param integer $pageNumber Start from (optional)
* @param integer $nbProducts Number of products to return (optional)
* @return array New products
*/

function getNewProducts($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, Context $context = null)
{
    if (!$context)
        $context = Context::getContext();

    $front = true;
    if (!in_array($context->controller->controller_type, array('front', 'modulefront')))
        $front = false;

    if ($page_number < 0) $page_number = 0;
    if ($nb_products < 1) $nb_products = 10;
    if (empty($order_by) || $order_by == 'position') $order_by = 'date_add';
    if (empty($order_way)) $order_way = 'DESC';
    if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add'  || $order_by == 'date_upd')
        $order_by_prefix = 'p';
    else if ($order_by == 'name')
        $order_by_prefix = 'pl';
    if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way))
        die(Tools::displayError());

    $sql_groups = '';
    if (Group::isFeatureActive())
    {
        $groups = FrontController::getCurrentCustomerGroups();
        $sql_groups = 'AND p.`id_product` IN (
            SELECT cp.`id_product`
            FROM `'._DB_PREFIX_.'category_group` cg
            LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
            WHERE cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
        ) AND cp.`id_category` = '.($id_lang == 2? 43:31);
    }

    if (strpos($order_by, '.') > 0)
    {
        $order_by = explode('.', $order_by);
        $order_by_prefix = $order_by[0];
        $order_by = $order_by[1];
    }

    if ($count)
    {
        $sql = 'SELECT COUNT(p.`id_product`) AS nb
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                WHERE product_shop.`active` = 1
                AND product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"
                '.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
                '.$sql_groups;
        return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    $sql = new DbQuery();
    $sql->select(
        'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
        pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, MAX(image_shop.`id_image`) id_image, il.`legend`, m.`name` AS manufacturer_name,
        product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new'
    );

    $sql->from('product', 'p');
    $sql->join(Shop::addSqlAssociation('product', 'p'));
    $sql->leftJoin('product_lang', 'pl', '
        p.`id_product` = pl.`id_product`
        AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl')
    );
    $sql->leftJoin('image', 'i', 'i.`id_product` = p.`id_product`');
    $sql->join(Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1'));
    $sql->leftJoin('image_lang', 'il', 'i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang);
    $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

    $sql->where('product_shop.`active` = 1');
    if ($front)
        $sql->where('product_shop.`visibility` IN ("both", "catalog")');
    $sql->where('product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"');
    if (Group::isFeatureActive())
        $sql->where('p.`id_product` IN (
            SELECT cp.`id_product`
            FROM `'._DB_PREFIX_.'category_group` cg
            LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
            WHERE cg.`id_group` '.$sql_groups.'
        )');
    $sql->groupBy('product_shop.id_product');

    $sql->orderBy((isset($order_by_prefix) ? pSQL($order_by_prefix).'.' : '').'`'.pSQL($order_by).'` '.pSQL($order_way));
    $sql->limit($nb_products, $page_number * $nb_products);

    if (Combination::isFeatureActive())
    {
        $sql->select('MAX(product_attribute_shop.id_product_attribute) id_product_attribute');
        $sql->leftOuterJoin('product_attribute', 'pa', 'p.`id_product` = pa.`id_product`');
        $sql->join(Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.default_on = 1'));
    }
    $sql->join(Product::sqlStock('p', Combination::isFeatureActive() ? 'product_attribute_shop' : 0));

    $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

    if ($order_by == 'price')
        Tools::orderbyPrice($result, $order_way);
    if (!$result)
        return false;

    $products_ids = array();
    foreach ($result as $row)
        $products_ids[] = $row['id_product'];
    // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
    Product::cacheFrontFeatures($products_ids, $id_lang);
    return Product::getProductsProperties((int)$id_lang, $result);
}
