<?php
if (!defined('_PS_VERSION_')) { exit; }

use PrestaShop\PrestaShop\Adapter\ObjectPresenter;
use Currency;

class RESTUtilsLegacy
{
    public static function getCurrencies($context){
        $current_currency = null;
        $serializer = new ObjectPresenter();
        $currencies = array_map(
            function ($currency) use ($serializer, &$current_currency, $context) {
                $currencyArray = $serializer->present($currency);

                // serializer doesn't see 'sign' because it is not a regular
                // ObjectModel field.
                $currencyArray['sign'] = $currency->sign;

                $url = $context->link->getLanguageLink($context->language->id);

                $parsedUrl = parse_url($url);
                $urlParams = [];
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $urlParams);
                }
                $newParams = array_merge(
                    $urlParams,
                    [
                        'SubmitCurrency' => 1,
                        'id_currency' => $currency->id,
                    ]
                );
                $newUrl = sprintf('%s://%s%s%s?%s',
                    $parsedUrl['scheme'],
                    $parsedUrl['host'],
                    isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '',
                    $parsedUrl['path'],
                    http_build_query($newParams)
                );

                $currencyArray['url'] = $newUrl;

                if ($currency->id === $context->currency->id) {
                    $currencyArray['current'] = true;
                    $current_currency = $currencyArray;
                } else {
                    $currencyArray['current'] = false;
                }

                return $currencyArray;
            },
            Currency::getCurrencies(true, true)
        );

        return [
            'currencies' => $currencies,
            'current_currency' => $current_currency,
        ];
    }
}