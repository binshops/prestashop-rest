<?php
/**
 * BINSHOPS | Best In Shops
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

if (!defined('_PS_VERSION_')) { exit; }

use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\Decimal\Operation\Rounding;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(
        ProductPresentationSettings $settings,
        array $product,
        Language $language,
        PriceFormatter $priceFormatter,
        ImageRetriever $imageRetriever,
        TranslatorInterface $translator
    ) {
        $this->settings = $settings;
        $this->product = $product;
        $this->language = $language;
        $this->priceFormatter = $priceFormatter;
        $this->imageRetriever = $imageRetriever;
        $this->translator = $translator;

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

    private function addPriceInformation(
        ProductPresentationSettings $settings,
        array $product
    ): void {
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
            $this->product['has_discount'] = 0 != $product['reduction'];
            $this->product['discount_type'] =
                $product['specific_prices']['reduction_type'];

            $absoluteReduction = new DecimalNumber(
                $product['specific_prices']['reduction']
            );
            $absoluteReduction = $absoluteReduction->times(
                new DecimalNumber('100')
            );
            $negativeReduction = $absoluteReduction->toNegative();
            $presAbsoluteReduction = $absoluteReduction->round(
                2,
                Rounding::ROUND_HALF_UP
            );
            $presNegativeReduction = $negativeReduction->round(
                2,
                Rounding::ROUND_HALF_UP
            );

            // TODO: add percent sign according to locale preferences
            $this->product['discount_percentage'] =
                Context::getContext()
                    ->getCurrentLocale()
                    ->formatNumber($presNegativeReduction) . '%';
            $this->product['discount_percentage_absolute'] =
                Context::getContext()
                    ->getCurrentLocale()
                    ->formatNumber($presAbsoluteReduction) . '%';
            if ($settings->include_taxes) {
                $regular_price = $product['price_without_reduction'];
            } else {
                $regular_price =
                    $product['price_without_reduction_without_tax'];
            }
            // We must calculate the real amount of discount.
            // see @https://github.com/PrestaShop/PrestaShop/issues/32924
            $product['reduction'] = $regular_price - $price;
            $this->product['discount_amount'] = $this->priceFormatter->format(
                $product['reduction']
            );
            $this->product['discount_amount_to_display'] =
                '-' . $this->priceFormatter->format($product['reduction']);
        }

        $this->product['price_amount'] = $price;
        $this->product['price'] = $this->priceFormatter->format($price);
        $this->product['regular_price_amount'] = $regular_price;
        $this->product['regular_price'] = $this->priceFormatter->format(
            $regular_price
        );

        if ($product['reduction'] < $product['price_without_reduction']) {
            $this->product['discount_to_display'] =
                $this->product['discount_amount'];
        } else {
            $this->product['discount_to_display'] =
                $this->product['regular_price'];
        }

        /*
         * Now, let's format unit price display.
         *
         * If we have a unit ("per 100 g") to display after the unit price AND we have the value, we can proceed with formatting.
         * We are intentionally not using empty here, because unit price can be also zero.
         *
         * If not, we will pass empty strings.
         */
        if (
            !empty($this->product['unity'])
            && isset(
                $this->product['unit_price_tax_excluded'],
                $this->product['unit_price_tax_included']
            )
        ) {
            /*
             * We use the tax included or tax excluded price, depending on presentation settings.
             * We have the prices calculated from the Product::computeUnitPriceRatio, that is called before it gets passed here.
             *
             * The prices are already adapted to account for specific prices and combinations.
             */
            $this->product['unit_price'] = $this->priceFormatter->format(
                $settings->include_taxes
                    ? $this->product['unit_price_tax_included']
                    : $this->product['unit_price_tax_excluded']
            );

            // And add the full version with the unit after the price
            $this->product['unit_price_full'] =
                $this->product['unit_price'] . ' ' . $product['unity'];
        } else {
            $this->product['unit_price'] = '';
            $this->product['unit_price_full'] = '';
        }

        // Assign no-pack prices in case of products that are packs
        if ($this->product['pack']) {
            $rawNoPackPrice = Pack::noPackPrice(
                (int) $this->product['id_product']
            );
            $this->product['nopackprice'] = $rawNoPackPrice;
            $this->product[
            'nopackprice_to_display'
            ] = $this->priceFormatter->format($rawNoPackPrice);
        } else {
            $this->product['nopackprice'] = null;
            $this->product['nopackprice_to_display'] = null;
        }
    }

    private function fillImages(
        array $product,
        Language $language
    ) {
        // Get all product images, including potential cover
        $productImages = $this->imageRetriever->getAllProductImages(
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
            if (key_exists(Tools::getValue('image_size', "home_default"), $tmp['bySize'])){
                $this->product['default_image'] = $tmp['bySize'][Tools::getValue('image_size', "home_default")];
            }else{
                $this->product['default_image'] = $tmp['bySize']['home_default'];
            }

            foreach ($images as $image) {
                // If one of the image is a cover it is used as such
                if (isset($image['cover']) && null !== $image['cover']) {
                    if (key_exists(Tools::getValue('image_size', "home_default"), $image['bySize'])){
                        $this->product['default_image'] = $image['bySize'][Tools::getValue('image_size', "home_default")];
                    }else{
                        $this->product['default_image'] = $image['bySize']['home_default'];
                    }


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
}
