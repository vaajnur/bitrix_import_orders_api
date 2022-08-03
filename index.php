<?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Импорт");
$APPLICATION->RestartBuffer();

use Bitrix\Sale;
use Api\Classes\ImportOrders;

\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('iblock');

require __DIR__ . '/vendor/autoload.php';

define('PERSON_TYPE_ID', 1);
define('CATALOG_GROUP_ID', 1);
define('USER_GROUP_ID', 2);
define('CATALOG_PRODS_IBLOCK', 4);
define('OLD_CATALOG_IBLOCK', 15);
define('OLD_CATALOG_ARTICUL_PROP', 168);
define('DEFAULT_DELIVERY_ID', 2);

new ImportOrders;