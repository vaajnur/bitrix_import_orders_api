<?php

namespace Api\Classes;

class Cache{

    private static $cacheTime;
    private static $cacheId;
    private static $cacheDir;
 
    public function __construct(){
        $this->cacheTime = '8640000';
        $this->cacheId = 'catalog_elems';
        $this->cacheDir = 'catalog_elems';       
    }

    public static function getCache(){

        $cache = \Bitrix\Main\Data\Cache::createInstance();

        if ($cache->initCache(self::$cacheTime, self::$cacheId, self::$cacheDir))
        {
            $CACHE = $cache->getVars();
        }
        elseif ($cache->startDataCache())
        {
            $CACHE = array();
            if ($isInvalid)
            {
                $cache->abortDataCache();
            }

            // CATALOG PRODS
            $res1 = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => CATALOG_PRODS_IBLOCK, 'IBLOCK_TYPE' => 'catalog'), false, false, array('ID', 'NAME', 'IBLOCK_ID'));
            $prods_list = [];
            while ($ob = $res1->getnextelement()) {
                $f = $ob->getfields();
                $props = $ob->getproperties();
                if($props['ARTNUMBER']['VALUE'] != '')
                    $f['ARTNUMBER'] = $props['ARTNUMBER']['VALUE'];
                else
                    $f['ARTNUMBER'] = '';
                $prods_list[] = $f;
            }

            $CACHE['prods_list'] = $prods_list;
            $CACHE['arDeliveries'] = \Api\Classes\Deliveries::getDeliveries();
            $CACHE['arrPayments'] = \Api\Classes\Payments::getPayments();
            $cache->endDataCache($CACHE);
        } 

        return $CACHE;
    }

}