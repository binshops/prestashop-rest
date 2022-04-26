<?php

class APIRoutes
{
    public static final function getRoutes(): array
    {
        return [
            'module-binshopsrest-login' => [
                'rule' => 'rest/login',
                'keywords' => [],
                'controller' => 'login',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-register' => [
                'rule' => 'rest/register',
                'keywords' => [],
                'controller' => 'register',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-logout' => [
                'rule' => 'rest/logout',
                'keywords' => [],
                'controller' => 'logout',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-accountinfo' => [
                'rule' => 'rest/accountInfo',
                'keywords' => [],
                'controller' => 'accountinfo',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-accountedit' => [
                'rule' => 'rest/accountedit',
                'keywords' => [],
                'controller' => 'accountedit',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-bootstrap' => [
                'rule' => 'rest/bootstrap',
                'keywords' => [],
                'controller' => 'bootstrap',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-lightbootstrap' => [
                'rule' => 'rest/lightbootstrap',
                'keywords' => [],
                'controller' => 'lightbootstrap',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-productdetail' => [
                'rule' => 'rest/productdetail',
                'keywords' => [],
                'controller' => 'productdetail',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-orderhistory' => [
                'rule' => 'rest/orderhistory',
                'keywords' => [],
                'controller' => 'orderhistory',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-cart' => [
                'rule' => 'rest/cart',
                'keywords' => [],
                'controller' => 'cart',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-categoryproducts' => [
                'rule' => 'rest/categoryProducts',
                'keywords' => [],
                'controller' => 'categoryproducts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-productsearch' => [
                'rule' => 'rest/productSearch',
                'keywords' => [],
                'controller' => 'productsearch',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-featuredproducts' => [
                'rule' => 'rest/featuredproducts',
                'keywords' => [],
                'controller' => 'featuredproducts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-address' => [
                'rule' => 'rest/address',
                'keywords' => [],
                'controller' => 'address',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-alladdresses' => [
                'rule' => 'rest/alladdresses',
                'keywords' => [],
                'controller' => 'alladdresses',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-addressform' => [
                'rule' => 'rest/addressform',
                'keywords' => [],
                'controller' => 'addressform',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-carriers' => [
                'rule' => 'rest/carriers',
                'keywords' => [],
                'controller' => 'carriers',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-setaddresscheckout' => [
                'rule' => 'rest/setaddresscheckout',
                'keywords' => [],
                'controller' => 'setaddresscheckout',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-setcarriercheckout' => [
                'rule' => 'rest/setcarriercheckout',
                'keywords' => [],
                'controller' => 'setcarriercheckout',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-paymentoptions' => [
                'rule' => 'rest/paymentoptions',
                'keywords' => [],
                'controller' => 'paymentoptions',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-resetpasswordemail' => [
                'rule' => 'rest/resetpasswordemail',
                'keywords' => [],
                'controller' => 'resetpasswordemail',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-resetpasswordcheck' => [
                'rule' => 'rest/resetpasswordcheck',
                'keywords' => [],
                'controller' => 'resetpasswordcheck',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-resetpasswordenter' => [
                'rule' => 'rest/resetpasswordenter',
                'keywords' => [],
                'controller' => 'resetpasswordenter',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-resetpasswordbyemail' => [
                'rule' => 'rest/resetpasswordbyemail',
                'keywords' => [],
                'controller' => 'resetpasswordbyemail',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-listcomments' => [
                'rule' => 'rest/listcomments',
                'keywords' => [],
                'controller' => 'listcomments',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-postcomment' => [
                'rule' => 'rest/postcomment',
                'keywords' => [],
                'controller' => 'postcomment',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-hello' => [
                'rule' => 'rest',
                'keywords' => [],
                'controller' => 'hello',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-ps_checkpayment' => [
                'rule' => 'rest/ps_checkpayment',
                'keywords' => [],
                'controller' => 'ps_checkpayment',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-ps_wirepayment' => [
                'rule' => 'rest/ps_wirepayment',
                'keywords' => [],
                'controller' => 'ps_wirepayment',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-wishlist' => [
                'rule' => 'rest/wishlist',
                'keywords' => [],
                'controller' => 'wishlist',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-emailsubscription' => [
                'rule' => 'rest/emailsubscription',
                'keywords' => [],
                'controller' => 'emailsubscription',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
        ];
    }
}
