<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Checkout\Model;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart\CartInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Observer\AddToCart;

/**
 * Shopping cart model
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @deprecated 100.1.0
 */
class Cart extends DataObject implements CartInterface
{
    
      
    /**
     * Shopping cart items summary quantity(s)
     *
     * @var int|null
     */
    protected $_summaryQty;
    
    /**
     * List of product ids in shopping cart
     *
     * @var int[]|null
     */
    protected $_productIds;
    
    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;
    
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * @var \Magento\Checkout\Model\ResourceModel\Cart
     */
    protected $_resourceCart;
    
    /**
     * @var Session
     */
    protected $_checkoutSession;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    
    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;
    
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    
    /**
     * @var \Magento\Checkout\Model\Cart\RequestInfoFilterInterface
     */
    private $requestInfoFilter;
    
    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\ResourceModel\Cart $resourceCart
     * @param Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\ResourceModel\Cart $resourceCart,
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        array $data = []
        ) {
            $this->_eventManager = $eventManager;
            $this->_scopeConfig = $scopeConfig;
            $this->_storeManager = $storeManager;
            $this->_resourceCart = $resourceCart;
            $this->_checkoutSession = $checkoutSession;
            $this->_customerSession = $customerSession;
            $this->messageManager = $messageManager;
            $this->stockRegistry = $stockRegistry;
            $this->stockState = $stockState;
            $this->quoteRepository = $quoteRepository;
            parent::__construct($data);
            $this->productRepository = $productRepository;
    }
    
    /**
     * Get shopping cart resource model
     *
     * @return \Magento\Checkout\Model\ResourceModel\Cart
     * @codeCoverageIgnore
     */
    protected function _getResource()
    {
        return $this->_resourceCart;
    }
    
    /**
     * Retrieve checkout session model
     *
     * @return Session
     * @codeCoverageIgnore
     */
    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
    
    /**
     * Retrieve customer session model
     *
     * @return \Magento\Customer\Model\Session
     * @codeCoverageIgnore
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }
    
    /**
     * List of shopping cart items
     *
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection|array
     */
    public function getItems()
    {
        if (!$this->getQuote()->getId()) {
            return [];
        }
        return $this->getQuote()->getItemsCollection();
    }
    
    /**
     * Retrieve array of cart product ids
     *
     * @return array
     */
    public function getQuoteProductIds()
    {
        $products = $this->getData('product_ids');
        if ($products === null) {
            $products = [];
            foreach ($this->getQuote()->getAllItems() as $item) {
                $products[$item->getProductId()] = $item->getProductId();
            }
            $this->setData('product_ids', $products);
        }
        return $products;
    }
    
    /**
     * Get quote object associated with cart. By default it is current customer session quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if (!$this->hasData('quote')) {
            $this->setData('quote', $this->_checkoutSession->getQuote());
        }
        return $this->_getData('quote');
    }
    
    /**
     * Set quote object associated with the cart
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     * @codeCoverageIgnore
     */
    public function setQuote(\Magento\Quote\Model\Quote $quote)
    {
        $this->setData('quote', $quote);
        return $this;
    }
    
    /**
     * Reinitialize cart quote state
     *
     * @return $this
     */
    protected function reinitializeState()
    {
        $quote = $this->getQuote()->setCheckoutMethod('');
        $this->_checkoutSession->setCartWasUpdated(true);
        // TODO: Move this logic to Multishipping module as plug-in.
        // reset for multiple address checkout
        if ($this->_checkoutSession->getCheckoutState() !== Session::CHECKOUT_STATE_BEGIN
            && $this->_checkoutSession->getCheckoutState() !== null) {
                $quote->removeAllAddresses()->removePayment();
                $this->_checkoutSession->resetCheckout();
            }
            return $this;
    }
    
    /**
     * Convert order item to quote item
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param true|null $qtyFlag if is null set product qty like in order
     * @return $this
     */
    public function addOrderItem($orderItem, $qtyFlag = null)
    {
        /* @var $orderItem \Magento\Sales\Model\Order\Item */
        if ($orderItem->getParentItem() === null) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                /**
                 * We need to reload product in this place, because products
                 * with the same id may have different sets of order attributes.
                 */
                $product = $this->productRepository->getById($orderItem->getProductId(), false, $storeId, true);
            } catch (NoSuchEntityException $e) {
                return $this;
            }
            $info = $orderItem->getProductOptionByCode('info_buyRequest');
            $info = new \Magento\Framework\DataObject($info);
            if ($qtyFlag === null) {
                $info->setQty($orderItem->getQtyOrdered());
            } else {
                $info->setQty(1);
            }
            
            $this->addProduct($product, $info);
        }
        return $this;
    }
    
    /**
     * Get product object based on requested product information
     *
     * @param   Product|int|string $productInfo
     * @return  Product
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProduct($productInfo)
    {
        $product = null;
        if ($productInfo instanceof Product) {
            $product = $productInfo;
            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find the product.'));
            }
        } elseif (is_int($productInfo) || is_string($productInfo)) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($productInfo, false, $storeId);
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find the product.'), $e);
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find the product.'));
        }
        $currentWebsiteId = $this->_storeManager->getStore()->getWebsiteId();
        if (!is_array($product->getWebsiteIds()) || !in_array($currentWebsiteId, $product->getWebsiteIds())) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find the product.'));
        }
        return $product;
    }
    
    /**
     * Get request for product add to cart procedure
     *
     * @param   \Magento\Framework\DataObject|int|array $requestInfo
     * @return  \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProductRequest($requestInfo)
    {
        if ($requestInfo instanceof \Magento\Framework\DataObject) {
            $request = $requestInfo;
        } elseif (is_numeric($requestInfo)) {
            $request = new \Magento\Framework\DataObject(['qty' => $requestInfo]);
        } elseif (is_array($requestInfo)) {
            $request = new \Magento\Framework\DataObject($requestInfo);
        }
        /*         elseif (is_null($requestInfo)){
         $request = new \Magento\Framework\DataObject(['qty' => $requestInfo]);
         } */
        else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
                );
        }
        $this->getRequestInfoFilter()->filter($request);
        
        return $request;
    }
    
    /**
     * Add product to shopping cart (quote)
     *
     * @param int|Product $productInfo
     * @param \Magento\Framework\DataObject|int|array $requestInfo
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     *
     *
     *
     */
    

    
    
    
    
    public function addToCart($productName,$slug,$price,$qty,$commercetools_cart_id){
        
  
        $ini_array = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/magento/vendor/magento/module-checkout/properties.ini");
        $projectKey = $ini_array['projectKey'];
        $token = $ini_array['token'];
        $baseURL = $ini_array['baseURL'];
        
        
        /* $token="LfrlvDA4vRoqLlPby5SxVrVAFs22oP1f";
        $baseURL="https://api.sphere.io/";
        $projectKey="openkart-1"; */
        $curr = "USD";
        
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // Get Cart version
        curl_setopt_array($curl, array(
           // CURLOPT_URL => "https://api.sphere.io/openkart-1/carts/".$commercetools_cart_id."",
            CURLOPT_URL => $baseURL.$projectKey."/carts/".$commercetools_cart_id."",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer ".$token,
                "cache-control: no-cache",
                "postman-token: 3be8eb42-8726-a3f0-1c2d-f860c143b50d"
            ),
        ));
        
        $cartResponse = curl_exec($curl);
        $cartErr = curl_error($curl);
        if ($cartErr) {
            echo "cURL Error #:" . $cartErr;
        } else {
            $cartResponse= json_decode($cartResponse);
            $cartVersion=$cartResponse->version;
        }
        
        $desc='{
  "version":'.$cartVersion.',
  "actions": [{
    "action": "addCustomLineItem",
    "name": {
      "en": "'.$productName.'",
      "de": "'.$productName.'"
    },
    "quantity": '.$qty.',
    "money": {
        "currencyCode": "USD",
        "centAmount":'.$price.'
      },
    "slug": "'.$slug.'",
	"taxCategory": {
    	"typeId": "tax-category",
    	"id": "71202ac2-1f18-43e5-a821-08dd0e20a135"
    }
  }]
}';
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseURL.$projectKey."/carts/".$commercetools_cart_id."",
            // CURLOPT_URL => "https://api.sphere.io/openkart-1/carts/".$commercetools_cart_id."",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $desc,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer ".$token,
                "cache-control: no-cache",
                "postman-token: 029b8757-29f8-93a1-dfb9-bb90a7a4767f",
                'Content-Type: application/json'
            ),
        ));
        curl_setopt($curl,CURLOPT_POST, true);
        //  curl_setopt($curl,CURLOPT_VERBOSE,true);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //   echo $response;
        }
    }
    
    
    public function addToAnonymousCart($productName,$slug,$price,$qty){
        
        
        //if anonymous user then create a cart in commercetools having a reference to the sessionID
        
        $ini_array = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/magento/vendor/magento/module-checkout/properties.ini");
        $projectKey = $ini_array['projectKey'];
        $token = $ini_array['token'];
        $baseURL = $ini_array['baseURL'];
        
        
        $anonymousId =$this->getCustomerSession()->getSessionId();
        $cookie_anonymousId = $anonymousId;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if(!isset($_COOKIE[$cookie_anonymousId])) {
            
            $desc='{
            "currency": "USD",
            "anonymousId":"'.$anonymousId.'"
            }';
            curl_setopt_array($curl, array(
                CURLOPT_URL => $baseURL.$projectKey."/carts/",
             //   CURLOPT_URL => "https://api.sphere.io/openkart-1/carts/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $desc,
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer ".$token,
                    "cache-control: no-cache",
                    "postman-token: a24ef8a7-22b1-3709-0e56-ff2917916085",
                    'Content-Type: application/json'
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
                /* $cookie_AnonymousCartID = "AnonymousCartID111";
                 $cookie_AnonymousCartID_value ="11111111111";
                 setcookie($cookie_AnonymousCartID, $cookie_AnonymousCartID_value, time() + (86400 * 30), "/"); // 86400 = 1 day */
            }
            else {
                //echo $response;
                $response= json_decode($response);
                $cartVersion=$response->version;
                $cookie_cartId=$response->id;
                setcookie($cookie_anonymousId, $cookie_cartId, time() + (86400 * 30), "/"); // 86400 = 1 day
                setcookie("anonymousID", $cookie_anonymousId, time() + (86400 * 30), "/");
            }
            
            
        }
        //   $cookie_cartId=$_COOKIE[$cookie_anonymousId];
        
        //     $this->addToCart($productName, $slug, $price, $qty,  $_COOKIE[$cookie_anonymousId]);
        if(!isset($_COOKIE[$cookie_anonymousId])) {
            $this->addToCart($productName, $slug, $price, $qty, $cookie_cartId);
        }
        else{
            
            $this->addToCart($productName, $slug, $price, $qty,  $_COOKIE[$cookie_anonymousId]);
        }
        curl_close($curl);
        
    }
    
    
    public function addProduct($productInfo, $requestInfo = null)
    {
        
        $product = $this->_getProduct($productInfo);
        $request = $this->_getProductRequest($requestInfo);
        $productId = $product->getId();
        
        if ($productId) {
            $stockItem = $this->stockRegistry->getStockItem($productId, $product->getStore()->getWebsiteId());
            $minimumQty = $stockItem->getMinSaleQty();
            //If product quantity is not specified in request and there is set minimal qty for it
            if ($minimumQty
                && $minimumQty > 0
                && !$request->getQty()
                ) {
                    $request->setQty($minimumQty);
                }
        }
        
        if ($productId) {
            try {
                $result = $this->getQuote()->addProduct($product, $request);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_checkoutSession->setUseNotice(false);
                $result = $e->getMessage();
            }
            /**
             * String we can get if prepare process has error
             */
            if (is_string($result)) {
                if ($product->hasOptionsValidationFail()) {
                    $redirectUrl = $product->getUrlModel()->getUrl(
                        $product,
                        ['_query' => ['startcustomization' => 1]]
                        );
                } else {
                    $redirectUrl = $product->getProductUrl();
                }
                $this->_checkoutSession->setRedirectUrl($redirectUrl);
                if ($this->_checkoutSession->getUseNotice() === null) {
                    $this->_checkoutSession->setUseNotice(true);
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($result));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('The product does not exist.'));
        }
        
        $this->_eventManager->dispatch(
            'checkout_cart_product_add_after',
            ['quote_item' => $result, 'product' => $product]
            );
        $this->_checkoutSession->setLastAddedProductId($productId);
        
        // Here custom Code---------------->>>>
        // Set product details for adding to commercetools cart
        $productName=  $product->getName();
        $slug = $product->getSku();
        $price = $product->getPrice();
        $price = round($price);
        
        if( !(array_key_exists( 'qty',$requestInfo))){
            $requestInfo=array("qty"=>1);
        }
        $qty = $requestInfo['qty'];
        
        //   $this->addToCart($productName,$slug,$price,$qty);
        
        
        
        
        //Get Email of Current Customer Logged in
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customerEmail = $customerSession->getCustomer()->getEmail();
        
        
        
        
        if($customerEmail === NULL){
            $this->addToAnonymousCart($productName, $slug, $price, $qty);
        }
        else{
            //Fetch Commercetools CartId of the corresponding email
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
            $commercetools_cart_id_result = $connection->fetchAll("SELECT commercetools_cart_id FROM `integrate_commercetools` WHERE cust_email='".$customerEmail."'");
            $commercetools_cart_id=$commercetools_cart_id_result[0]['commercetools_cart_id'];
            $this->addToCart($productName, $slug, $price, $qty, $commercetools_cart_id);
        }
        
        
        
        // custom code ends here
        
    }
    
    /**
     * Adding products to cart by ids
     *
     * @param  int[] $productIds
     * @return $this
     */
    public function addProductsByIds($productIds)
    {
        $allAvailable = true;
        $allAdded = true;
        
        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                $productId = (int)$productId;
                if (!$productId) {
                    continue;
                }
                $product = $this->_getProduct($productId);
                if ($product->getId() && $product->isVisibleInCatalog()) {
                    try {
                        $this->getQuote()->addProduct($product);
                    } catch (\Exception $e) {
                        $allAdded = false;
                    }
                } else {
                    $allAvailable = false;
                }
            }
            
            if (!$allAvailable) {
                $this->messageManager->addError(__("We don't have some of the products you want."));
            }
            if (!$allAdded) {
                $this->messageManager->addError(__("We don't have as many of some products as you want."));
            }
        }
        return $this;
    }
    
    /**
     * Returns suggested quantities for items.
     * Can be used to automatically fix user entered quantities before updating cart
     * so that cart contains valid qty values
     *
     * The $data is an array of ($quoteItemId => (item info array with 'qty' key), ...)
     *
     * @param   array $data
     * @return  array
     */
    public function suggestItemsQty($data)
    {
        foreach ($data as $itemId => $itemInfo) {
            if (!isset($itemInfo['qty'])) {
                continue;
            }
            $qty = (float)$itemInfo['qty'];
            if ($qty <= 0) {
                continue;
            }
            
            $quoteItem = $this->getQuote()->getItemById($itemId);
            if (!$quoteItem) {
                continue;
            }
            
            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }
            
            $data[$itemId]['before_suggest_qty'] = $qty;
            $data[$itemId]['qty'] = $this->stockState->suggestQty(
                $product->getId(),
                $qty,
                $product->getStore()->getWebsiteId()
                );
        }
        return $data;
    }
    
    /**
     * Update cart items information
     *
     * @param  array $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function updateItems($data)
    {
        $infoDataObject = new \Magento\Framework\DataObject($data);
        $this->_eventManager->dispatch(
            'checkout_cart_update_items_before',
            ['cart' => $this, 'info' => $infoDataObject]
            );
        
        $qtyRecalculatedFlag = false;
        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }
            
            if (!empty($itemInfo['remove']) || isset($itemInfo['qty']) && $itemInfo['qty'] == '0') {
                $this->removeItem($itemId);
                continue;
            }
            
            $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;
            if ($qty > 0) {
                $item->setQty($qty);
                
                if ($item->getHasError()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($item->getMessage()));
                }
                
                if (isset($itemInfo['before_suggest_qty']) && $itemInfo['before_suggest_qty'] != $qty) {
                    $qtyRecalculatedFlag = true;
                    $this->messageManager->addNotice(
                        __('Quantity was recalculated from %1 to %2', $itemInfo['before_suggest_qty'], $qty),
                        'quote_item' . $item->getId()
                        );
                }
            }
        }
        
        if ($qtyRecalculatedFlag) {
            $this->messageManager->addNotice(
                __('We adjusted product quantities to fit the required increments.')
                );
        }
        
        $this->_eventManager->dispatch(
            'checkout_cart_update_items_after',
            ['cart' => $this, 'info' => $infoDataObject]
            );
        
        return $this;
    }
    
    /**
     * Remove item from cart
     *
     * @param  int $itemId
     * @return $this
     * @codeCoverageIgnore
     */
	 
	 
	     // my remove method
    public function removeCommercetoolsItem( $itemId){
		
		
		
		  
       $ini_array = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/magento/vendor/magento/module-checkout/properties.ini");
        $projectKey = $ini_array['projectKey'];
        $token = $ini_array['token'];
        $baseURL = $ini_array['baseURL'];
        
        //Get Email of Current Customer Logged in
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customerEmail = $customerSession->getCustomer()->getEmail();
        
        
        if($customerEmail === NULL){
           $anonymousID=$_COOKIE["anonymousID"];
            $commercetools_cart_id=$_COOKIE[$anonymousID];
           //   $anonymousID=$_COOKIE["anonymousID"];
          //  $commercetools_cart_id=$_COOKIE["thisCart"];
            
            
        }else{
            //Fetch Commercetools CartId of of the corresponding email
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
            $commercetools_cart_id_result = $connection->fetchAll("SELECT commercetools_cart_id FROM `integrate_commercetools` WHERE cust_email='".$customerEmail."'");
            $commercetools_cart_id=$commercetools_cart_id_result[0]['commercetools_cart_id'];
        }
        
        
        
        
        
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        
        
        
        
        
        // Get Cart version
        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseURL.$projectKey."/carts/".$commercetools_cart_id."",
            //    CURLOPT_URL => $baseURL.$projectKey."/carts/c9ba6221-b65d-4e41-ae8a-358af51ef720 ",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                // "authorization: Bearer zhztQJDmwBD_bBSMUpgFwsKeMe0ckDh2",
                "authorization: Bearer ".$token,
                "cache-control: no-cache",
                "postman-token: 3be8eb42-8726-a3f0-1c2d-f860c143b50d"
            ),
        ));
        
        $cartResponse = curl_exec($curl);
        $cartErr = curl_error($curl);
        if ($cartErr) {
            echo "cURL Error #:" . $cartErr;
        } else {
            $cartResponse= json_decode($cartResponse);
            $cartVersion=$cartResponse->version;
        }
        $slug=$this->getQuote()->getItemById($itemId)->getSku();
        foreach ($cartResponse->customLineItems as $val):{
            if($val->slug == $slug){
                $customLineItemId= $val->id;
                break;
            }
            
        }
        
        endforeach;
        
        $desc='{
                    "version": '.$cartVersion.',
                    "actions": [{
                    "action": "removeCustomLineItem",
                    "customLineItemId": "'.$customLineItemId.'"
                  }]
                }';
        
        curl_setopt_array($curl, array(
            CURLOPT_URL =>  $baseURL.$projectKey."/carts/".$commercetools_cart_id."",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $desc,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer ".$token,
                "cache-control: no-cache",
                "postman-token: 029b8757-29f8-93a1-dfb9-bb90a7a4767f",
                'Content-Type: application/json'
            ),
        ));
        curl_setopt($curl,CURLOPT_POST, true);
        //  curl_setopt($curl,CURLOPT_VERBOSE,true);
        $responseAfterRemove = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //   echo $responseAfterRemove;
        }
		
		
		
		
		
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
       public function removeItem($itemId)
    {
        
      
        
        
        $this->removeCommercetoolsItem($itemId);
        
        $this->getQuote()->removeItem($itemId);
        return $this;
    }
    
    /**
     * Save cart
     *
     * @return $this
     */
    public function save()
    {
        $this->_eventManager->dispatch('checkout_cart_save_before', ['cart' => $this]);
        
        $this->getQuote()->getBillingAddress();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();
        $this->quoteRepository->save($this->getQuote());
        $this->_checkoutSession->setQuoteId($this->getQuote()->getId());
        /**
         * Cart save usually called after changes with cart items.
         */
        $this->_eventManager->dispatch('checkout_cart_save_after', ['cart' => $this]);
        $this->reinitializeState();
        return $this;
    }
    
    /**
     * Save cart (implement interface method)
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function saveQuote()
    {
        $this->save();
    }
    
    /**
     * Mark all quote items as deleted (empty shopping cart)
     *
     * @return $this
     * @codeCoverageIgnore
     */
    public function truncate()
    {
        $this->getQuote()->removeAllItems();
        return $this;
    }
    
    /**
     * @return int[]
     */
    public function getProductIds()
    {
        if (null === $this->_productIds) {
            $this->_productIds = [];
            if ($this->getSummaryQty() > 0) {
                foreach ($this->getQuote()->getAllItems() as $item) {
                    $this->_productIds[] = $item->getProductId();
                }
            }
            $this->_productIds = array_unique($this->_productIds);
        }
        return $this->_productIds;
    }
    
    /**
     * Get shopping cart items summary (includes config settings)
     *
     * @return int|float
     */
    public function getSummaryQty()
    {
        $quoteId = $this->_checkoutSession->getQuoteId();
        
        //If there is no quote id in session trying to load quote
        //and get new quote id. This is done for cases when quote was created
        //not by customer (from backend for example).
        if (!$quoteId && $this->_customerSession->isLoggedIn()) {
            $this->_checkoutSession->getQuote();
            $quoteId = $this->_checkoutSession->getQuoteId();
        }
        
        if ($quoteId && $this->_summaryQty === null) {
            $useQty = $this->_scopeConfig->getValue(
                'checkout/cart_link/use_qty',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            $this->_summaryQty = $useQty ? $this->getItemsQty() : $this->getItemsCount();
        }
        return $this->_summaryQty;
    }
    
    /**
     * Get shopping cart items count
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getItemsCount()
    {
        return $this->getQuote()->getItemsCount() * 1;
    }
    
    /**
     * Get shopping cart summary qty
     *
     * @return int|float
     * @codeCoverageIgnore
     */
    public function getItemsQty()
    {
        return $this->getQuote()->getItemsQty() * 1;
    }
    
    /**
     * Update item in shopping cart (quote)
     * $requestInfo - either qty (int) or buyRequest in form of array or \Magento\Framework\DataObject
     * $updatingParams - information on how to perform update, passed to Quote->updateItem() method
     *
     * @param int $itemId
     * @param int|array|\Magento\Framework\DataObject $requestInfo
     * @param null|array|\Magento\Framework\DataObject $updatingParams
     * @return \Magento\Quote\Model\Quote\Item|string
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @see \Magento\Quote\Model\Quote::updateItem()
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function updateItem($itemId, $requestInfo = null, $updatingParams = null)
    {
        try {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                throw new \Magento\Framework\Exception\LocalizedException(__('This quote item does not exist.'));
            }
            $productId = $item->getProduct()->getId();
            $product = $this->_getProduct($productId);
            $request = $this->_getProductRequest($requestInfo);
            
            if ($productId) {
                $stockItem = $this->stockRegistry->getStockItem($productId, $product->getStore()->getWebsiteId());
                $minimumQty = $stockItem->getMinSaleQty();
                // If product was not found in cart and there is set minimal qty for it
                if ($minimumQty
                    && $minimumQty > 0
                    && !$request->getQty()
                    && !$this->getQuote()->hasProductId($productId)
                    ) {
                        $request->setQty($minimumQty);
                    }
            }
            
            $result = $this->getQuote()->updateItem($itemId, $request, $updatingParams);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_checkoutSession->setUseNotice(false);
            $result = $e->getMessage();
        }
        
        /**
         * We can get string if updating process had some errors
         */
        if (is_string($result)) {
            if ($this->_checkoutSession->getUseNotice() === null) {
                $this->_checkoutSession->setUseNotice(true);
            }
            throw new \Magento\Framework\Exception\LocalizedException(__($result));
        }
        
        $this->_eventManager->dispatch(
            'checkout_cart_product_update_after',
            ['quote_item' => $result, 'product' => $product]
            );
        $this->_checkoutSession->setLastAddedProductId($productId);
		
		
		
		
		
		
		
		
		 //my custom code
        
        // my edit code
        $this->removeCommercetoolsItem($itemId);
        
        
       
        //Get Email of Current Customer Logged in
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customerEmail = $customerSession->getCustomer()->getEmail();
        
      
        
        
        $productName=$item->getName();
        $slug=$result->getSku();
        $price=$item->getPrice();
        $price = round($price);
        
      
        
        $qty = $requestInfo['qty'];
        
       if($customerEmail === NULL){
           $anonymousID=$_COOKIE["anonymousID"];
           $commercetools_cart_id=$_COOKIE[$anonymousID];
	   }   
        
        else{
        //Fetch Commercetools CartId of the corresponding email
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $commercetools_cart_id_result = $connection->fetchAll("SELECT commercetools_cart_id FROM `integrate_commercetools` WHERE cust_email='".$customerEmail."'");
        $commercetools_cart_id=$commercetools_cart_id_result[0]['commercetools_cart_id'];
       
        }
        
         $this->addToCart($productName, $slug, $price, $qty, $commercetools_cart_id);
        
        
        
        
        
        //custom code ends here
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
        return $result;
    }
    
    /**
     * Getter for RequestInfoFilter
     *
     * @deprecated 100.1.2
     * @return \Magento\Checkout\Model\Cart\RequestInfoFilterInterface
     */
    private function getRequestInfoFilter()
    {
        if ($this->requestInfoFilter === null) {
            $this->requestInfoFilter = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Checkout\Model\Cart\RequestInfoFilterInterface::class);
        }
        return $this->requestInfoFilter;
    }
}
