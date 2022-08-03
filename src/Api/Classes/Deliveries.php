<?

namespace Api\Classes;

class Deliveries{
	public static function getDeliveries(){
		$filter = array();

		if (empty($filter["=CLASS_NAME"]))
		{
			$filter['!=CLASS_NAME'] = array(
				'\Bitrix\Sale\Delivery\Services\Group',
				'\Bitrix\Sale\Delivery\Services\EmptyDeliveryService'
			);

			/** @var \Bitrix\Sale\Delivery\Services\Base $handlerClass */
			foreach($handlersList as $handlerClass)
			{
				if($handlerClass::isProfile() && !in_array($handlerClass, $filter['!=CLASS_NAME']))
				{
					$filter['!=CLASS_NAME'][] = $handlerClass;
				}
			}
		}

		$filter['ACTIVE'] = 'N';
		$filter['SORT'] = '10';
		$filter['!XML_ID'] = false;

		$params = array(
			'filter' => $filter,
			// 'select' => array('*')
			'select' => array('ID', 'XML_ID')
		);

		if(!isset($by))
			$by = 'ID';
		if(!isset($order))
			$order = 'ASC';

		if($by <> '' && $order <> '')
			$params['order'] = array($by => $order);

		$dbResultList = \Bitrix\Sale\Delivery\Services\Table::getList($params);

		$arDeliveries = Array();
		while ($arResult = $dbResultList->fetch())
			$arDeliveries[$arResult['XML_ID']] = $arResult['ID'];

		return $arDeliveries;
	}
}