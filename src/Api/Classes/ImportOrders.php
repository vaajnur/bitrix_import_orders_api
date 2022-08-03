<?

namespace Api\Classes;

class ImportOrders{

	public function __construct(){

		$orders_old = \Api\Classes\Orders::getOrders();
		$CACHE = \Api\Classes\Cache::getCache();
		$items_collection_obj = new \Api\Classes\GetItemsCollection;

		foreach ($orders_old as $key => $_orderid) {
		            
			$order_json = file_get_contents('https://ivan-pole.ru/api.php/shop.order.getInfo?access_token=1457399d58081c858c597f18e980f5ea&id='.$_orderid);
			$ws_order_arr = json_decode($order_json, true);

			// если заказ не найден
			if($ws_order_arr == false)
				continue;

			$ext_id = $ws_order_arr['id'];
			$params = array(
				 'select' => ['ID'], 
				'filter' => [
			        '=PROPERTY_VAL.CODE' => 'EXTERNAL_ID',
			        '=PROPERTY_VAL.VALUE' => $ext_id,
				],
			    'runtime' => [
			        new \Bitrix\Main\Entity\ReferenceField(
			            'PROPERTY_VAL',
			            '\Bitrix\sale\Internals\OrderPropsValueTable',
			            ["=this.ID" => "ref.ORDER_ID"],
			            ["join_type"=>"left"]
			        ),
			    ]
			);
			$order = \Bitrix\Sale\Order::getList($params);
			if($order->fetch() == true)
		        continue;
			/**
			 * end check exist
			 */        
			// ITEMS!
			$all_goods = $items_collection_obj->getItemsCollection($ws_order_arr['items']);

			$userID = $this->GetUserId($ws_order_arr['contact']);
		        
	        /**
	         * ORDER OBJECT
	         */
			$siteId = 's1'; // код сайта
			$order = \Bitrix\Sale\Order::create($siteId, $userID);

			$order->setPersonTypeId(PERSON_TYPE_ID); // 1 - ID типа плательщика
		        
	        /**
	         * BASKET OBJECT
	         */
			$basket = \Bitrix\Sale\Basket::create($siteId);

		        if(!empty($all_goods)){
		            foreach ($all_goods as $product)
		            {
		            	$prod_id = $product["PRODUCT_ID"];
		                $item = $basket->createItem("catalog", $prod_id);
		                
		                $quantity = 1;
		            	$arPrice = \CCatalogProduct::GetOptimalPrice($prod_id, $quantity);
		                
		                $update_params = array();
		                if( $arPrice != false && (int)$arPrice['PRICE']['PRICE'] <= 0 && $product['PRICE'] > 0 ){
							$arFields = array('PRODUCT_ID' => $prod_id, 'PRICE' => $product['PRICE']);
		            	    $res1 = \CPrice::Update($arPrice['PRICE']["ID"], $arFields);
		                }elseif($arPrice == false){
		            	    $arFields = Array(
						        "PRODUCT_ID" => $prod_id,
						        "CATALOG_GROUP_ID" => CATALOG_GROUP_ID,
						        "PRICE" => $product['PRICE'],
						        "CURRENCY" => "RUB",
						        "QUANTITY_FROM" => false,
						        "QUANTITY_TO" => false
						    );
		            	    $RES2 = \CPrice::Add($arFields);
		                }
		                unset($prod_id); 
		                $item->setFields($product);
		            }   
		        }

			$order->setBasket($basket);
		     $ws_order_params = $ws_order_arr['params'];
			/*
			FIELDS
			 */
		        if($ws_order_arr['comment'] != '')
		            $res1 = $order->setfield('COMMENTS', $ws_order_arr['comment']);
		        
		        $collection = $order->getPropertyCollection();
			
		        foreach ($collection as $propertyValue)
		        {
		            $propertyInfo = $propertyValue->getProperty();
		            if($propertyInfo['CODE'] == 'LOCATION')
		                $propertyValue->setfield('VALUE' , '');   
		            if($propertyInfo['CODE'] == 'ADDRESS')
		                $propertyValue->setfield('VALUE' , $ws_order_params['shipping_address.street']);
		            if($propertyInfo['CODE'] == 'CITY')
		                $propertyValue->setfield('VALUE' , $ws_order_params['shipping_address.city']);
		            if($propertyInfo['CODE'] == 'ZIP')
		                $propertyValue->setfield('VALUE' , $ws_order_params['shipping_address.zip']); 
		            if($propertyInfo['CODE'] == 'EXTERNAL_ID')
		                $propertyValue->setfield('VALUE' ,$ws_order_arr['id']);     
		            // if($propertyInfo['CODE'] == 'ID_ZAKAZA')
		                // $propertyValue->setfield('VALUE' , '100'.$ws_order_arr['id']);             
		        }    
		        
		        
	        // Неизвестная группа св-в
	        $counter = 99;
	        foreach ($ws_order_params  as $key  => $param){
	            if(in_array($key, ['shipping_address.street', 'shipping_address.city', 'shipping_address.zip', 'user_agent']))
	            	continue;
	            $counter++;
	            $propertyValue = $collection->createItem([
	                'ID' => $counter,
	                'NAME' => $key,
	                'TYPE' => 'STRING',
	                'CODE' => $key,
	            ]);

	            $propertyValue->setField('VALUE', $param);
	        }
		        

			/**
			 * [$shipmentCollection description]
			 * @var [type]
			 */
			$shipmentCollection = $order->getShipmentCollection();
			$CACHE['arDeliveries'][$ws_order_params['shipping_id']] = $CACHE['arDeliveries'][$ws_order_params['shipping_id']] != false ? $CACHE['arDeliveries'][$ws_order_params['shipping_id']] : DEFAULT_DELIVERY_ID;
		    $shipment = $shipmentCollection->createItem(
			    \Bitrix\Sale\Delivery\Services\Manager::getObjectById($CACHE['arDeliveries'][$ws_order_params['shipping_id']]) 
			);

			$shipmentItemCollection = $shipment->getShipmentItemCollection();

			foreach ($basket as $basketItem)
			{
			    $item = $shipmentItemCollection->createItem($basketItem);
			    $item->setQuantity($basketItem->getQuantity());
			}
			// Цена доставки
			$shipment->setBasePriceDelivery($ws_order_arr['shipping']);
			
	        // Трек номер доставки
	        if($ws_order_params['tracking_number'] != '')
	            $res1 = $shipment->setfield('TRACKING_NUMBER', $ws_order_params['tracking_number']);
		        
			/**
			 * [$paymentCollection description]
			 * @var [type]
			 */
			$paymentCollection = $order->getPaymentCollection();
			$payment = $paymentCollection->createItem(
			    \Bitrix\Sale\PaySystem\Manager::getObjectById($CACHE['arrPayments'][$ws_order_params['payment_id']]) // 1 - ID платежной системы
			);

			$payment->setField("SUM", $ws_order_arr['total']);
			$payment->setField("CURRENCY", 'RUB');


			/**
			 * [$r SAVE!!!!]
			 * @var [type]
			 */
			$r = $order->save();
			if (!$r->isSuccess())
			{ 
			    var_dump($r->getErrorMessages());
			}
			// НОМЕР ЗАКЗА
			$res1 = $order->setfield('ACCOUNT_NUMBER', '100'.$ws_order_arr['id']);
			$r = $order->save();
		}
	}

	private function GetUserId($ws_contact){
		$user_ID = false;    
		$filter = Array
		(
		    "UF_EXTERNAL_ID" => $ws_contact['id'],
		    "LOGIN_EQUAL" => $ws_contact['email'],
		);
		$by1 = array();
		$order1 = array();
		$rsUsers = \CUser::GetList($by1, $order1, $filter); // выбираем пользователей
	        if($user_arr = $rsUsers->getnext(true, false)) :
		   $user_ID = $user_arr['ID'];    	
		endif;

		if($user_ID == false){
			$user = new \CUser;
			$arFields = Array(
			  "NAME"              => $ws_contact['name'],
			  "EMAIL"             => $ws_contact['email'],
			  "LOGIN"             => $ws_contact['email'],
			  "PERSONAL_PHONE"	  => $ws_contact['phone'],
			  "LID"               => "s1",
			  "ACTIVE"            => "Y",
			  "GROUP_ID"          => array(USER_GROUP_ID),
			  "PASSWORD"          => "Vcv9EwXgP9J~(=v[",
			  "CONFIRM_PASSWORD"  => "Vcv9EwXgP9J~(=v[",
			  "PERSONAL_PHOTO"    => '',
			  "UF_EXTERNAL_ID"    => $ws_contact['id'],
			);

			$user_ID = $user->Add($arFields);
		}
		return $user_ID;
	}
}