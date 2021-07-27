<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

use PrestaShop\Decimal\Number;
use PrestaShop\Decimal\Operation\Rounding;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use Symfony\Component\Translation\TranslatorInterface;

class RESTProductLazyArray
{
    /**
     * @var ProductPresentationSettings
     */
    protected $settings;

    /**
     * @var array
     */
    protected $product;

    /**
     * @var Language
     */
    private $language;

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    /**
     * @var ImageRetriever
     */
    private $imageRetriever;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Link
     */
    private $link;

    public function __construct(
        ProductPresentationSettings $settings,
        array $product,
        Language $language,
        PriceFormatter $priceFormatter,
        ImageRetriever $imageRetriever,
        TranslatorInterface $translator,
        Link $link
    ) {
        $this->settings = $settings;
        $this->product = $product;
        $this->language = $language;
        $this->priceFormatter = $priceFormatter;
        $this->imageRetriever = $imageRetriever;
        $this->translator = $translator;
        $this->link = $link;

        $this->fillImages(
            $product,
            $language
        );

        $this->addPriceInformation(
            $settings,
            $product
        );

        $this->addQuantityInformation(
            $settings,
            $product,
            $language
        );
    }

    protected function addPriceInformation(
        ProductPresentationSettings $settings,
        array $product
    ) {
        $this->product['has_discount'] = false;
        $this->product['discount_type'] = null;
        $this->product['discount_percentage'] = null;
        $this->product['discount_percentage_absolute'] = null;
        $this->product['discount_amount'] = null;
        $this->product['discount_amount_to_display'] = null;

        if ($settings->include_taxes) {
            $price = $regular_price = $product['price'];
        } else {
            $price = $regular_price = $product['price_tax_exc'];
        }

        if ($product['specific_prices']) {
            $this->product['has_discount'] = (0 != $product['reduction']);
            $this->product['discount_type'] = $product['specific_prices']['reduction_type'];

            $absoluteReduction = new Number($product['specific_prices']['reduction']);
            $absoluteReduction = $absoluteReduction->times(new Number('100'));
            $negativeReduction = $absoluteReduction->toNegative();
            $presAbsoluteReduction = $absoluteReduction->round(2, Rounding::ROUND_HALF_UP);
            $presNegativeReduction = $negativeReduction->round(2, Rounding::ROUND_HALF_UP);

            // TODO: add percent sign according to locale preferences
            $this->product['discount_percentage'] = Tools::displayNumber($presNegativeReduction) . '%';
            $this->product['discount_percentage_absolute'] = Tools::displayNumber($presAbsoluteReduction) . '%';
            if ($settings->include_taxes) {
                $regular_price = $product['price_without_reduction'];
                $this->product['discount_amount'] = $this->priceFormatter->format(
                    $product['reduction']
                );
            } else {
                $regular_price = $product['price_without_reduction_without_tax'];
                $this->product['discount_amount'] = $this->priceFormatter->format(
                    $product['reduction_without_tax']
                );
            }
            $this->product['discount_amount_to_display'] = '-' . $this->product['discount_amount'];
        }

        $this->product['price_amount'] = $price;
        $this->product['price'] = $this->priceFormatter->format($price);
        $this->product['regular_price_amount'] = $regular_price;
        $this->product['regular_price'] = $this->priceFormatter->format($regular_price);

        if ($product['reduction'] < $product['price_without_reduction']) {
            $this->product['discount_to_display'] = $this->product['discount_amount'];
        } else {
            $this->product['discount_to_display'] = $this->product['regular_price'];
        }

        if (isset($product['unit_price']) && $product['unit_price']) {
            $this->product['unit_price'] = $this->priceFormatter->format($product['unit_price']);
            $this->product['unit_price_full'] = $this->priceFormatter->format($product['unit_price'])
                . ' ' . $product['unity'];
        } else {
            $this->product['unit_price'] = $this->product['unit_price_full'] = '';
        }
    }

    private function fillImages(
        array $product,
        Language $language
    ) {
        // Get all product images, including potential cover
        // getAllProductImages() method does not exists in 1.7.6.x version
        $productImages = $this->getAllProductImages(
            $product,
            $language
        );

        // Get filtered product images matching the specified id_product_attribute
        if (Tools::getValue('with_all_images')) {
            $this->product['images'] = $this->filterImagesForCombination($productImages, $product['id_product_attribute']);

            // Get default image for selected combination (used for product page, cart details, ...)
            $this->product['default_image'] = reset($this->product['images']);
            foreach ($this->product['images'] as $image) {
                // If one of the image is a cover it is used as such
                if (isset($image['cover']) && null !== $image['cover']) {
                    $this->product['default_image'] = $image;

                    break;
                }
            }
        }else{
            $images = $this->filterImagesForCombination($productImages, $product['id_product_attribute']);

            // Get default image for selected combination (used for product page, cart details, ...)
            $tmp = reset($images);
            $this->product['default_image'] = $tmp['bySize'][Tools::getValue('image_size', "home_default")];
            foreach ($images as $image) {
                // If one of the image is a cover it is used as such
                if (isset($image['cover']) && null !== $image['cover']) {
                    $this->product['default_image'] = $image['bySize'][Tools::getValue('image_size', "home_default")];

                    break;
                }
            }
        }

        // Get generic product image, used for product listing
        if (isset($product['cover_image_id'])) {
            // First try to find cover in product images
            foreach ($productImages as $productImage) {
                if ($productImage['id_image'] == $product['cover_image_id']) {
                    if (Tools::getValue('with_all_images')) {
                        $this->product['cover'] = $productImage;
                    }else{
                        $this->product['cover'] = $productImage['bySize'][Tools::getValue('image_size', "home_default")];
                    }
                    break;
                }
            }

            // If the cover is not associated to the product images it is fetched manually
            if (!isset($this->product['cover'])) {
                $coverImage = $this->imageRetriever->getImage(new Product($product['id_product'], false, $language->getId()), $product['cover_image_id']);
                $this->product['cover'] = array_merge($coverImage, [
                    'legend' => $coverImage['legend'],
                ]);
            }
        }

        // If no cover fallback on default image
        if (!isset($this->product['cover'])) {
            $this->product['cover'] = $this->product['default_image'];
        }
    }

    /**
     * @param ProductPresentationSettings $settings
     * @param array $product
     * @param Language $language
     */
    public function addQuantityInformation(
        ProductPresentationSettings $settings,
        array $product,
        Language $language
    ) {
        $show_price = $this->shouldShowPrice($settings, $product);
        $show_availability = $show_price && $settings->stock_management_enabled;
        $this->product['show_availability'] = $show_availability;
        $product['quantity_wanted'] = $this->getQuantityWanted();

        if (isset($product['available_date']) && '0000-00-00' == $product['available_date']) {
            $product['available_date'] = null;
        }

        if ($show_availability) {
            if ($product['quantity'] - $product['quantity_wanted'] >= 0) {
                $this->product['availability_date'] = $product['available_date'];

                if ($product['quantity'] < $settings->lastRemainingItems) {
                    $this->applyLastItemsInStockDisplayRule();
                } else {
                    $this->product['availability_message'] = $product['available_now'] ? $product['available_now']
                        : Configuration::get('PS_LABEL_IN_STOCK_PRODUCTS', $language->id);
                    $this->product['availability'] = 'available';
                }
            } elseif ($product['allow_oosp']) {
                $this->product['availability_message'] = $product['available_later'] ? $product['available_later']
                    : Configuration::get('PS_LABEL_OOS_PRODUCTS_BOA', $language->id);
                $this->product['availability_date'] = $product['available_date'];
                $this->product['availability'] = 'available';
            } elseif ($product['quantity_wanted'] > 0 && $product['quantity'] > 0) {
                $this->product['availability_message'] = $this->translator->trans(
                    'There are not enough products in stock',
                    [],
                    'Shop.Notifications.Error'
                );
                $this->product['availability'] = 'unavailable';
                $this->product['availability_date'] = null;
            } elseif (!empty($product['quantity_all_versions']) && $product['quantity_all_versions'] > 0) {
                $this->product['availability_message'] = $this->translator->trans(
                    'Product available with different options',
                    [],
                    'Shop.Theme.Catalog'
                );
                $this->product['availability_date'] = $product['available_date'];
                $this->product['availability'] = 'unavailable';
            } else {
                $this->product['availability_message'] =
                    Configuration::get('PS_LABEL_OOS_PRODUCTS_BOD', $language->id);
                $this->product['availability_date'] = $product['available_date'];
                $this->product['availability'] = 'unavailable';
            }
        } else {
            $this->product['availability_message'] = null;
            $this->product['availability_date'] = null;
            $this->product['availability'] = null;
        }
    }

    /**
     * @param array $images
     * @param int $productAttributeId
     *
     * @return array
     */
    private function filterImagesForCombination(array $images, int $productAttributeId)
    {
        $filteredImages = [];

        foreach ($images as $image) {
            if (in_array($productAttributeId, $image['associatedVariants'])) {
                $filteredImages[] = $image;
            }
        }

        return (0 === count($filteredImages)) ? $images : $filteredImages;
    }

    /**
     * Prices should be shown for products with active "Show price" option
     * and customer groups with active "Show price" option.
     *
     * @param ProductPresentationSettings $settings
     * @param array $product
     *
     * @return bool
     */
    private function shouldShowPrice(
        ProductPresentationSettings $settings,
        array $product
    ) {
        return $settings->shouldShowPrice() && (bool)$product['show_price'];
    }

    /**
     * @return int Quantity of product requested by the customer
     */
    private function getQuantityWanted()
    {
        return (int)Tools::getValue('quantity_wanted', 1);
    }

    /**
     * Override availability message.
     */
    protected function applyLastItemsInStockDisplayRule()
    {
        $this->product['availability_message'] = $this->translator->trans(
            'Last items in stock',
            [],
            'Shop.Theme.Catalog'
        );
        $this->product['availability'] = 'last_remaining_items';
    }

    /**
     * @return array
     */
    public function getProduct(): array
    {
        return $this->product;
    }

    /**
     * @param array $product
     * @param Language $language
     *
     * @return array
     */
    public function getAllProductImages(array $product, Language $language)
    {
        $productInstance = new Product(
            $product['id_product'],
            false,
            $language->id
        );

        $images = $productInstance->getImages($language->id);

        if (empty($images)) {
            return [];
        }

        $combinationImages = $productInstance->getCombinationImages($language->id);
        if (!$combinationImages) {
            $combinationImages = [];
        }
        $imageToCombinations = [];

        foreach ($combinationImages as $imgs) {
            foreach ($imgs as $img) {
                $imageToCombinations[$img['id_image']][] = $img['id_product_attribute'];
            }
        }

        $images = array_map(function (array $image) use (
            $productInstance,
            $imageToCombinations
        ) {
            $image = array_merge($this->getImage(
                $productInstance,
                $image['id_image']
            ), $image);

            if (isset($imageToCombinations[$image['id_image']])) {
                $image['associatedVariants'] = $imageToCombinations[$image['id_image']];
            } else {
                $image['associatedVariants'] = [];
            }

            return $image;
        }, $images);

        return $images;
    }

    /**
     * @param $object
     * @param int $id_image
     *
     * @return array|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getImage($object, $id_image)
    {
        if (!$id_image) {
            return null;
        }

        if (get_class($object) === 'Product') {
            $type = 'products';
            $getImageURL = 'getImageLink';
            $root = _PS_PROD_IMG_DIR_;
            $imageFolderPath = implode(DIRECTORY_SEPARATOR, [
                rtrim($root, DIRECTORY_SEPARATOR),
                rtrim(Image::getImgFolderStatic($id_image), DIRECTORY_SEPARATOR),
            ]);
        } elseif (get_class($object) === 'Store') {
            $type = 'stores';
            $getImageURL = 'getStoreImageLink';
            $root = _PS_STORE_IMG_DIR_;
            $imageFolderPath = rtrim($root, DIRECTORY_SEPARATOR);
        } else {
            $type = 'categories';
            $getImageURL = 'getCatImageLink';
            $root = _PS_CAT_IMG_DIR_;
            $imageFolderPath = rtrim($root, DIRECTORY_SEPARATOR);
        }

        $urls = [];
        $image_types = ImageType::getImagesTypes($type, true);

        $extPath = $imageFolderPath . DIRECTORY_SEPARATOR . 'fileType';
        $ext = @file_get_contents($extPath) ?: 'jpg';

        $mainImagePath = implode(DIRECTORY_SEPARATOR, [
            $imageFolderPath,
            $id_image . '.' . $ext,
        ]);

        foreach ($image_types as $image_type) {
            $resizedImagePath = implode(DIRECTORY_SEPARATOR, [
                $imageFolderPath,
                $id_image . '-' . $image_type['name'] . '.' . $ext,
            ]);

            if (!file_exists($resizedImagePath)) {
                ImageManager::resize(
                    $mainImagePath,
                    $resizedImagePath,
                    (int) $image_type['width'],
                    (int) $image_type['height']
                );
            }

            $url = $this->link->$getImageURL(
                isset($object->link_rewrite) ? $object->link_rewrite : $object->name,
                $id_image,
                $image_type['name']
            );

            $urls[$image_type['name']] = [
                'url' => $url,
                'width' => (int) $image_type['width'],
                'height' => (int) $image_type['height'],
            ];
        }

        uasort($urls, function (array $a, array $b) {
            return $a['width'] * $a['height'] > $b['width'] * $b['height'] ? 1 : -1;
        });

        $keys = array_keys($urls);

        $small = $urls[$keys[0]];
        $large = end($urls);
        $medium = $urls[$keys[ceil((count($keys) - 1) / 2)]];

        return [
            'bySize' => $urls,
            'small' => $small,
            'medium' => $medium,
            'large' => $large,
            'legend' => isset($object->meta_title) ? $object->meta_title : $object->name,
            'id_image' => $id_image,
        ];
    }

}
