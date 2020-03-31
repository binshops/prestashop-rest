<?php

require_once __DIR__ . '/../AbstractRESTController.php';
define('PRICE_REDUCTION_TYPE_PERCENT' , 'percentage');

/**
 * This REST endpoint gets details of a product
 */
class BinshopsrestProductdetailModuleFrontController extends AbstractRESTController
{
    private $product = null;

    protected function processGetRequest()
    {
        if (!(int) Tools::getValue('product_id', 0)) {
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
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $this->getProduct()
            ]));
            die;
        }
    }

    protected function processPostRequest()
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
                    $temp_price = (float) ($this->product->specificPrice['reduction'] * 100);
                    $percent = (float) ($temp_price/ $price_without_reduction);
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
        $temp_images = $this->product->getImages((int) $this->context->language->id);
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
                    $attribute_list .= (int) $attribute_id . '_';
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
        $product['product_info'] = $product_info;
        $product['accessories'] = $this->getProductAccessories();
        $product['customization_fields'] = $this->getCustomizationFields();
        $product['pack_products'] = $this->getPackProducts();
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
            $final_attachment_data[$count]['download_link'] = $this->context->link->getPageLink('attachment', true, null, "id_attachment=".$attachment['id_attachment']);
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
                    $colors[$row['id_attribute']]['attributes_quantity'] += (int) $row['quantity'];
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
                    $groups[$row['id_attribute_group']]['default'] = (int) $row['id_attribute'];
                }
                if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']])) {
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
                }
                $r_attr = $row['id_attribute_group'];
                $groups[$r_attr]['attributes_quantity'][$row['id_attribute']] += (int) $row['quantity'];

                $combinations[$row['id_product_attribute']]['attributes'][] = (int) $row['id_attribute'];

                //calculate full price for combination
                $priceDisplay = Product::getTaxCalculationMethod(0); //(int)$this->context->cookie->id_customer
                if (!$priceDisplay || $priceDisplay == 2) {
                    $combination_price = $this->product->getPrice(true, $row['id_product_attribute']);
                } else {
                    $combination_price = $this->product->getPrice(false, $row['id_product_attribute']);
                }
                $combinations[$row['id_product_attribute']]['price'] = $this->formatPrice($combination_price);
                $combinations[$row['id_product_attribute']]['quantity'] = (int) $row['quantity'];
                $combinations[$row['id_product_attribute']]['minimal_quantity'] = (int) $row['minimal_quantity'];
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
                    $attribute_list .= '\'' . (int) $id_attribute . '\',';
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
    public function getProductAccessories()
    {
        $accessory_products = array();
        $accessories = $this->product->getAccessories($this->context->language->id);
        $has_accessories = "1";

        if ($accessories) {
            $index = 0;
            foreach ($accessories as $accessory) {
                if ($accessory['available_for_order']) {
                    $accessory_products[$index] = array(
                        'id' => $accessory['id_product'],
                        'name' => $accessory['name'],
                        'price' => $this->formatPrice($accessory['price_without_reduction']),
                        'available_for_order' => $accessory['available_for_order'],
                        'show_price' => $accessory['show_price'],
                        'new_products' => (isset($accessory['new']) && $accessory['new'] == 1) ? "1" : "0",
                        'on_sale_products' => $accessory['on_sale'],
                        'src' => $this->context->link->getImageLink(
                        /* Changes started by rishabh jain on 3rd sep 2018
                        * To get url encoded image link as per admin setting
                        */
                            $this->getUrlEncodedImageLink($accessory['link_rewrite']),
                            /* Changes over */
                            $accessory['id_image'],
                            $this->getImageType('large')
                        )
                    );
                    if (count($accessory['specific_prices']) > 0) {
                        $accessory_products[$index]['discount_price'] = $this->formatPrice($accessory['price']);
                        if ($accessory['specific_prices']['reduction_type'] == PRICE_REDUCTION_TYPE_PERCENT) {
                            $temp_p = (float) $accessory['specific_prices']['reduction'] * 100;
                            $accessory_products[$index]['discount_percentage'] = $temp_p;
                            unset($temp_p);
                        } else {
                            if ($accessory['price_without_reduction']) {
                                $temp_price = ((float) $accessory['specific_prices']['reduction'] * 100);
                                $percent = (float) ($temp_price / $accessory['price_without_reduction']);
                                unset($temp_price);
                            } else {
                                $percent = 0;
                            }
                            $accessory_products[$index]['discount_percentage'] = Tools::ps_round($percent);
                        }
                    } else {
                        $accessory_products[$index]['discount_price'] = '';
                        $accessory_products[$index]['discount_percentage'] = '';
                    }
                    $index++;
                }
            }
        } else {
            $has_accessories = "0";
        }
        return array('has_accessories' => $has_accessories, 'accessories_items' => $accessory_products);
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
    public function getPackProducts()
    {
        $is_pack = "0";
        $pack_products = array();
        if (Pack::isPack($this->product->id)) {
            $is_pack = "1";
            $pack_items = Pack::getItemTable($this->product->id, $this->context->language->id, true);
            if ($pack_items) {
                $index = 0;
                foreach ($pack_items as $item) {
                    $pack_products[$index] = array(
                        'id' => $item['id_product'],
                        'name' => $item['name'],
                        'price' => $this->formatPrice($item['price_without_reduction']),
                        'available_for_order' => $item['available_for_order'],
                        'show_price' => $item['show_price'],
                        'new_products' => (isset($item['new']) && $item['new'] == 1) ? "1" : "0",
                        'on_sale_products' => $item['on_sale'],
                        'src' => $this->context->link->getImageLink(
                        /* Changes started by rishabh jain on 3rd sep 2018
                        * To get url encoded image link as per admin setting
                        */
                            $this->getUrlEncodedImageLink($item['link_rewrite']),
                            /* Changes over */
                            $item['id_image'],
                            $this->getImageType('large')
                        ),
                        'pack_quantity' => $item['pack_quantity']
                    );
                    if (count($item['specific_prices']) > 0) {
                        $pack_products[$index]['discount_price'] = $this->formatPrice($item['price']);
                        if ($item['specific_prices']['reduction_type'] == PRICE_REDUCTION_TYPE_PERCENT) {
                            $item[$index]['discount_percentage'] = (float) $item['specific_prices']['reduction'] * 100;
                        } else {
                            if ($item['price_without_reduction']) {
                                $temp_price = (float) ($item['specific_prices']['reduction'] * 100);
                                $percent = (float) ($temp_price / $item['price_without_reduction']);
                                unset($temp_price);
                            } else {
                                $percent = 0;
                            }
                            $pack_products[$index]['discount_percentage'] = Tools::ps_round($percent);
                        }
                    } else {
                        $pack_products[$index]['discount_price'] = '';
                        $pack_products[$index]['discount_percentage'] = '';
                    }
                    $index++;
                }
            }
        }
        return array('is_pack' => $is_pack, 'pack_items' => $pack_products);
    }
}