<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';
define('PRICE_REDUCTION_TYPE_PERCENT', 'percentage');

use PrestaShop\PrestaShop\Core\Product\ProductExtraContentFinder;
use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;

/**
 * This REST endpoint gets details of a product
 */
class BinshopsrestProductdetailModuleFrontController extends AbstractRESTController
{
    /** @var Product */
    private $product = null;
    private $taxConfiguration;

    protected function processPostRequest()
    {
        $this->taxConfiguration = new TaxConfiguration();

        if (!(int)Tools::getValue('product_id', 0)) {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'message' => 'product id not specified'
            ]));
            die;
        }
        $this->product = new Product(
            Tools::getValue('product_id', 0),
            true,
            $this->context->language->id,
            $this->context->shop->id,
            $this->context
        );

        if (!Validate::isLoadedObject($this->product)) {
            $this->ajaxRender(json_encode([
                'code' => 302,
                'message' => 'product not found'
            ]));
            die;
        } else {
            //this is when you change an attribute, every time a request is sent to get the price and its discount
            if ((boolean)Tools::getValue('refresh', 0)) {
                $product = $this->getTemplateVarProduct();

                $this->ajaxRender(json_encode([
                    'psdata' => $product,
                    'code' => 200,
                    'success' => true
                ]));
                die;
            }

            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $this->getProduct()
            ]));
            die;
        }
    }

    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'POST not supported on this path'
        ]));
        die;
    }

    protected function processPutRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'put not supported on this path'
        ]));
        die;
    }

    protected function processDeleteRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'delete not supported on this path'
        ]));
        die;
    }

    /**
     * Get Product details
     *
     * @return array product data
     */
    public function getProduct()
    {
        $product = array();
        $product['id_product'] = $this->product->id;
        $product['name'] = $this->product->name;
        $product['available_for_order'] = $this->product->available_for_order;
        $product['show_price'] = $this->product->show_price;
        $product['new_products'] = (isset($this->product->new) && $this->product->new == 1) ? "1" : "0";
        $product['on_sale_products'] = $this->product->on_sale;
        $product['quantity'] = $this->product->quantity;
        $product['minimal_quantity'] = $this->product->minimal_quantity;
        if ($this->product->out_of_stock == 1) {
            $product['allow_out_of_stock'] = "1";
        } elseif ($this->product->out_of_stock == 0) {
            $product['allow_out_of_stock'] = "0";
        } elseif ($this->product->out_of_stock == 2) {
            $out_of_stock = Configuration::get('PS_ORDER_OUT_OF_STOCK');
            if ($out_of_stock == 1) {
                $product['allow_out_of_stock'] = "1";
            } else {
                $product['allow_out_of_stock'] = "0";
            }
        }


        $priceDisplay = Product::getTaxCalculationMethod(0); //(int)$this->context->cookie->id_customer
        if (!$priceDisplay || $priceDisplay == 2) {
            $price = $this->product->getPrice(true, false);
            $price_without_reduction = $this->product->getPriceWithoutReduct(false);
        } else {
            $price = $this->product->getPrice(false, false);
            $price_without_reduction = $this->product->getPriceWithoutReduct(true);
        }
        if ($priceDisplay >= 0 && $priceDisplay <= 2) {
            if ($price_without_reduction <= 0 || !$this->product->specificPrice) {
                $product['price'] = $this->formatPrice($price);
                $product['discount_price'] = '';
                $product['discount_percentage'] = '';
            } else {
                if ($this->product->specificPrice
                    && $this->product->specificPrice['reduction_type'] == PRICE_REDUCTION_TYPE_PERCENT) {
                    $product['discount_percentage'] = $this->product->specificPrice['reduction'] * 100;
                } elseif ($this->product->specificPrice
                    && $this->product->specificPrice['reduction_type'] == 'amount'
                    && $this->product->specificPrice['reduction'] > 0) {
                    $temp_price = (float)($this->product->specificPrice['reduction'] * 100);
                    $percent = (float)($temp_price / $price_without_reduction);
                    $product['discount_percentage'] = Tools::ps_round($percent);
                    unset($temp_price);
                }
                $product['price'] = $this->formatPrice($price_without_reduction);
                $product['discount_price'] = $this->formatPrice($price);
            }
        } else {
            $product['price'] = '';
            $product['discount_price'] = '';
            $product['discount_percentage'] = '';
        }

        $product['images'] = array();
        $temp_images = $this->product->getImages((int)$this->context->language->id);
        $cover = false;
        $images = array();
        foreach ($temp_images as $image) {
            if ($image['cover']) {
                $cover = $image;
            } else {
                $images[] = $image;
            }
        }

        if ($cover) {
            $images = array_merge(array($cover), $images);
        }
        foreach ($images as $image) {
            $product['images'][]['src'] = $this->context->link->getImageLink(
            /* Changes started by rishabh jain on 3rd sep 2018
            * To get url encoded image link as per admin setting
            */
                urlencode($this->product->link_rewrite),
                /* Changes over */
                ($this->product->id . '-' . $image['id_image']),
                $this->getImageType('large')
            );
        }
        $options = array();
        $combinations = array();
        $attributes = $this->getProductAttributesGroups();
        if (!empty($attributes['groups'])) {
            $index = 0;
            foreach ($attributes['groups'] as $grp_id => $grp) {
                $options[$index]['id'] = $grp_id;
                $options[$index]['title'] = $grp['name'];
                if ($grp['group_type'] == 'color') {
                    $options[$index]['is_color_option'] = 1;
                } else {
                    $options[$index]['is_color_option'] = 0;
                }
                $item = array();
                foreach ($grp['attributes'] as $key => $group_item) {
                    if ($grp['group_type'] == 'color') {
                        $hex_value = '';
                        if (isset($attributes['colors'][$key]['value'])) {
                            $hex_value = $attributes['colors'][$key]['value'];
                        }
                        $item[] = array(
                            'id' => $key,
                            'value' => $group_item,
                            'hex_value' => $hex_value
                        );
                    } else {
                        $item[] = array(
                            'id' => $key,
                            'value' => $group_item
                        );
                    }
                }
                $options[$index]['items'] = $item;
                $index++;
            }
        }
        if (!empty($attributes['combinations'])) {
            $index = 0;
            foreach ($attributes['combinations'] as $attr_id => $attr) {
                $combinations[$index]['id_product_attribute'] = $attr_id;
                $combinations[$index]['quantity'] = $attr['quantity'];
                $combinations[$index]['price'] = $attr['price'];
                $combinations[$index]['minimal_quantity'] = $attr['minimal_quantity'];
                $attribute_list = '';
                foreach ($attr['attributes'] as $attribute_id) {
                    $attribute_list .= (int)$attribute_id . '_';
                }
                $attribute_list = rtrim($attribute_list, '_');
                $combinations[$index]['combination_code'] = $attribute_list;
                $index++;
            }
        }
        $product['combinations'] = $combinations;
        $product['options'] = $options;

        $product['description'] = preg_replace('/<iframe.*?\/iframe>/i', '', $this->product->description);

        /*end:changes made by aayushi on 1 DEC 2018 to add Short Description on product page*/
        $product_info = array();
        if ($this->product->id_manufacturer) {
            $product_info[] = array(
                'name' => $this->l('Brand'),
                'value' => Manufacturer::getNameById($this->product->id_manufacturer)
            );
        }

        $product_info[] = array(
            'name' => $this->l('SKU'),
            'value' => $this->product->reference
        );
        $product_info[] = array(
            'name' => $this->l('Condition'),
            'value' => Tools::ucfirst($this->product->condition)
        );

        $features = $this->product->getFrontFeatures($this->context->language->id);
        if (!empty($features)) {
            foreach ($features as $f) {
                $product_info[] = array('name' => $f['name'], 'value' => $f['value']);
            }
        }

        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->getTranslator()
        );

        $product['product_info'] = $product_info;
        $product['accessories'] = $this->getProductAccessories($presenter);
        $product['customization_fields'] = $this->getCustomizationFields();
        $product['pack_products'] = $this->getPackProducts($presenter);
        $product['seller_info'] = array();

        //Add seller Information if Marketplace is installed and feature is enable
        $product['seller_info'] = array();

        $product['product_attachments_array'] = $this->getProductAttachmentURLs($this->product->id);

        $link = new Link();
        $url = $link->getProductLink($product);
        $product['product_url'] = $url;

        return $product;
    }

    /**
     * Get Virtual product attchements URLS
     *
     * @param int $id_product product id
     * @return array product attachment data
     */
    public function getProductAttachmentURLs($id_product)
    {
        $final_attachment_data = array();
        $attachments = Product::getAttachmentsStatic((int)$this->context->language->id, $id_product);
        $count = 0;
        foreach ($attachments as $attachment) {
            $final_attachment_data[$count]['download_link'] = $this->context->link->getPageLink('attachment', true, null, "id_attachment=" . $attachment['id_attachment']);
            $final_attachment_data[$count]['file_size'] = Tools::formatBytes($attachment['file_size'], 2);
            $final_attachment_data[$count]['description'] = $attachment['description'];
            $final_attachment_data[$count]['file_name'] = $attachment['file_name'];
            $final_attachment_data[$count]['mime'] = $attachment['mime'];
            $final_attachment_data[$count]['display_name'] = $attachment['name'];
            $count++;
        }
        return $final_attachment_data;
    }

    /**
     * Get details of product attributes groups
     *
     * @return array product attribute group data
     */
    public function getProductAttributesGroups()
    {
        $colors = array();
        $groups = array();
        $combinations = array();

        $attributes_groups = $this->product->getAttributesGroups($this->context->language->id);

        if (is_array($attributes_groups) && $attributes_groups) {
            foreach ($attributes_groups as $row) {
                // Color management
                if (isset($row['is_color_group'])
                    && $row['is_color_group']
                    && (isset($row['attribute_color']) && $row['attribute_color'])
                    || (file_exists(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg'))) {
                    $colors[$row['id_attribute']]['value'] = $row['attribute_color'];
                    $colors[$row['id_attribute']]['name'] = $row['attribute_name'];
                    if (!isset($colors[$row['id_attribute']]['attributes_quantity'])) {
                        $colors[$row['id_attribute']]['attributes_quantity'] = 0;
                    }
                    $colors[$row['id_attribute']]['attributes_quantity'] += (int)$row['quantity'];
                }
                if (!isset($groups[$row['id_attribute_group']])) {
                    $groups[$row['id_attribute_group']] = array(
                        'group_name' => $row['group_name'],
                        'name' => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default' => -1,
                    );
                }

                $attr_g = $row['id_attribute_group'];
                $groups[$attr_g]['attributes'][$row['id_attribute']] = $row['attribute_name'];
                if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1) {
                    $groups[$row['id_attribute_group']]['default'] = (int)$row['id_attribute'];
                }
                if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']])) {
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
                }
                $r_attr = $row['id_attribute_group'];
                $groups[$r_attr]['attributes_quantity'][$row['id_attribute']] += (int)$row['quantity'];

                $combinations[$row['id_product_attribute']]['attributes'][] = (int)$row['id_attribute'];

                //calculate full price for combination
                $priceDisplay = Product::getTaxCalculationMethod(0); //(int)$this->context->cookie->id_customer
                if (!$priceDisplay || $priceDisplay == 2) {
                    $combination_price = $this->product->getPrice(true, $row['id_product_attribute']);
                } else {
                    $combination_price = $this->product->getPrice(false, $row['id_product_attribute']);
                }
                $combinations[$row['id_product_attribute']]['price'] = $this->formatPrice($combination_price);
                $combinations[$row['id_product_attribute']]['quantity'] = (int)$row['quantity'];
                $combinations[$row['id_product_attribute']]['minimal_quantity'] = (int)$row['minimal_quantity'];
            }

            // wash attributes list (if some attributes are unavailables and if allowed to wash it)
            if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock)
                && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0) {
                foreach ($groups as &$group) {
                    foreach ($group['attributes_quantity'] as $key => &$quantity) {
                        if ($quantity <= 0) {
                            unset($group['attributes'][$key]);
                        }
                    }
                }

                foreach ($colors as $key => $color) {
                    if ($color['attributes_quantity'] <= 0) {
                        unset($colors[$key]);
                    }
                }
            }
            foreach ($combinations as $id_product_attribute => $comb) {
                $attribute_list = '';
                foreach ($comb['attributes'] as $id_attribute) {
                    $attribute_list .= '\'' . (int)$id_attribute . '\',';
                }
                $attribute_list = rtrim($attribute_list, ',');
                $combinations[$id_product_attribute]['list'] = $attribute_list;
            }
        }

        return array(
            'groups' => $groups,
            'colors' => (count($colors)) ? $colors : false,
            'combinations' => $combinations
        );
    }

    /**
     * Get details of accessories products
     *
     * @return array product accessories information
     */
    public function getProductAccessories($presenter)
    {
        $accessories = $this->product->getAccessories($this->context->language->id);

        if (is_array($accessories)) {
            foreach ($accessories as &$accessory) {
                $accessory = $presenter->present(
                    $this->getProductPresentationSettings(),
                    Product::getProductProperties($this->context->language->id, $accessory, $this->context),
                    $this->context->language
                );
            }
            unset($accessory);
        }

        return $accessories;
    }

    /**
     * Get details of customzable fields of customized product
     *
     * @return array product customized data
     */
    public function getCustomizationFields()
    {
        $customization_fields = array();
        $customization_data = $this->product->getCustomizationFields($this->context->language->id);
        $is_customizable = "0";

        if ($customization_data && is_array($customization_data)) {
            $index = 0;
            foreach ($customization_data as $data) {
                if ($data['type'] == 1) {
                    $is_customizable = "1";
                    $customization_fields[$index] = array(
                        'id_customization_field' => $data['id_customization_field'],
                        'required' => $data['required'],
                        'title' => $data['name'],
                        'type' => 'text'
                    );
                    $index++;
                } elseif ($data['type'] == 0 && $data['required'] == 1) {
                    $this->has_file_field = 1;
                }
            }
        }

        return array('is_customizable' => $is_customizable, 'customizable_items' => $customization_fields);
    }

    /**
     * Get details of pack products
     *
     * @return array pick items information
     */
    public function getPackProducts($presenter){
        $pack_items = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : [];
        $assembler = new ProductAssembler($this->context);

        $presentedPackItems = [];
        foreach ($pack_items as $item) {
            $presentedPackItems[] = $presenter->present(
                $this->getProductPresentationSettings(),
                $assembler->assembleProduct($item),
                $this->context->language
            );
        }

        return $presentedPackItems;
    }


    public function getTemplateVarProduct()
    {
        $factory = new ProductPresenterFactory($this->context, new TaxConfiguration());
        $productSettings = $factory->getPresentationSettings();
        // Hook displayProductExtraContent
        $extraContentFinder = new ProductExtraContentFinder();
        $objectPresenter = new ObjectPresenter();

        $product = $objectPresenter->present($this->product);
        $product['id_product'] = (int)$this->product->id;
        $product['out_of_stock'] = (int)$this->product->out_of_stock;
        $product['new'] = (int)$this->product->new;
        $product['id_product_attribute'] = $this->getIdProductAttributeByGroupOrRequestOrDefault();
//        $product['minimal_quantity'] = $this->getProductMinimalQuantity($product);
//        $product['quantity_wanted'] = $this->getRequiredQuantity($product);
        $product['extraContent'] = $extraContentFinder->addParams(array('product' => $this->product))->present();
        $product['ecotax'] = Tools::convertPrice((float)$product['ecotax'], $this->context->currency, true, $this->context);

        $product_full = Product::getProductProperties($this->context->language->id, $product, $this->context);

        $product_full = $this->addProductCustomizationData($product_full);

        $product_full['show_quantities'] = (bool)(
            Configuration::get('PS_DISPLAY_QTIES')
            && Configuration::get('PS_STOCK_MANAGEMENT')
            && $this->product->quantity > 0
            && $this->product->available_for_order
            && !Configuration::isCatalogMode()
        );

        $id_product_attribute = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        $product_price = $this->product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC, $id_product_attribute);

        $id_customer = (isset($this->context->customer) ? (int)$this->context->customer->id : 0);
        $id_group = (int)Group::getCurrent()->id;
        $id_country = $id_customer ? (int)Customer::getCurrentCountry($id_customer) : (int)Tools::getCountry();
        $id_currency = (int)$this->context->cookie->id_currency;
        $id_product = (int)$this->product->id;
        $id_product_attribute = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        $id_shop = $this->context->shop->id;


        $quantity_discounts = SpecificPrice::getQuantityDiscounts($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_product_attribute, false, (int)$this->context->customer->id);


        $tax = (float)$this->product->getTaxesRate(new Address((int)$this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));


        $this->quantity_discounts = $this->formatQuantityDiscounts($quantity_discounts, $product_price, (float)$tax, $this->product->ecotax);


        $product_full['quantity_label'] = ($this->product->quantity > 1) ? $this->trans('Items', array(), 'Shop.Theme.Catalog') : $this->trans('Item', array(), 'Shop.Theme.Catalog');
        $product_full['quantity_discounts'] = $this->quantity_discounts;

        if ($product_full['unit_price_ratio'] > 0) {
            $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
            $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
        }

        $group_reduction = GroupReduction::getValueForProduct($this->product->id, (int)Group::getCurrent()->id);
        if ($group_reduction === false) {
            $group_reduction = Group::getReduction((int)$this->context->cookie->id_customer) / 100;
        }
        $product_full['customer_group_discount'] = $group_reduction;
        $presenter = $factory->getPresenter();

        return $presenter->present(
            $productSettings,
            $product_full,
            $this->context->language
        );
    }

    private function getIdProductAttributeByGroupOrRequestOrDefault()
    {
        $idProductAttribute = $this->getIdProductAttributeByGroup();
        if (null === $idProductAttribute) {
            $idProductAttribute = (int)Tools::getValue('id_product_attribute');
        }

        if (0 === $idProductAttribute) {
            $idProductAttribute = (int)Product::getDefaultAttribute($this->product->id);
        }

        return $this->tryToGetAvailableIdProductAttribute($idProductAttribute);
    }

    private function getIdProductAttributeByGroup()
    {
        $groups = Tools::getValue('group');
        if (empty($groups)) {
            return null;
        }

        return (int)Product::getIdProductAttributeByIdAttributes(
            $this->product->id,
            $groups,
            true
        );
    }

    protected function addProductCustomizationData(array $product_full)
    {
        if ($product_full['customizable']) {
            $customizationData = array(
                'fields' => array(),
            );

            $customized_data = array();

            $already_customized = $this->context->cart->getProductCustomization(
                $product_full['id_product'],
                null,
                true
            );

            $id_customization = 0;
            foreach ($already_customized as $customization) {
                $id_customization = $customization['id_customization'];
                $customized_data[$customization['index']] = $customization;
            }

            $customization_fields = $this->product->getCustomizationFields($this->context->language->id);
            if (is_array($customization_fields)) {
                foreach ($customization_fields as $customization_field) {
                    // 'id_customization_field' maps to what is called 'index'
                    // in what Product::getProductCustomization() returns
                    $key = $customization_field['id_customization_field'];

                    $field = array();
                    $field['label'] = $customization_field['name'];
                    $field['id_customization_field'] = $customization_field['id_customization_field'];
                    $field['required'] = $customization_field['required'];

                    switch ($customization_field['type']) {
                        case Product::CUSTOMIZE_FILE:
                            $field['type'] = 'image';
                            $field['image'] = null;
                            $field['input_name'] = 'file' . $customization_field['id_customization_field'];

                            break;
                        case Product::CUSTOMIZE_TEXTFIELD:
                            $field['type'] = 'text';
                            $field['text'] = '';
                            $field['input_name'] = 'textField' . $customization_field['id_customization_field'];

                            break;
                        default:
                            $field['type'] = null;
                    }

                    if (array_key_exists($key, $customized_data)) {
                        $data = $customized_data[$key];
                        $field['is_customized'] = true;
                        switch ($customization_field['type']) {
                            case Product::CUSTOMIZE_FILE:
                                $imageRetriever = new ImageRetriever($this->context->link);
                                $field['image'] = $imageRetriever->getCustomizationImage(
                                    $data['value']
                                );
                                $field['remove_image_url'] = $this->context->link->getProductDeletePictureLink(
                                    $product_full,
                                    $customization_field['id_customization_field']
                                );

                                break;
                            case Product::CUSTOMIZE_TEXTFIELD:
                                $field['text'] = $data['value'];

                                break;
                        }
                    } else {
                        $field['is_customized'] = false;
                    }

                    $customizationData['fields'][] = $field;
                }
            }
            $product_full['customizations'] = $customizationData;
            $product_full['id_customization'] = $id_customization;
            $product_full['is_customizable'] = true;
        } else {
            $product_full['customizations'] = array(
                'fields' => array(),
            );
            $product_full['id_customization'] = 0;
            $product_full['is_customizable'] = false;
        }

        return $product_full;
    }

    private function tryToGetAvailableIdProductAttribute($checkedIdProductAttribute)
    {
        if (!Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) {
            $availableProductAttributes = array_filter(
                $this->product->getAttributeCombinations(),
                function ($elem) {
                    return $elem['quantity'] > 0;
                }
            );

            $availableProductAttribute = array_filter(
                $availableProductAttributes,
                function ($elem) use ($checkedIdProductAttribute) {
                    return $elem['id_product_attribute'] == $checkedIdProductAttribute;
                }
            );

            if (empty($availableProductAttribute) && count($availableProductAttributes)) {
                return (int)array_shift($availableProductAttributes)['id_product_attribute'];
            }
        }

        return $checkedIdProductAttribute;
    }

    protected function formatQuantityDiscounts($specific_prices, $price, $tax_rate, $ecotax_amount)
    {
        $priceFormatter = new PriceFormatter();

        foreach ($specific_prices as $key => &$row) {
            $row['quantity'] = &$row['from_quantity'];
            if ($row['price'] >= 0) {
                // The price may be directly set

                /** @var float $currentPriceDefaultCurrency current price with taxes in default currency */
                $currentPriceDefaultCurrency = (!$row['reduction_tax'] ? $row['price'] : $row['price'] * (1 + $tax_rate / 100)) + (float)$ecotax_amount;
                // Since this price is set in default currency,
                // we need to convert it into current currency
                $row['id_currency'];
                $currentPriceCurrentCurrency = Tools::convertPrice($currentPriceDefaultCurrency, $this->context->currency, true, $this->context);

                if ($row['reduction_type'] == 'amount') {
                    $currentPriceCurrentCurrency -= ($row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100));
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100);
                } else {
                    $currentPriceCurrentCurrency *= 1 - $row['reduction'];
                }
                $row['real_value'] = $price > 0 ? $price - $currentPriceCurrentCurrency : $currentPriceCurrentCurrency;
                $discountPrice = $price - $row['real_value'];

                if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                    if ($row['reduction_tax'] == 0 && !$row['price']) {
                        $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                    } else {
                        $row['discount'] = $priceFormatter->format($price - $row['real_value']);
                    }
                } else {
                    $row['discount'] = $priceFormatter->format($row['real_value']);
                }
            } else {
                if ($row['reduction_type'] == 'amount') {
                    if (Product::$_taxCalculationMethod == PS_TAX_INC) {
                        $row['real_value'] = $row['reduction_tax'] == 1 ? $row['reduction'] : $row['reduction'] * (1 + $tax_rate / 100);
                    } else {
                        $row['real_value'] = $row['reduction_tax'] == 0 ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100);
                    }
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] + ($row['reduction'] * $tax_rate) / 100;
                    $discountPrice = $price - $row['real_value'];
                    if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                        if ($row['reduction_tax'] == 0 && !$row['price']) {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                        } else {
                            $row['discount'] = $priceFormatter->format($price - $row['real_value']);
                        }
                    } else {
                        $row['discount'] = $priceFormatter->format($row['real_value']);
                    }
                } else {
                    $row['real_value'] = $row['reduction'] * 100;
                    $discountPrice = $price - $price * $row['reduction'];
                    if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                        if ($row['reduction_tax'] == 0) {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                        } else {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction']));
                        }
                    } else {
                        $row['discount'] = $row['real_value'] . '%';
                    }
                }
            }

            $row['save'] = $priceFormatter->format((($price * $row['quantity']) - ($discountPrice * $row['quantity'])));
            $row['nextQuantity'] = (isset($specific_prices[$key + 1]) ? (int)$specific_prices[$key + 1]['from_quantity'] : -1);
        }

        return $specific_prices;
    }

    protected function getProductPresentationSettings(){
        $settings = new ProductPresentationSettings();

        $settings->catalog_mode = Configuration::isCatalogMode();
        $settings->catalog_mode_with_prices = (int) Configuration::get('PS_CATALOG_MODE_WITH_PRICES');
        $settings->include_taxes = $this->taxConfiguration->includeTaxes();
        $settings->allow_add_variant_to_cart_from_listing = (int) Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');
        $settings->showPrices = Configuration::showPrices();
        $settings->lastRemainingItems = Configuration::get('PS_LAST_QTIES');

        return $settings;
    }
}
