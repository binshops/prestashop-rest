<div align="center">
<img src="https://www.binshops.com/assets/img/logo-medium.png?v=1.2"/>
</div>

# PrestaShop REST API Module
Easily expose REST API endpoints for your Prestashop website. No configuration needed, just install and use it. 

Powerful PrestaShop REST API for Headless Commerce. Build high-performance web and mobile applications using Next.js, Nuxt.js, React.js, Vue.js, Angular, Svelte, Flutter, React Native, Node.js, and other modern frontend technologies. Scale your PrestaShop store with an API-first approach.

**Read more about the REST API:**
- [PrestaShop REST API Documentation](https://www.binshops.com/docs/ecommerce-api/prestashop-rest-api.html)
- [Demo Download](https://www.binshops.com/prestashop-api)

## [Officially Supported Version v6](https://addons.prestashop.com/en/website-performance/52062-rest-api-pro-for-front-applications-integrations.html)

Compatible with PrestaShop 9.x, Attribute-based API routing support PHP8 and Symfony, API caching and many admin APIs and full front APIs and our support. 

New! Home page builder API added to the version 6.

Official version is available on:

[PrestaShop Addons](https://addons.prestashop.com/en/website-performance/52062-rest-api-pro-for-front-applications-integrations.html)

## [Free Version v2.7](https://www.binshops.com/prestashop-api)
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

### Download Demo
[Demo version](https://www.binshops.com/prestashop-api)

### Your API Endpoint
After installation access your API endpoints at: http://yourdomain.tld/rest.

### Why we need this API module? Is not Webservice API enough?
You can get more info about this module: https://www.binshops.com/prestashop-api

### Documentation
- You can access full documentation for REST endpoints on Postman publisher:
https://documenter.getpostman.com/view/1491681/TzkyP1UC
- Read more about the API: [REST API Doc](https://www.binshops.com/docs/ecommerce-api/prestashop-rest-api.html)

### How to write your API?
Attributr-based API routing available in v6.
```php
    #[Route(
        '/new-products',
        name: 'binshops_rest_new_products',
        methods: ['GET']
    )]
    public function showNewProducts(): JsonResponse
    {
      ..//
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
