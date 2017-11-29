<?php
/**
 * 2014-2017 Retargeting BIZ SRL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@retargeting.biz so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2017 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
 
if (!defined('_PS_VERSION_')) {
   exit;
}

include(dirname(__FILE__) . '/lib/Client.php');

class RetargetingTracker extends Module
{

      protected $js_state = 0;
      protected $eligible = 0;
      protected $filterable = 1;
      protected static $products = array();

      public function __construct()
      {
          $this->name = 'retargetingtracker';
          $this->tab = 'analytics_stats';
          $this->version = '2.0.0';
          $this->author = 'Retargeting.Biz Team';
          $this->module_key = '07f632866f76537ce3f8f01eedad4f00';
          $this->need_instance = 0;
          $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
          $this->bootstrap = true;
          parent::__construct();
          $this->displayName = $this->l('Retargeting Tracker');
          $this->description = $this->l('Retargeting is a marketing automation tool that boosts the conversion rate and sales of your online store.');
          $this->confirmUninstall = $this->l('Are you sure you want to uninstall Retargeting Tracker? You will lose all the data related to this module.');
          if (!Configuration::get('ra_apikey') || Configuration::get('ra_apikey') == '') {
              $this->warning = $this->l('No Tracking API Key provided.');
          }
      }
      public function install()
      {
          if (Shop::isFeatureActive()) {
              Shop::setContext(Shop::CONTEXT_ALL);
          }
          if (_PS_VERSION_ >= '1.7.0.0') {
              return parent::install() &&
              Configuration::updateValue('ra_apikey', '') &&
              Configuration::updateValue('ra_token', '') &&
              Configuration::updateValue('ra_productFeedUrl', '') &&
              Configuration::updateValue('ra_discountApiUrl', '') &&
              Configuration::updateValue('ra_opt_visitHelpPage', '') &&
              Configuration::updateValue('ra_qs_addToCart', '') &&
              Configuration::updateValue('ra_qs_variation', '') &&
              Configuration::updateValue('ra_qs_addToWishlist', '') &&
              Configuration::updateValue('ra_qs_productImages', '.page-content .images-container') &&
              Configuration::updateValue('ra_qs_review', '') &&
              Configuration::updateValue('ra_qs_price', '') &&
              Configuration::updateValue('ra_qs_oldPrice', '') &&
              Configuration::updateValue('ra_checkout_url', '') &&
              Configuration::updateValue('ra_init', 'false') &&
              $this->registerHook('displayHome') &&
              $this->registerHook('displayHeader') &&
              $this->registerHook('displayFooter') &&
              $this->registerHook('actionCartSave') &&
              $this->registerHook('displayOrderConfirmation') &&
              $this->registerHook('actionAuthentication') &&
              $this->registerHook('actionCustomerAccountAdd') &&
              $this->registerHook('displayFooterProduct');
          } else {
              return parent::install() &&
              Configuration::updateValue('ra_apikey', '') &&
              Configuration::updateValue('ra_token', '') &&
              Configuration::updateValue('ra_productFeedUrl', '') &&
              Configuration::updateValue('ra_discountApiUrl', '') &&
              Configuration::updateValue('ra_opt_visitHelpPage', '') &&
              Configuration::updateValue('ra_qs_addToCart', '') &&
              Configuration::updateValue('ra_qs_variation', '') &&
              Configuration::updateValue('ra_qs_addToWishlist', '') &&
              Configuration::updateValue('ra_qs_productImages', '') &&
              Configuration::updateValue('ra_qs_review', '') &&
              Configuration::updateValue('ra_qs_price', '') &&
              Configuration::updateValue('ra_qs_oldPrice', '') &&
              Configuration::updateValue('ra_checkout_url', '') &&
              Configuration::updateValue('ra_init', 'false') &&
              $this->registerHook('displayHome') &&
              $this->registerHook('displayHeader') &&
              $this->registerHook('displayFooter') &&
              $this->registerHook('displayOrderConfirmation') &&
              $this->registerHook('actionCartSave') &&
              $this->registerHook('actionCarrierProcess') &&
              $this->registerHook('authentication') &&
              $this->registerHook('createAccount');
          }
      }
      public function uninstall()
      {
          return Configuration::deleteByName('ra_apikey') &&
          Configuration::deleteByName('ra_token') &&
          Configuration::deleteByName('ra_productFeedUrl') &&
          Configuration::deleteByName('ra_discountApiUrl') &&
          Configuration::deleteByName('ra_opt_visitHelpPage') &&
          Configuration::deleteByName('ra_qs_addToCart', '') &&
          Configuration::deleteByName('ra_qs_variation', '') &&
          Configuration::deleteByName('ra_qs_addToWishlist', '') &&
          Configuration::deleteByName('ra_qs_productImages', '') &&
          Configuration::deleteByName('ra_qs_review', '') &&
          Configuration::deleteByName('ra_qs_price', '') &&
          Configuration::deleteByName('ra_qs_oldPrice', '') &&
          Configuration::deleteByName('ra_checkout_url', '') &&
          Configuration::deleteByName('ra_init') &&
          parent::uninstall();
      }
      public function getContent()
      {
          $output = null;
          if (Tools::isSubmit('submitDisableInit')) {
              if ((int)Tools::getValue('ra_init')) {
                  Configuration::updateValue('ra_init', 'true');
              }
          } else if (Tools::isSubmit('submitBasicSettings')) {
              $ra_apikey = (string)Tools::getValue('ra_apikey');
              $ra_token = (string)Tools::getValue('ra_token');
              Configuration::updateValue('ra_apikey', $ra_apikey);
              Configuration::updateValue('ra_token', $ra_token);
              $output .= $this->displayConfirmation($this->l('Settings updated! Enjoy!'));
          } else if (Tools::isSubmit('submitTrackerOptions')) {
              $ra_opt_visitHelpPages = array();
              $ra_addToCart = (string)Tools::getValue('ra_qs_addToCart');
              $ra_variation = (string)Tools::getValue('ra_qs_variation');
              $ra_addToWishlist = (string)Tools::getValue('ra_qs_addToWishlist');
              $ra_productImages = (string)Tools::getValue('ra_qs_productImages');
              $ra_review = (string)Tools::getValue('ra_qs_review');
              $ra_price = (string)Tools::getValue('ra_qs_price');
              $ra_oldPrice = (string)Tools::getValue('ra_qs_oldPrice');
              $ra_checkout_url = (string)Tools::getValue('ra_checkout_url');
              foreach (CMS::listCMS() as $cmsPage) {
                  $option = (string)Tools::getValue('ra_opt_visitHelpPage_' . $cmsPage['id_cms']);
                  if ($option == 'on') {
                      $ra_opt_visitHelpPages[] = $cmsPage['id_cms'];
                  }
              }
              Configuration::updateValue('ra_opt_visitHelpPage', implode('|', $ra_opt_visitHelpPages));
              Configuration::updateValue('ra_qs_addToCart', $ra_addToCart);
              Configuration::updateValue('ra_qs_variation', $ra_variation);
              Configuration::updateValue('ra_qs_addToWishlist', $ra_addToWishlist);
              Configuration::updateValue('ra_qs_productImages', $ra_productImages);
              Configuration::updateValue('ra_qs_review', $ra_review);
              Configuration::updateValue('ra_qs_price', $ra_price);
              Configuration::updateValue('ra_qs_oldPrice', $ra_oldPrice);
              Configuration::updateValue('ra_checkout_url', $ra_checkout_url);
              $output .= $this->displayConfirmation($this->l('Settings updated! Enjoy!'));
          }

          if (Configuration::get('ra_init') == 'false') {
              return $this->displayForm();
          }
      }

      public function displayForm()
      {
          // Get default language
          $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
          // Init Fields form array
          $fields_form = array();
          $fields_form[0]['form'] = array(
              'legend' => array(
                  'title' => $this->l('Required Settings'),
              ),
              'input' => array(
                  array(
                      'type' => 'text',
                      'label' => $this->l('Tracking API Key'),
                      'name' => 'ra_apikey',
                      'desc' => 'You can find your unique and secure Tracking API Key in your <a href="https://retargeting.biz/admin/module/settings/docs-and-api" target="_blank" rel="noopener noreferrer">Retargeting</a> account.'
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('REST API Key'),
                      'name' => 'ra_token',
                      'desc' => 'You can find your unique and secure REST API Key in your <a href="https://retargeting.biz/admin/module/settings/docs-and-api" target="_blank" rel="noopener noreferrer">Retargeting</a> account.'
                  )
              ),
              'submit' => array(
                  'name' => 'submitBasicSettings',
                  'title' => $this->l('Save')
              )
          );
          $fields_form[1]['form'] = array('legend' => array('title' => $this->l('Specific URLs'),), 'input' => array(array('type' => 'text', 'label' => $this->l('Product Feed URL'), 'name' => 'ra_productFeedUrl', 'desc' => '', 'disabled' => 'disabled'), array('type' => 'text', 'label' => $this->l('Discounts API URL'), 'name' => 'ra_discountApiUrl', 'desc' => '', 'disabled' => 'disabled'),),);
          $fields_form[2]['form'] = array(
              'legend' => array(
                  'title' => $this->l('Optional Settings'),
                  ),
              'input' => array(
                  array(
                      'type' => 'checkbox',
                      'label' => $this->l('Help Pages'),
                      'name' => 'ra_opt_visitHelpPage',
                      'desc' => $this->l('Choose the pages on which the "visitHelpPage" event should fire.'),
                      'values' => array(
                          'query' => CMS::listCMS(),
                          'id' => 'id_cms', 'name' => 'meta_title'
                      )
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('Add To Cart Button'
                      ),
                      'name' => 'ra_qs_addToCart',
                      'desc' => '[Experimental] Query selector for the button used to add a product to cart.'
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('Product Variants Buttons'),
                      'name' => 'ra_qs_variation',
                      'desc' => '[Experimental] Query selector for the product options used to change the preferences of the product.'
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('Add To Wishlist Button'),
                      'name' => 'ra_qs_addToWishlist',
                      'desc' => '[Experimental] Query selector for the button used to add a product to wishlist.'
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('Product Images'),
                      'name' => 'ra_qs_productImages',
                      'desc' => '[Experimental] Query selector for the main product image on a product page.'
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('Submit Review Button'),
                      'name' => 'ra_qs_review',
                      'desc' => '[Experimental] Query selector for the button used to submit a comment/review for a product.'
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('Price'),
                      'name' => 'ra_qs_price',
                      'desc' => '[Experimental] Query selector for the main product price on a product page.'
                  ),
              array(
                  'type' => 'text',
                  'label' => $this->l('Old Price'),
                  'name' => 'ra_qs_oldPrice',
                  'desc' => '[Experimental] Query selector for the main product price without discount on a product page.'
              ),
              array(
                  'type'  =>  'text',
                  'label' =>  $this->l('Checkout URL'),
                  'name'  =>  'ra_checkout_url',
                  'desc'  =>  'Your website checkout/cart page. The URL must include HTTP (not HTTPS) (i.e: <a href="http://yourdomain.com/cart" target="_blank" rel="noopener noreferrer">http://yourdomain.com/cart</a>)'
              ),
          ),
              'submit' => array(
                  'name' => 'submitTrackerOptions',
                  'title' => $this->l('Save')
              )
          );
          $helper = new HelperForm();
          // Module, Token and currentIndex
          $helper->module = $this;
          $helper->name_controller = $this->name;
          $helper->token = Tools::getAdminTokenLite('AdminModules');
          $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
          // Language
          $helper->default_form_language = $default_lang;
          $helper->allow_employee_form_lang = $default_lang;
          // Title and toolbar
          $helper->title = $this->displayName;
          $helper->show_toolbar = true;
          $helper->toolbar_scroll = true;
          $helper->submit_action = 'submit' . $this->name;
          $helper->toolbar_btn = array('save' => array('desc' => $this->l('Save'), 'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),), 'back' => array('href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'), 'desc' => $this->l('Back to list')));
          // Load current value
          $helper->fields_value['ra_apikey'] = Configuration::get('ra_apikey');
          $helper->fields_value['ra_token'] = Configuration::get('ra_token');
          $helper->fields_value['ra_productFeedUrl'] = Configuration::get('ra_productFeedUrl') != '' ? Configuration::get('ra_productFeedUrl') : '/modules/retargetingtracker/productFeed.php';
          $helper->fields_value['ra_discountApiUrl'] = Configuration::get('ra_discountApiUrl') != '' ? Configuration::get('ra_discountApiUrl') : '/modules/retargetingtracker/discountsApi.php?params';
          $options_visitHelpPages = explode('|', Configuration::get('ra_opt_visitHelpPage'));
          foreach ($options_visitHelpPages as $option) {
              $helper->fields_value['ra_opt_visitHelpPage_' . $option] = true;
          }
          $helper->fields_value['ra_qs_addToCart'] = Configuration::get('ra_qs_addToCart');
          $helper->fields_value['ra_qs_variation'] = Configuration::get('ra_qs_variation');
          $helper->fields_value['ra_qs_addToWishlist'] = Configuration::get('ra_qs_addToWishlist');
          $helper->fields_value['ra_qs_productImages'] = Configuration::get('ra_qs_productImages');
          $helper->fields_value['ra_qs_review'] = Configuration::get('ra_qs_review');
          $helper->fields_value['ra_qs_price'] = Configuration::get('ra_qs_price');
          $helper->fields_value['ra_qs_oldPrice'] = Configuration::get('ra_qs_oldPrice');
          $helper->fields_value['ra_checkout_url'] = Configuration::get('ra_checkout_url');
          return $helper->generateForm($fields_form);
      }
      
      protected function _getRetargetingTrackerTag()
      {
          $raApiKey = Configuration::get('ra_apikey');
          
          if ($raApiKey && $raApiKey != '') {
              $jsEmbedd = '<script>// Retargeting 49.54.x 1.0.6
                var _ra = _ra || {};
                ra_key = "' . $raApiKey . '";
                (function(){
                var ra = document.createElement("script"); ra.type ="text/javascript"; ra.async = true; ra.src = ("https:" ==
                document.location.protocol ? "https://" : "http://") + "tracking.retargeting.biz/v3/rajs/" + ra_key + ".js";
                var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ra,s);})();
                </script>';
          } else {
              $jsEmbedd = 'console.info("Retargeting Tracker: Tracking API Key is unset.");';
          }
          
          return $jsEmbedd;
      }
      
      public function hookdisplayHeader($params)
      {
        $raApiKey = $raApiKey = Configuration::get('ra_apikey');
        if ($raApiKey && $raApiKey != '') {
            $this->context->controller->addJs($this->_path.'views/js/retargetingtracker.js');
            
            return $this->_getRetargetingTrackerTag();
        }
      }

      public function hookdisplayFooterProduct($params)
      {
          $controllerName = Tools::getValue('controller');
          if ($controllerName == 'product') 
          {
              // Add product view
              $raProduct = $this->wrapProduct((array)$params['product'], null, 0, true);
              $js = 'raModule.sendProduct('.json_encode($raProduct, JSON_PRETTY_PRINT).');' . PHP_EOL;
              $js .= 'raModule.clickImage("'. Configuration::get('ra_qs_productImages') .'");';
              $this->js_state = 1;
              return $this->_runJs($js);
          }
      }

      protected function _runJs($js_code)
      {
          if (Configuration::get('ra_apikey')) {
              $runjs_code = '';
              if (!empty($js_code)) {
                  $runjs_code .= '
              				<script type="text/javascript">
                                  document.addEventListener(\'DOMContentLoaded\', function() {
              						var raModule = retargetingTracker;
                                      '.$js_code.'
              					});
              				</script>';
              }
              return $runjs_code;
          }
      }
      

      public function wrapProduct($product, $extras, $index = 0, $full = false)
      {
          $raProduct = '';
          
          $variation = null;
          if (isset($product['attributes_small'])) {
              $variation = $product['attributes_small'];
          } elseif (isset($extras['attributes_small'])) {
              $variation = $extras['attributes_small'];
          }
          
          $productQty = 1;
          if (isset($extras['qty'])) {
              $productQty = $extras['qty'];
          } elseif (isset($product['cart_quantity'])) {
              $productQty = $product['cart_quantity'];
          }
          
          $productId = 0;
          if (!empty($product['id_product'])) {
              $productId = $product['id_product'];
          } elseif (!empty($product['id'])) {
              $productId = $product['id'];
          }
          
          if (isset($product['name'])) {
              $productName = htmlspecialchars($product['name']);
          } else {
              $productName = '';
          }

          if (isset($product['link'])) {
              $productLink = $product['link'];
          } else {
              $productLink = '';
          }

          if (isset($product['price_tax_exc'])) {
              $productTaxExc = $product['price_tax_exc'];
          } else {
              $productTaxExc = 0;
          }

          if (isset($product['category_name'])) {
              $productCategoryName = $product['category_name'];
          } else {
              $productCategoryName = 'Home';
          }

          $raProduct = array(
              'id' => $productId,
              'name' => $productName,
              'url' => $productLink,
              'img' => isset($product['images'][0]['bySize']['medium_default']['url']) ? $product['images'][0]['bySize']['medium_default']['url'] : '',
              'price' => $product['price_without_reduction'],
              'promo' => $productTaxExc == $product['price_without_reduction'] ? 0 : $productTaxExc,
              'brand' => isset($product['manufacturer_name']) ? Tools::str2url($product['manufacturer_name']) : false,
              'category' => array(array(
                                "id" => isset($product['id_category_default']) ? $product['id_category_default'] : '9999',
                                "name" => Tools::str2url($productCategoryName),
                                "parent" => false
                            )),
              'inventory' => array(
                            'variations' => Tools::str2url($variation),
                            'stock' => $productQty
                          ),
              'quantity' => null
          );
          return $raProduct;
      }
      
      public function wrapCategory($category)
      {
          $raCategory = '';
          
          $raCategory = array(
              'id' => $category['id'],
              'name' => $category['name'],
              'parent' => false,
              'breadcrumb' => array()
          );
          
          return $raCategory;
      }
      
      public function wrapBrand($brand)
      {
          $raBrand = '';
          
          $raBrand = array(
              "id" => $brand['id'],
              "name" => $brand['name']
          );
          
          return $raBrand;
      }
      
      public function wrapCartUrl($cartUrl)
      {
          $raCartUrl = '';
          
          $raCartUrl = array(
              "url" => $cartUrl
          );
          
          return $raCartUrl;
      }
      
      public function wrapCheckOutIds()
      {
          $cart_instance = $this->context->cart;
          $cartProducts = $cart_instance->getProducts();
          
          $cartProductsArray = array();
          
          foreach ($cartProducts as $product) {
              $cartProductsArray[] = $product['id_product'];
          }
          
          $raCheckoutIds = $cartProductsArray;
          
          return $raCheckoutIds;
      }

      public function hookactionCartSave()
      {
          if (!isset($this->context->cart)) {
              return;
          }
          
          if (!Tools::getIsset('id_product')) {
              return;
          }
          
          $cart = array(
              'controller' => Tools::getValue('controller'),
              'addAction' => Tools::getValue('add') ? 'add' : '',
              'removeAction' => Tools::getValue('delete') ? 'delete' : '',
              'extraAction' => Tools::getValue('op'),
              'qty' => (int)Tools::getValue('qty', 1)
          );
          
          $cartProducts = $this->context->cart->getProducts();
          if (isset($cartProducts) && count($cartProducts)) {
              foreach ($cartProducts as $cartProduct) {
                  if ($cartProduct['id_product'] == Tools::getValue('id_product')) {
                      $addProduct = $cartProduct;
                  }
              }
          }
          
          if ($cart['removeAction'] == 'delete') {
              $addProductObject = new Product((int)Tools::getValue('id_product'), true, (int)Configuration::get('PS_LANG_DEFAULT'));
              if (Validate::isLoadedObject($addProductObject)) {
                  $addProduct['name'] = $addProductObject->name;
                  $addProduct['id_product'] = Tools::getValue('id_product');
                  $addProduct['out_of_stock'] = $addProductObject->out_of_stock;
                  $addProduct = Product::getProductProperties((int)Configuration::get('PS_LANG_DEFAULT'), $addProduct);
                  
              }
          }
          
          if (isset($addProduct) && !in_array((int)Tools::getValue('id_product'), self::$products)) {
              self::$products[] = (int)Tools::getValue('id_product');
              $raProducts = $this->wrapProduct($addProduct, $cart, 0, true);
              
              if (array_key_exists('id_product_attribute', $raProducts) && $raProducts['id_product_attribute'] != '' && $raProducts['id_product_attribute'] != 0) {
                  $idProduct = $raProducts['id_product_attribute'];
              } else {
                  $idProduct = Tools::getValue('id_product');
              }
              
              if (isset($this->context->cookie->ra_cart)) {
                  $raCart = json_decode($this->context->cookie->ra_cart, true);
              } else {
                  $raCart = array();
              }
              
              if ($cart['removeAction'] == 'delete') {
                  $raProducts['quantity'] = -1;
              } elseif ($cart['extraAction'] == 'down') {
                  if (array_key_exists($idProduct, $raCart)) {
                      $raProducts['quantity'] = $raCart[$idProduct]['quantity'] - $cart['qty'];
                  } else {
                      $raProducts['quantity'] = $cart['qty'] * -1;
                  }
              } elseif (Tools::getValue('step') <= 0) {
                  if (array_key_exists($idProduct, $raCart)) {
                      $raProducts['quantity'] = $raCart[$idProduct]['quantity'] + $cart['qty'];
                  }
              }
              
              $raCart[$idProduct] = $raProducts;
              $this->context->cookie->ra_cart = json_encode($raCart);
          }
      }
      
      public function hookdisplayFooter($params)
      {
          $raScripts = '';
          $this->js_state = 0;
          
          if (isset($this->context->cookie->ra_cart)) {
              $this->filterable = 0;
              
              $raCarts = json_decode($this->context->cookie->ra_cart, true);
              if (is_array($raCarts)) {
                  foreach ($raCarts as $raCart) {
                      if ($raCart['quantity'] < 0) {
                          $raCart['quantity'] = abs($raCart['quantity']);
                          $raCartParams = array(
                              'id' => $raCart['id'],
                              'qty' => $raCart['quantity']
                          );
                          $raScripts .= 'raModule.removeFromCart('.json_encode($raCartParams, JSON_PRETTY_PRINT).');' . PHP_EOL;
                      }
                  }
              }
              unset($this->context->cookie->ra_cart);
          }
          
          
          $controllerName = Tools::getValue('controller');
          
          // Add category view
          
          if ($controllerName == 'category') {
              $categoryInstance = $this->context->controller->getCategory();
              $raCategory = $this->wrapCategory((array)$categoryInstance);
              $raScripts .= "raModule.sendCategory(".json_encode($raCategory).");";
              $this->js_state = 1;
          }
          
          // Add brand view
          
          if ($controllerName == 'manufacturer') {
              $brandInstance = new Manufacturer((int)Tools::getValue('id_manufacturer'), $this->context->language->id);
              $raBrand = $this->wrapBrand((array)$brandInstance);
              $raScripts .= "raModule.sendBrand(".json_encode($raBrand).");";
              $this->js_state = 1;
          }
          
          // Add setCartUrl && checkoutIds view
          $customUrl = Configuration::get('ra_checkout_url');
          
          if ($controllerName == 'order' || $controllerName == 'orderopc' || $controllerName == 'module-supercheckout-supercheckout' || $controllerName == 'cart' && $customUrl == '') {
              $currentUrl = strtok(_PS_BASE_URL_.$_SERVER['REQUEST_URI'], '?');
              $raCartUrl = $this->wrapCartUrl((array)$currentUrl);
              $raCheckoutIds = $this->wrapCheckOutIds();
              $raScripts .= "raModule.setCartUrl(".json_encode($raCartUrl).");";
              $raScripts .= "raModule.checkoutIds(".json_encode($raCheckoutIds).");";
              $this->js_state = 1;
          } elseif ($customUrl != '') {
              $raCartUrl = $this->wrapCartUrl((array)$customUrl);
              $raCheckOutIds = $this->wrapCheckOutIds();
              $raScripts .= "raModule.setCartUrl(".json_encode($raCartUrl).");";
              $raScripts .= "raModule.checkoutIds(".json_encode($raCheckOutIds).");";
              $this->js_state = 1;
          }
          
          // Add setEmail view
          
          $customer = $this->context->customer;
          if ($customer->email !== null) {
              $birthday = $this->context->customer->birthday;
              
              if ($birthday == 'null' || $birthday == '0000-00-00') {
                  $setEmailBirthday = '';
              } else {
                  $setEmailBirthday = date("d-m-Y", strtotime($birthday));  
              }
              
              $raEmail = '';
              
              $raEmail = array(
                  "email" => $customer->email,
                  "name" => $customer->firstname . ' ' . $customer->lastname,
                  "birthday" => $setEmailBirthday
              ); 
              
              $raScripts .= "raModule.setEmail(".json_encode($raEmail).");";
          } 
          
          // Add visitHelpPage view
          
          $customVisitHelpPage = Configuration::get('ra_opt_visitHelpPage');

          if (!empty($customVisitHelpPage) && $controllerName == 'cms') {
              $raHelp = '';
              
              $raHelp = array(
                  "visit" => true
              );
              
              $raScripts .= "raModule.visitHelpPage(".json_encode($raHelp).");";
          }

          return $this->_runJs($raScripts);
      }
      
      public function wrapProducts($products, $extras = array(), $full = false)
      {
          $resultProducts = array();
          if (!is_array($products)) {
              return;
          }
          
          $currency = new Currency($this->context->currency->id);
          $useTax = (Product::getTaxCalculationMethod((int)$this->context->customer->id) != PS_TAX_EXC);
          
          if (count($products) > 20) {
              $full = false;
          } else {
              $full = true;
          }
          
          foreach ($products as $index => $product) {
              if ($product instanceof Product) {
                  $product = (array)$product;
              }
              
              if (!isset($product['price'])) {
                  $product['price'] = (float)Tools::displayPrice(Product::getPriceStatic((int)$product['id_product'], $useTax), $currency);
              }
              $resultProducts[] = $this->wrapProduct($product, $extras, $index, $full);
          }
          
          return $resultProducts;
      }
      
      public function hookdisplayOrderConfirmation($params)
      {
          $order = $params['order'];
          $discounts = $order->getCartRules();
          $customer = new Customer((int)$order->id_customer);
          $address = new Address((int)$order->id_address_delivery);
          $birthday = $this->context->customer->birthday;
          $homePhone = $address->phone;
          $mobilePhone = $address->phone_mobile;
          
          if ($birthday == 'null' || $birthday == '0000-00-00') {
              $formattedBirthday = '';
          } else {
              $formattedBirthday = date("d-m-Y", strtotime($birthday));  
          }
          
          if (Validate::isLoadedObject($order) && Validate::isLoadedObject($customer) && $order->getCurrentState() != (int)Configuration::get('PS_OS_ERROR')) {
              $paramsAPI = array('orderInfo' => null, 'orderProducts' => array());
              
              $orderProducts = array();
              $cartInstance = new Cart($order->id_cart);
              
              foreach ($cartInstance->getProducts() as $orderProduct) {
                  $orderProductAttributes = (!empty($orderProduct['attributes_small']) ? str_replace(', ', '-', $orderProduct['attributes_small']) : '');
                  $orderProductInstance = new Product((int)$orderProduct['id_product']);
                  $orderProducts[] = array(
                      "id" => $orderProduct['id_product'],
                      "quantity" => $orderProduct['quantity'],
                      "price" => $orderProductInstance->getPrice(true, null, 2),
                      "variation_code" => $orderProductAttributes
                  );
                  
                  $paramsAPI['orderProducts'][] = array(
                      "id" => $orderProduct['id_product'],
                      "quantity" => $orderProduct['quantity'],
                      "price" => $orderProductInstance->getPrice(true, null, 2),
                      "variation_code" => $orderProductAttributes
                  );
              }
              
              $discountCode = '';
              if (count($discounts) > 0) {
                  $discountCode = array();
                  foreach ($discounts as $discount) {
                      $cartRule = new CartRule((int)$discount['id_cart_rule']);
                      $discountCode[] = $cartRule->code;
                      var_dump($cartRule, 'carpati');
                  }
              }
              
              $raSaveOrder = array(
                  "order_no" => $order->id,
                  "lastname" => $address->lastname,
                  "firstname" => $address->firstname,
                  "email" => $customer->email,
                  "phone" => ($mobilePhone !== '' ? $mobilePhone : $homePhone),
                  "state" => (isset($address->id_state) ? State::getNameById($address->id_state) : ''),
                  "city" => $address->city,
                  "address" => $address->address1,
                  "birthday" => $formattedBirthday,
                  "discount" => $order->total_discounts,
                  "discount_code" => $discountCode,
                  "shipping" => $order->total_shipping,
                  "rebates" => 0,
                  "fees" => 0,
                  "total" => $order->total_paid
              );
              
              $raSaveOrderProducts = $orderProducts;
              
              $raScripts = '';
              
              $raScripts  = "raModule.saveOrder(
                ".json_encode($raSaveOrder).",
                ".json_encode($raSaveOrderProducts)."
              );";
              
              $paramsAPI['orderInfo'] = array(
                  "order_no" => $order->id,
                  "lastname" => $address->lastname,
                  "firstname" => $address->firstname,
                  "email" => $customer->email,
                  "phone" => ($mobilePhone !== '' ? $mobilePhone : $homePhone),
                  "state" => (isset($address->id_state) ? State::getNameById($address->id_state) : ''),
                  "city" => $address->city,
                  "address" => $address->address1,
                  "discount" => $order->total_discounts,
                  "discount_code" => $discountCode,
                  "shipping" => $order->total_shipping,
                  "rebates" => 0,
                  "fees" => 0,
                  "total" => $order->total_paid
              );
              
              $this->_apiOrderSave($paramsAPI);
          }
          return $this->_runJs($raScripts);
      }
      
      private function _apiOrderSave($params)
      {
          $raApiKey = Configuration::get('ra_apikey');
          $raToken  = Configuration::get('ra_token');
          
          if ($raApiKey && $raToken !== '') {
              $client = new Retargeting_REST_API_Client($raToken);
              $client->setResponseFormat("json");
              $client->setDecoding(false);
              
              $response = $client->order->save($params['orderInfo'], $params['orderProducts']);
              
              return $response;
          }
          
          return false;
      }
}