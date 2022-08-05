<?php
/**
 * BINSHOPS | Best In Shops REST API
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

class RESTMainMenu
{
    const MENU_JSON_CACHE_KEY = 'REST_MOD_BLOCKTOPMENU_MENU_JSON';

    private $context;

    private $user_groups;

    protected $pattern = '/^([A-Z_]*)[0-9]+/';

    private $imageFiles;

    protected $spacer_size = '5';

    protected $page_name = '';

    private $mainmenu_module;

    public function renderMenu($context, $mainmenu){
        $this->mainmenu_module = $mainmenu;
        $this->context = $context;
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;

        $this->user_groups = Customer::getGroupsStatic($context->customer->id);
        $groupsKey = empty($this->user_groups) ? '' : '_' . join("_", $this->user_groups);
        $key = self::MENU_JSON_CACHE_KEY . '_' . $id_lang . '_' . $id_shop . $groupsKey . '.json';
        $cacheDir = $this->getCacheDirectory();
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $key;
        $menu = json_decode(@file_get_contents($cacheFile), true);
        if (!is_array($menu) || json_last_error() !== JSON_ERROR_NONE) {
            $menu = $this->makeMenu();
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir);
            }
            file_put_contents($cacheFile, json_encode($menu));
        }

        return $menu['children'];
    }

    protected function getCacheDirectory()
    {
        $dir =_PS_CACHE_DIR_ . 'ps_mainmenu';

        if (isset($this->context->context->customer)) {
            $groups = $this->context->customer->getGroups();
            if (!empty($groups)) {
                $dir .=  '/' . implode('_', $groups);
            }
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    protected function makeMenu()
    {
        $root_node = $this->makeNode([
            'label' => null,
            'type' => 'root',
            'children' => [],
        ]);

        $menu_items = $this->getMenuItems();
        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) Shop::getContextShopID();

        foreach ($menu_items as $item) {
            if (!$item) {
                continue;
            }

            preg_match($this->pattern, $item, $value);
            $id = (int) substr($item, strlen($value[1]), strlen($item));

            switch (substr($item, 0, strlen($value[1]))) {
                case 'CAT':
                    $categories = $this->generateCategoriesMenu(
                        Category::getNestedCategories($id, $id_lang, false, $this->user_groups)
                    );
                    $root_node['children'] = array_merge($root_node['children'], $categories);
                    break;

                case 'PRD':
                    $product = new Product((int) $id, true, (int) $id_lang);
                    if ($product->id) {
                        $root_node['children'][] = $this->makeNode([
                            'type' => 'product',
                            'page_identifier' => 'product-' . $product->id,
                            'label' => $product->name,
                            'url' => $product->getLink(),
                        ]);
                    }
                    break;

                case 'CMS':
                    $cms = CMS::getLinks((int) $id_lang, [$id]);
                    if (count($cms)) {
                        $root_node['children'][] = $this->makeNode([
                            'type' => 'cms-page',
                            'page_identifier' => 'cms-page-' . $id,
                            'label' => $cms[0]['meta_title'],
                            'url' => $cms[0]['link'],
                        ]);
                    }
                    break;

                case 'CMS_CAT':
                    $root_node['children'][] = $this->generateCMSCategoriesMenu((int) $id, (int) $id_lang);
                    break;

                // Case to handle the option to show all Manufacturers
                case 'ALLMAN':
                    $children = array_map(function ($manufacturer) use ($id_lang) {
                        return $this->makeNode([
                            'type' => 'manufacturer',
                            'page_identifier' => 'manufacturer-' . $manufacturer['id_manufacturer'],
                            'label' => $manufacturer['name'],
                            'url' => $this->context->link->getManufacturerLink(
                                new Manufacturer($manufacturer['id_manufacturer'], $id_lang),
                                null,
                                $id_lang
                            ),
                        ]);
                    }, Manufacturer::getManufacturers());

                    $root_node['children'][] = $this->makeNode([
                        'type' => 'manufacturers',
                        'page_identifier' => 'manufacturers',
                        'label' => $this->mainmenu_module->getTranslator()->trans('All brands', [], 'Modules.Mainmenu.Admin'),
                        'url' => $this->context->link->getPageLink('manufacturer'),
                        'children' => $children,
                    ]);
                    break;

                case 'MAN':
                    $manufacturer = new Manufacturer($id, $id_lang);
                    if ($manufacturer->id) {
                        $root_node['children'][] = $this->makeNode([
                            'type' => 'manufacturer',
                            'page_identifier' => 'manufacturer-' . $manufacturer->id,
                            'label' => $manufacturer->name,
                            'url' => $this->context->link->getManufacturerLink(
                                $manufacturer,
                                null,
                                $id_lang
                            ),
                        ]);
                    }
                    break;

                // Case to handle the option to show all Suppliers
                case 'ALLSUP':
                    $children = array_map(function ($supplier) use ($id_lang) {
                        return $this->makeNode([
                            'type' => 'supplier',
                            'page_identifier' => 'supplier-' . $supplier['id_supplier'],
                            'label' => $supplier['name'],
                            'url' => $this->context->link->getSupplierLink(
                                new Supplier($supplier['id_supplier'], $id_lang),
                                null,
                                $id_lang
                            ),
                        ]);
                    }, Supplier::getSuppliers());

                    $root_node['children'][] = $this->makeNode([
                        'type' => 'suppliers',
                        'page_identifier' => 'suppliers',
                        'label' => $this->mainmenu_module->getTranslator()->trans('All suppliers', [], 'Modules.Mainmenu.Admin'),
                        'url' => $this->context->link->getPageLink('supplier'),
                        'children' => $children,
                    ]);
                    break;

                case 'SUP':
                    $supplier = new Supplier($id, $id_lang);
                    if ($supplier->id) {
                        $root_node['children'][] = $this->makeNode([
                            'type' => 'supplier',
                            'page_identifier' => 'supplier-' . $supplier->id,
                            'label' => $supplier->name,
                            'url' => $this->context->link->getSupplierLink(
                                $supplier,
                                null,
                                $id_lang
                            ),
                        ]);
                    }
                    break;

                case 'SHOP':
                    $shop = new Shop((int) $id);
                    if (Validate::isLoadedObject($shop)) {
                        $root_node['children'][] = $this->makeNode([
                            'type' => 'shop',
                            'page_identifier' => 'shop-' . $id,
                            'label' => $shop->name,
                            'url' => $shop->getBaseURL(),
                        ]);
                    }
                    break;
                case 'LNK':
                    $link = Ps_MenuTopLinks::get($id, $id_lang, $id_shop);
                    if (!empty($link)) {
                        if (!isset($link[0]['label']) || ($link[0]['label'] == '')) {
                            $default_language = Configuration::get('PS_LANG_DEFAULT');
                            $link = Ps_MenuTopLinks::get($link[0]['id_linksmenutop'], $default_language, (int) Shop::getContextShopID());
                        }
                        $root_node['children'][] = $this->makeNode([
                            'type' => 'link',
                            'page_identifier' => 'lnk-' . Tools::str2url($link[0]['label']),
                            'label' => $link[0]['label'],
                            'url' => $link[0]['link'],
                            'open_in_new_window' => $link[0]['new_window'],
                        ]);
                    }
                    break;
            }
        }

        return $this->mapTree(function ($node, $depth) {
            $node['depth'] = $depth;

            return $node;
        }, $root_node);
    }

    protected function makeNode(array $fields)
    {
        $defaults = [
            'id' => '',
            'slug' => '',
            'type' => '',
            'label' => '',
            'url' => '',
            'children' => [],
            'image_urls' => [],
            'page_identifier' => null
        ];

        return array_merge($defaults, $fields);
    }

    protected function getMenuItems()
    {
        $items = Tools::getValue('items');
        if (is_array($items) && count($items)) {
            return $items;
        } else {
            $shops = Shop::getContextListShopID();
            $conf = null;

            if (count($shops) > 1) {
                foreach ($shops as $key => $shop_id) {
                    $shop_group_id = Shop::getGroupFromShop($shop_id);
                    $conf .= (string)($key > 1 ? ',' : '').Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $shop_group_id, $shop_id);
                }
            } else {
                $shop_id = (int)$shops[0];
                $shop_group_id = Shop::getGroupFromShop($shop_id);
                $conf = Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $shop_group_id, $shop_id);
            }

            if (strlen($conf)) {
                return explode(',', $conf);
            } else {
                return array();
            }
        }
    }

    protected function generateCategoriesMenu($categories, $is_children = 0)
    {
        $nodes = [];

        foreach ($categories as $key => $category) {
            $node = $this->makeNode([]);
            if ($category['level_depth'] > 1) {
                $cat = new Category($category['id_category']);
                $link = $cat->getLink();
                // Check if customer is set and check access
                if (Validate::isLoadedObject($this->context->customer) && !$cat->checkAccess($this->context->customer->id)) {
                    continue;
                }
            } else {
                $link = $this->context->link->getPageLink('index');
            }

            $node['id'] = $category['id_category'];
            $node['slug'] = $category['link_rewrite'];
            $node['url'] = $link;
            $node['type'] = 'category';
            $node['page_identifier'] = 'category-' . $category['id_category'];

            /* Whenever a category is not active we shouldnt display it to customer */
            if ((bool) $category['active'] === false) {
                continue;
            }

            $current = $this->page_name == 'category' && (int) Tools::getValue('id_category') == (int) $category['id_category'];
            $node['current'] = $current;
            $node['label'] = $category['name'];
            $node['image_urls'] = [];

            if (isset($category['children']) && !empty($category['children'])) {
                $node['children'] = $this->generateCategoriesMenu($category['children'], 1);
            }

            if ($this->imageFiles === null) {
                $this->imageFiles = scandir(_PS_CAT_IMG_DIR_);
            }

            if (count(preg_grep('/^' . $category['id_category'] . '-([0-9])?_thumb.jpg/i', $this->imageFiles)) > 0) {
                foreach ($this->imageFiles as $file) {
                    if (preg_match('/^' . $category['id_category'] . '-([0-9])?_thumb.jpg/i', $file) === 1) {
                        $image_url = $this->context->link->getMediaLink(_THEME_CAT_DIR_ . $file);
                        $node['image_urls'][] = $image_url;
                    }
                }
            }

            $nodes[] = $node;
        }

        return $nodes;
    }

    protected function generateCMSCategoriesMenu($id_cms_category, $id_lang)
    {
        $category = new CMSCategory($id_cms_category, $id_lang);

        $rawSubCategories = $this->getCMSCategories(false, $id_cms_category, $id_lang);
        $rawSubPages = $this->getCMSPages($id_cms_category);

        $subCategories = array_map(function ($category) use ($id_lang) {
            return $this->generateCMSCategoriesMenu($category['id_cms_category'], $id_lang);
        }, $rawSubCategories);

        $subPages = array_map(function ($page) use ($id_lang) {
            return $this->makeNode([
                'type' => 'cms-page',
                'page_identifier' => 'cms-page-' . $page['id_cms'],
                'label' => $page['meta_title'],
                'url' => $this->context->link->getCMSLink(
                    new CMS($page['id_cms'], $id_lang),
                    null, null,
                    $id_lang
                ),
            ]);
        }, $rawSubPages);

        $node = $this->makeNode([
            'type' => 'cms-category',
            'page_identifier' => 'cms-category-' . $id_cms_category,
            'label' => $category->name,
            'url' => $category->getLink(),
            'children' => array_merge($subCategories, $subPages),
        ]);

        return $node;
    }

    protected function mapTree(callable $cb, array $node, $depth = 0)
    {
        $node['children'] = array_map(function ($child) use ($cb, $depth) {
            return $this->mapTree($cb, $child, $depth + 1);
        }, $node['children']);

        return $cb($node, $depth);
    }

    protected function getCMSCategories($recursive = false, $parent = 1, $id_lang = false, $id_shop = false)
    {
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        $id_shop = ($id_shop !== false) ? $id_shop : Context::getContext()->shop->id;
        $join_shop = '';
        $where_shop = '';

        if (Tools::version_compare(_PS_VERSION_, '1.6.0.12', '>=') == true) {
            $join_shop = ' INNER JOIN `' . _DB_PREFIX_ . 'cms_category_shop` cs
			ON (bcp.`id_cms_category` = cs.`id_cms_category`)';
            $where_shop = ' AND cs.`id_shop` = ' . (int) $id_shop . ' AND cl.`id_shop` = ' . (int) $id_shop;
        }

        if ($recursive === false) {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
				FROM `' . _DB_PREFIX_ . 'cms_category` bcp' .
                $join_shop . '
				INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
				ON (bcp.`id_cms_category` = cl.`id_cms_category`)
				WHERE cl.`id_lang` = ' . (int) $id_lang . '
				AND bcp.`id_parent` = ' . (int) $parent .
                $where_shop;

            return Db::getInstance()->executeS($sql);
        } else {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
				FROM `' . _DB_PREFIX_ . 'cms_category` bcp' .
                $join_shop . '
				INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
				ON (bcp.`id_cms_category` = cl.`id_cms_category`)
				WHERE cl.`id_lang` = ' . (int) $id_lang . '
				AND bcp.`id_parent` = ' . (int) $parent .
                $where_shop;

            $results = Db::getInstance()->executeS($sql);
            foreach ($results as $result) {
                $sub_categories = $this->getCMSCategories(true, $result['id_cms_category'], (int) $id_lang);
                if ($sub_categories && count($sub_categories) > 0) {
                    $result['sub_categories'] = $sub_categories;
                }
                $categories[] = $result;
            }

            return isset($categories) ? $categories : false;
        }
    }

    protected function getCMSPages($id_cms_category, $id_shop = false, $id_lang = false)
    {
        $id_shop = ($id_shop !== false) ? (int) $id_shop : (int) Context::getContext()->shop->id;
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;

        $where_shop = '';
        if (Tools::version_compare(_PS_VERSION_, '1.6.0.12', '>=') == true) {
            $where_shop = ' AND cl.`id_shop` = ' . (int) $id_shop;
        }

        $sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms` c
			INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs
			ON (c.`id_cms` = cs.`id_cms`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
			ON (c.`id_cms` = cl.`id_cms`)
			WHERE c.`id_cms_category` = ' . (int) $id_cms_category . '
			AND cs.`id_shop` = ' . (int) $id_shop . '
			AND cl.`id_lang` = ' . (int) $id_lang .
            $where_shop . '
			AND c.`active` = 1
			ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }
}

