<?

namespace Api\Classes;

class Payments{

	public static function getPayments(){
		$filter = array();

		$filter['ACTIVE'] = 'N';
		$filter['!XML_ID'] = false;

		if(!isset($by))
			$by = 'ID';
		if(!isset($order))
			$order = 'ASC';

		$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getList(
			array(
				'select' => array('ID', 'XML_ID'),
				'filter' => $filter,
				'order' => array(ToUpper($by) => ToUpper($order))
			)
		);

		$arrPayments = array();
		while ($arResult = $dbRes->fetch())
			$arrPayments[$arResult['XML_ID']] = $arResult['ID'];

		return $arrPayments;
	}
}