<div align="center">
<img src="https://www.binshops.com/assets/img/logo-medium.png?v=1.2"/>
</div>

# PrestaShop REST API Module
Easily expose REST API endpoints for your Prestashop website. No configuration needed, just install and use it. 

## [Download the Latest version v2.4.3](https://www.binshops.com/prestashop-api)

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

### Demo Link
https://rest.binshops.com/rest/bootstrap

### Your API Endpoint
After installation access your API endpoints at: http://yourdomain.tld/rest.

### Why we need this API module? Is not Webservice API enough?
You can get more info about this module: https://www.binshops.com/prestashop-rest-module

### Documentation
You can access full documentation for REST endpoints on Postman publisher:
https://documenter.getpostman.com/view/1491681/TzkyP1UC

### Backward Compatibility
If your shop is running on 1.7.6.x version of PrestaShop, please check the 1.7.6.x branch. The stable version supports latest Ps version. 

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

You can request your custom API implementation on Binshops panel - [Your Assistant - Create a Ticket](https://www.binshops.com/panel) 

### Change Log
- ### 2.4.x Latest Version
- 2.4.3
  - Code refactor
  - Messages Translation [#21](https://github.com/binshops/prestashop-rest/issues/21)
  - Currencies format in cart [#17](https://github.com/binshops/prestashop-rest/issues/17)
- 2.4.2
  - Added attributes array in cart
- 2.4.1
  - Added email subscription 
- 2.4.0
  - Added Wishlist endpoints
  - Added Zipcode validation
  - Added currencies to bootstrap/lightbootstrap API
  - Added languages to bootstrap/lightbootstrap API
  - Added the logo url to bootstrap/lightbootstrap API
  - Added float price to combinations
  - Fix support for countries without state in address creation
  - Improved error handling in profile edit
- 2.3.0
  - Adds two payment options 
  - Cart Management refactoring
  - Returns cart items on cart update  
  - Returns newly created address
  - Returns user info on user login
  - Removes unnecessary fields from registration  
  - Product images refactor
  - Adds attribute groups to product details  
  - Check permission on address delete and order details
- 2.2.6 changed cart management API
- 2.2.5 product comment api - APIRoutes class
- 2.2.4 cleaning customer info - makes gender optional - adds user info to login api 
- 2.2.3 adds groups to product details - featured products api refactor 
- 2.2.2. adds light bootstrap endpoint
- 2.2.1 some response cleaning - ability to load menu item images
- 2.2.0 improves bootstrap api - adds id and slug to menu items 
- 2.x The latest stable version which includes all endpoints.
