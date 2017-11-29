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
 
 /* globals $, _ra, jQuery, prestashop */

var retargetingTracker = {
    add: function(Product, Order, Impression) {
        var Products = {};
        var Orders = {};

        var ProductFieldObject = ['id', 'name', 'url', 'img', 'price', 'promo', 'brand', 'category', 'inventory'];

        if (Product != null) {
            if (Impression && Product.quantity !== undefined) {
                delete Product.quantity;
            }

            for (var productKey in Product) {
                for (var i = 0; i < ProductFieldObject.length; i++) {
                    if (productKey.toLowerCase() == ProductFieldObject[i]) {
                        if (Product[productKey] != null) {
                            Products[productKey.toLowerCase()] = Product[productKey];
                        }
                    }
                }
            }
            
            if (Order != null) {
              for (var orderKey in Order) {
                for (var j = 0; j < OrderFieldObject.length; j++) {
                  if (orderKey.toLowerCase() == OrderFieldObject[j]) {
                    Orders[orderKey.toLowerCase()] = Order[orderKey];
                  }
                }
              }
            }
        }
    },
    sendProduct: function(Product) {
        this.add(Product);

        prestashop.on('updateCart', function(event) {
            
            if (event && event.reason) {
                if (_ra.ready !== undefined) {
                    _ra.addToCart(event.reason.idProduct, 1, false);
                }
            }
        });
        
        _ra.sendProductInfo = {
            "id": Product.id, 
            "name": Product.name,
            "url": Product.url,
            "img": Product.img,
            "price": Product.price.toFixed(2),
            "promo": Product.promo.toFixed(2),
            "brand": Product.brand,
            "category": [{
                "id": Product.category[0].id,
                "name": Product.category[0].name,
                "parent": false
            }],
            "inventory": {
                "variations": false,
                "stock": Product.inventory.stock
            }
        };
        console.info(_ra.sendProductInfo);
        if (_ra.ready !== undefined) {
            _ra.sendProduct(_ra.sendProductInfo);
        }
    },
    
    clickImage: function(Selector) {
        var productId = _ra.sendProductInfo.id;

        window.addEventListener("load", function() {
            document.querySelector(Selector).addEventListener('click', function() {
                if (_ra.ready !== undefined) {
                    _ra.clickImage(productId);
                };
            });
        });
    },
    
    setCartUrl: function(Cart) {
      
      window.addEventListener("load", function() {
          _ra.setCartUrlInfo = {
              "url": Cart.url
          };
          
          if (_ra.ready !== undefined) {
              _ra.setCartUrl(_ra.setCartUrlInfo.url);
          }
      });
    },
    
    checkoutIds: function(productsId) {
        
        window.addEventListener("load", function(){
            _ra.checkoutIdsInfo = [productsId];
            
            if (_ra.ready !== undefined) {
                _ra.checkoutIds(_ra.checkoutIdsInfo);
            }
        });
    },
    
    setEmail: function(User){
        window.addEventListener("load", function() {
            _ra.setEmailInfo = {
                "email": User.email,
                "name": User.name,
                "birthday": User.birthday
            }
            
            if (_ra.ready !== undefined) {
                _ra.setEmail(_ra.setEmailInfo);
            }
        });
    },
    
    visitHelpPage: function(Page) {
        window.addEventListener("load", function() {
            _ra.visitHelpPageInfo = {
                "visit": Page
            }
            
            if (_ra.ready !== undefined) {
                _ra.visitHelpPage();
            }
        });
    },
    
    sendCategory: function(Category) {
        
        _ra.sendCategoryInfo = {
            "id": Category.id,
            "name": Category.name,
            "parent": false,
            "breadcrumb": []
        };
        console.info(_ra.sendCategoryInfo);
        if (_ra.ready !== undefined) {
            _ra.sendCategory(_ra.sendCategoryInfo);
        }
    },
    
    sendBrand: function(Brand) {
        
        _ra.sendBrandInfo = {
            "id": Brand.id,
            "name": Brand.name
        };
        console.info(_ra.sendBrandInfo);
        if (_ra.ready !== undefined) {
            _ra.sendBrand(_ra.sendBrandInfo);
        }
    },
    
    removeFromCart: function(Product) {
        this.add(Product);
        
        window.addEventListener("load", function() {
            if (_ra.ready !== undefined) {
                _ra.removeFromCart(Product.id, Product.qty, false);
            };
        });
    },
    
    saveOrder: function(SaveOrder, SaveOrderProducts) {
        
        window.addEventListener("load", function() {
            _ra.saveOrderInfo = {
                "order_no": SaveOrder.order_no,
                "lastname": SaveOrder.lastname,
                "firstname": SaveOrder.firstname,
                "email": SaveOrder.email,
                "phone": SaveOrder.phone,
                "state": SaveOrder.state,
                "city": SaveOrder.city,
                "address": SaveOrder.address,
                "birthday": SaveOrder.birthday,
                "discount_code": SaveOrder.discount_code,
                "discount": SaveOrder.discount,
                "shipping": SaveOrder.shipping,
                "rebates": SaveOrder.rebates,
                "fees": SaveOrder.fees,
                "total": SaveOrder.total
            };
            
            if (_ra.ready !== undefined) {
                _ra.saveOrder(_ra.saveOrderInfo, SaveOrderProducts);
            }
        });
    }
};