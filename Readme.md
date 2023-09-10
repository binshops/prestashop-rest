<div align="center">
<img src="https://www.binshops.com/assets/img/logo-medium.png?v=1.2"/>
</div>

# PrestaShop REST API Module
Easily expose REST API endpoints for your Prestashop website. No configuration needed, just install and use it. 

## [Official Supported Version v5](https://addons.prestashop.com/en/website-performance/52062-rest-api-pro-version-with-fast-api-caching.html)
New! Annotation-based API routing support added in version 5, July 2023.

## [Free Version v2.5](https://www.binshops.com/prestashop-api)
For demo and testing, not recommended for production.

## The products that use this REST API
<table>
<tr>
<td align="center">
<a href="https://www.binshops.com/prestashop-pwa" target="_blank">  <img src="https://www.binshops.com/assets/img/vue-storefront2.jpg" alt="PrestaShop PWA" />PrestaShop PWA</a>
</td>
<td align="center">
<a href="https://www.binshops.com/prestashop-mobile-application" target="_blank">
  <img src="https://www.binshops.com/assets/img/ps-mobile-app2.jpg" alt="PrestaShop Mobile Application" />
PrestaShop Mobile App
</a>
</td>
</tr>
</table>

# [Headless Commerce](https://www.binshops.com/blog/why-headless-commerce)
This module helps you to build Headless applications based on PrestaShop platform. You can read [this article](https://www.binshops.com/blog/why-headless-commerce) to know about Headless PrestaShop and Headless Commerce, why we need it and why it matters.

### Demo Link
https://rest.binshops.com/rest/bootstrap

### Your API Endpoint
After installation access your API endpoints at: http://yourdomain.tld/rest.

### Why we need this API module? Is not Webservice API enough?
You can get more info about this module: https://www.binshops.com/prestashop-api

### Documentation
You can access full documentation for REST endpoints on Postman publisher:
https://documenter.getpostman.com/view/1491681/TzkyP1UC

### How to write your API?
Annotation-based API routing added in v5.
```php
/**
* @Route("/rest/get-products", name=”products”)
*/
public function getProducts()
{
// ...
}
```

### Required Modules
These native modules, which are already included in PrestaShop out of the box, are required to work with some endpoints.

- ps_mainmenu (Native Ps Menu module)
- ps_featuredproducts (Native Ps Featured Products module)
- ps_facetedsearch (Native Ps Faceted Search module)
- productcomments (Native Ps Product Comments module)
- ps_banner (Native Ps Banner module)
- ps_imageslider (Native Ps Image slider module)
- ps_wirepayment (Native Ps Bankwire module)
- ps_checkpayment (Native Ps Pay by Check module)
- blockwishlist (Native Ps Wishlist module)

If you need custom APIs or you want to have APIs for a third-party module, you can send your request for custom API implementation on Binshops website - [Request Form](https://www.binshops.com/prestashop-api#request-custom-api)
