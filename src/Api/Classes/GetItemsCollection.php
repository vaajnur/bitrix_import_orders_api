<?php

namespace Api\Classes;

class GetItemsCollection{

	/**
	 * [SearchByKode description]
	 * @param [type] $kode    [description]
	 * @param [type] $catalog [description]
	 */
	public function SearchByKode($kode, $catalog){
		foreach ($catalog as $key => $value) {
			// pr($value);
			if($value['ARTNUMBER'] == $kode)
	            return $value['ID'];
		}
		return false;
	}

	/**
	 * [getOldCatalog description]
	 * @return [type] [description]
	 */
	private function getOldCatalog(){
		// Старый каталог
		$old_prods_list = [];
		$res2 = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => OLD_CATALOG_IBLOCK, 'IBLOCK_TYPE' => 'old_catalog'), false, false, array('ID', 'NAME', 'IBLOCK_ID'));
		while ($ob1 = $res2->getnextelement()) {
		    $f = $ob1->getfields();
		    $props = $ob1->getproperties();
		    if($props['ARTNUMBER']['VALUE'] != '')
		            $f['ARTNUMBER'] = $props['ARTNUMBER']['VALUE'];
		    else
		            $f['ARTNUMBER'] = '';
		    $old_prods_list[] = $f;
		}
	}

	/**
	 * [getItemsCollection description]
	 * @param  [type] $items [description]
	 * @return [type]        [description]
	 */
	public function getItemsCollection($items){
		$old_prods_list = $this->getOldCatalog();
		$all_items = [];
		foreach ($items as $key => $value) {
	            $str = $value['name'];
	            $value['quantity'] = $value['quantity'] > 0 ? $value['quantity'] : 1;
	            $value['name'] = substr($str, 0, strlen($str)/2);

				if($ID_old = $this->SearchByKode($value['sku_code'], $old_prods_list)){

	                $all_items[] = array(
	                        'PRODUCT_ID' => $ID_old, // новый элемент 
	                        'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
	                        'NAME' => $value['name'], 
	                        'PRICE' => $value['price'], 
	                        'CURRENCY' => 'RUB', 
	                        'QUANTITY' => $value['quantity'], 
							);

				}else{


					// var_dump($ID);
					echo "no!!";
					// continue;
		            $arFields = array(
		                'NAME' => $value['name'],
		                'IBLOCK_ID' => OLD_CATALOG_IBLOCK, // старый каталог
		                'ACTIVE' => 'Y',
		                'PROPERTY_VALUES' => array(OLD_CATALOG_ARTICUL_PROP => $value['sku_code']) // артикул
		             );
		            $obElement = new \CIBlockElement();
		             if($elemId = $obElement->Add($arFields)){
		                   $arFields = array(
		                    "ID" => $elemId, 
		                    "QUANTITY" => $value['quantity'] != '' ? $value['quantity'] : '100'   
		                    );
		                    if(\CCatalogProduct::Add($arFields)){
		                    	$all_items[] = array(
						        'PRODUCT_ID' => $elemId, // новый элемент 
						        'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
						        'NAME' => $value['name'], 
						        'PRICE' => $value['price'], 
						        'CURRENCY' => 'RUB', 
						        'QUANTITY' => $value['quantity'], 
								);
		                        echo "sucsess $elemId updated!" .PHP_EOL;
		                    }else{
		                        echo "Error 2: product $elemId not added".PHP_EOL;
		                    }
		             }else{
		                 echo 'Error 1: '.$value['name'].' not added'.PHP_EOL;
		             } 


		         }
			// }
		}
		global $APPLICATION;
		if($ex = $APPLICATION->getException())
			echo $ex->getString();

		return $all_items;
	}

}