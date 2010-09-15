<?php
// 緯度経度変換ライブラリの読み込み
require_once(VENDORS. 'Geomobilejp'. DS. 'Converter.php');
require_once(VENDORS. 'Geomobilejp'. DS. 'Mobile.php');

// 距離計算ライブラリの読込み
App::import('Vendor', 'Geo.GeoSimple');

class GeoSimpleComponent extends Object {
	var $Controller;
	var $gpsParams = array(
		'lat' => 35.683096,
		'lng' => 139.766264,
	);
	function startup(&$controller) {
		$this->Controller =& $controller;
	}

	#####################################################
	/**
	 * GPS位置情報を取得する
	 * @return 緯度（lat）と経度（lng）の配列
	 */
	#####################################################
	function getGPS(){

		// PC等の場合
		$args = array_merge($this->Controller->params['url'], $this->Controller->params['named']);
		if (isset($args['gpslat']) && isset($args['gpslng'])) {
			return $this->setGpsParams($args['gpslat'], $args['gpslng']);
		}
		
		// ドコモのパラメータの変換
		if(isset($_REQUEST['LAT'])) $_REQUEST['lat'] = $_REQUEST['LAT'];
		if(isset($_REQUEST['LON'])) $_REQUEST['lon'] = $_REQUEST['LON'];
		if(isset($_REQUEST['GEO'])) $_REQUEST['geo'] = $_REQUEST['GEO'];
		
		// 緯度経度変換コンバーターの読込み端末のGPS位置情報を取得
		$mobile = new Geomobilejp_Mobile();
		if ($mobile->hasParameter()) {
			$lat = $mobile->getLatitude();
			$lng = $mobile->getLongitude();
			$dat = $mobile->getDatum();
			$conv = new Geomobilejp_Converter($lat, $lng, $dat);
			$conv = $conv->convert('wgs84')->format('degree');
			return $this->setGpsParams($conv->getLatitude(), $conv->getLongitude());
		}
		
		return $this->gpsParams;
	}
	
	#####################################################
	/**
	 * pagenation用にコントローラーのnamedパラメータに反映
	 */
	#####################################################
	function setGpsParams($lat, $lng){
		$lat = (float)$lat;
		$lng = (float)$lng;
		
		$this->Controller->params['named']['gpslat'] = $this->gpsParams['lat'] = $lat;
		$this->Controller->params['named']['gpslng'] = $this->gpsParams['lng'] = $lng;
		
		return $this->gpsParams;
	}
	
	#####################################################
	/**
	 * 2地点間の距離計算
	 */
	#####################################################
	function distance(){
		$args = func_get_args();
		return call_user_func_array(array('GeoSimple', 'distance'), $args);
	}
}
