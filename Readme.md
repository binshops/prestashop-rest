<div align="center">
<img src="https://www.binshops.com/assets/img/logo-medium.png?v=1.2"/>
</div>

# PrestaShop REST API Module
Easily expose REST API endpoints for your Prestashop website. No configuration needed, just install and use it. 

## The products that use this REST API
<table>
<tr>
<td align="center">
<a href="https://www.binshops.com/prestashop-pwa" target="_blank">  <img src="https://www.binshops.com/assets/img/prestashop-pwa-github.jpg" alt="PrestaShop PWA" />PrestaShop PWA</a>
</td>
<td align="center">
<a href="https://www.binshops.com/prestashop-mobile-application" target="_blank">
  <img src="https://www.binshops.com/assets/img/ps-mobile-app-github.jpg" alt="PrestaShop Mobile Application" />
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
These native modules are required to work with this REST API.

- ps_mainmenu (Native Ps Menu module)
- ps_featuredproducts (Native Ps Featured Products module)
- ps_facetedsearch (Native Ps Faceted Search module)
- productcomments (Native Ps Product Comments module)
- ps_banner (Native Ps Banner module)
- ps_imageslider (Native Ps Image slider module)
- ps_wirepayment (Native Ps Bankwire module)
- ps_checkpayment (Native Ps Pay by Check module)

Request your custom API implementation on Binshops panel - [Your Assistant - Create a Ticket](https://www.binshops.com/panel) 

### Change Log
- 2.2.6 changed cart management API
- 2.2.5 product comment api - APIRoutes class
- 2.2.4 cleaning customer info - makes gender optional - adds user info to login api 
- 2.2.3 adds groups to product details - featured products api refactor 
- 2.2.2. adds light bootstrap endpoint
- 2.2.1 some response cleaning - ability to load menu item images
- 2.2.0 improves bootstrap api - adds id and slug to menu items 
- 2.x The latest stable version which includes all endpoints.
