<?php
// 携帯ライブラリ
require_once(VENDORS.'ecw'.DS.'lib3gk.php');

/**
 * GeoStaticMapHelper for GoogleStaticMaps Ver1
 */
class GeoStaticMapHelper extends Helper {
	
	var $helpers = array('Html');
	
	// GoogleMapApiキー
	var $apiKey = null;
	
	// GPS緯度経度
	var $gps = array(
		'lat' => 35.683096,
		'lng' => 139.766264,
	);
	
	// マーカーの色
	var $gpsColor     = 'red';
	var $resultsColor = 'blue';
	
	// マーカー
	var $markers = array();
	var $markersIndex = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
	
	// 検索結果のマーカーインデックス => array('lat'=>'緯度', 'lng'=>'経度')
	var $results = array();
	
	// 中心地点
	var $center = array();
	
	// ズームサイズ
	var $zoom = 14;
	
	// パス生成
	var $path = array(
		'rgb' => '0x0000ff', // 線の色
		'weight' => '4',     // 線の太さ
		'points' => array(),
	);
	
	// 地図サイズ
	var $sizeW = 240;
	var $sizeH = 240;
	
	// 1回の移動時の地図サイズに対する割合(%)
	var $moveLen = 50;
	
	#####################################################
	/**
	 * 設定
	 */
	#####################################################
	function __construct($configs = array()) {
		parent::__construct($configs);
		foreach($configs as $key=>$value){
			if(isset($this->{$key})){
				$this->{$key} = $value;
			}
		}
	}
	
	function beforeRender(){
		parent::beforeRender();
		
		$this->setApiKey(Configure::read('GeoStaticMap.apiKey'));
		
		$params = $this->__getParams();
		
		if(isset($params['gpslat']) && isset($params['gpslng'])){
			$this->setGPS($params['gpslat'], $params['gpslng']);
		}
		
		if(isset($params['zoom'])){
			$this->setZoom($params['zoom']);
		}
		
		if(isset($params['clat']) && isset($params['clng'])){
			$this->setCenter($params['clat'], $params['clng']);
		}
		
		if(isset($params['plat']) && isset($params['plng'])){
			$this->setPath($params['plat'], $params['plng']);
		}
		
		$lib3gk = Lib3gk::get_instance();
		$this->setSize( $lib3gk->stretch_image_size($this->sizeW, $this->sizeH) );
	}
	
	function __getParams(){
		// リンクに引き継ぐパラメータはnamedに設定しておく
		$params = $this->params['named'];
		return $params;
	}
	
	#####################################################
	/**
	 * setter
	 */
	#####################################################
	function setApiKey($apiKey = null){
		if($apiKey){
			$this->apiKey = $apiKey;
		}
		return $this;
	}
	
	function setGPS($lat, $lng = null){
		if(is_array($lat)){
			list($lat, $lng) = $this->__splitLatLng($lat);
		}
		$this->gps = array(
			'lat' => (float)$lat,
			'lng' => (float)$lng,
		);
		return $this;
	}
	
	function setCenter($lat, $lng = null){
		if(is_array($lat)){
			list($lat, $lng) = $this->__splitLatLng($lat);
		}
		$this->center = array(
			'lat' => (float)$lat,
			'lng' => (float)$lng,
		);
		return $this;
	}
	
	function setPath($lat, $lng = null){
		if(is_array($lat)){
			list($lat, $lng) = $this->__splitLatLng($lat);
		}
		if(empty($this->path['points'])){
			$this->path['points'][] = array($this->gps['lat'], $this->gps['lng']);
		}
		$this->path['points'][] = array((float)$lat, (float)$lng);
		return $this;
	}
	
	function setZoom($zoom){
		$zoom = (int)$zoom;
		if($zoom > 20) $zoom = 20;
		if($zoom < 1)  $zoom = 1;
		$this->zoom = $zoom;
		return $this;
	}
	
	function setMarker($latlng, $color = 'red', $s = ''){
		list($lat, $lng) = $this->__splitLatLng($latlng);
		$this->markers[] = array((float)$lat, (float)$lng, $color.$s);
		return $this;
	}
	
	function setSize($w, $h = null){
		if(is_array($w)){
			list($w, $h) = $w;
		}
		if(is_null($h)){
			$h = $w;
		}
		$this->sizeW = (int)$w;
		$this->sizeH = (int)$h;
		return $this;
	}
	
	function __splitLatLng($latlng){
		if(is_array($latlng)){
			if(isset($latlng['lat']) && isset($latlng['lng'])){
				$lat = $latlng['lat'];
				$lng = $latlng['lng'];
			}else{
				list($lat, $lng) = $latlng;
			}
		}else{
			list($lat, $lng) = explode(',', $latlng, 2);
		}
		return array($lat, $lng);
	}
	
	#####################################################
	/**
	 * 検索結果の位置情報をマーカーにセット
	 */
	#####################################################
	function setResults($results, $model=null, $lat='lat', $lng='lng'){
		if(empty($results)) {
			return array();
		}
		if(!isset($results[0])){
			$results = array($results);
		}
		
		$return = array();
		foreach($results as $i => $_){
			if(!isset($this->markersIndex[$i])){
				break;
			}
			if(isset($_[$model])){
				$res =& $_[$model];
			}else{
				$res =& $_;
			}
			if(isset($res[$lat]) && $res[$lat] > 0 && isset($res[$lng]) && $res[$lng] > 0){
				$this->setMarker(array($res[$lat], $res[$lng]), $this->resultsColor, $this->markersIndex[$i]);
				$marker = strtoupper($this->markersIndex[$i]);
				$this->results[$marker] = array(
					'lat' => $res[$lat],
					'lng' => $res[$lng],
				);
				$return[$marker] = $_;
			}
		}
		
		return $return;
		
	}
	
	#####################################################
	/**
	 * 拡大縮小用のリンク
	 */
	#####################################################
	function linkZoom($title, $step, $htmlAttributes = array()){
		$params = $this->__getParams();
		$params['zoom'] = $this->zoom + $step;
		return $this->Html->link($title, $params, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 中心配置用のリンク
	 */
	#####################################################
	function linkCenter($title, $lat, $lng, $path=true, $htmlAttributes = array()){
		$lat = (float)$lat;
		$lng = (float)$lng;
		$params = $this->__getParams();
		$params['clat'] = $lat;
		$params['clng'] = $lng;
		if($path){
			$params['plat'] = $lat;
			$params['plng'] = $lng;
		}
		return $this->Html->link($title, $params, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 中心をGPS位置に戻すリンク
	 */
	#####################################################
	function linkCenterGPS($title, $htmlAttributes = array()){
		$params = $this->__getParams();
		if(isset($params['clat'])) unset($params['clat']);
		if(isset($params['clng'])) unset($params['clng']);
		if(isset($params['plat'])) unset($params['plat']);
		if(isset($params['plng'])) unset($params['plng']);
		return $this->Html->link($title, $params, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 移動系のリンク
	 */
	#####################################################
	function linkL($title, $htmlAttributes = array()){
		$len = (int)($this->sizeW * $this->moveLen / 100);
		$c = $this->__move(-($len), 0);
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	function linkR($title, $htmlAttributes = array()){
		$len = (int)($this->sizeW * $this->moveLen / 100);
		$c = $this->__move($len, 0);
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	function linkT($title, $htmlAttributes = array()){
		$len = (int)($this->sizeH * $this->moveLen / 100);
		$c = $this->__move(0, -($len));
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	function linkB($title, $htmlAttributes = array()){
		$len = (int)($this->sizeH * $this->moveLen / 100);
		$c = $this->__move(0, $len);
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 移動地点の計算
	 * 参考)http://hirokawa.netflowers.jp/entry/26247/
	 */
	#####################################################
	function __move($x = 0, $y = 0){
		if(empty($this->center)){
			$c = $this->gps;
		}else{
			$c = $this->center;
		}
		$offset = 268435456;
		$radius = $offset / pi();
		return array(
			'lat' => (pi() / 2 - 2 * atan(exp((round(round($offset - $radius * log((1 + sin($c['lat'] * pi() / 180))/(1 - sin($c['lat'] * pi() / 180))) / 2)+($y << (21-$this->zoom))) - $offset) / $radius))) * 180 / pi(),
			'lng' => ((round(round($offset + $radius * $c['lng'] * pi()/180)+($x << (21-$this->zoom))) - $offset) / $radius) * 180 / pi(),
		);
	}
	
	#####################################################
	/**
	 * 地図表示
	 */
	#####################################################
	function render(){
		
		// GPS地点をマーク
		if(!empty($this->gps)){
			$this->setMarker($this->gps, $this->gpsColor);
			
			// 中心点が設定されていなければ中心に配置
			if(empty($this->center)){
				$this->setCenter($this->gps);
			}
		}
		
		// 他のマーカーやオプションを設定
		$options = array();
		$options['markers'] = $this->markers;
		$options['zoom']    = $this->zoom;
		$options['size']    = array($this->sizeW, $this->sizeH);
		
		if(!empty($this->path['points'])){
			$options['path'] = $this->path;
		}
		
		// $lib3gk = Lib3gk::get_instance();
		// return $lib3gk->get_static_maps($this->center['lat'], $this->center['lng'], $options, $this->apiKey);
		return $this->get_static_maps($this->center['lat'], $this->center['lng'], $options, $this->apiKey);
	}
	
	#####################################################
	/**
	 * Google static Maps APIを用いて地図表示
	 * 携帯ライブラリのソースを少し改修
	 *
	 * @param $lat string 緯度
	 * @param $lon string 経度
	 * @param $options array APIに与えるオプション
	 * @param $apikey string 取得したGoogle API キー
	 * @return string imageタグ付き文字列
	 * @access public
	 */
	#####################################################
	function get_static_maps( $lat, $lon, $options = array(), $api_key = null){
		$lib3gk = Lib3gk::get_instance();
		
		$default_options = array(
			'zoom' => 15, 
			// 'format' => 'jpg', 
			'maptype' => 'mobile', 
			'sensor' => false,
		);
		$options = array_merge($default_options, $options);
		// $sep = '|';
		$sep = '%7C';
		
		//center
		//
		if($lat == '' || $lon == ''){
			if(!empty($options['center'])){
				if(is_array($options['center'])){
					$lat = $options['center'][0];
					$lon = $options['center'][1];
				}else{
					list($lat, $lon) = explode(',', $options['center']);
				}
			}else{
				return null;
			}
			unset($options['center']);
		}
		
		//size
		//
		if(!empty($options['size'])){
			if(is_array($options['size'])){
				$width  = $options['size'][0];
				$height = $options['size'][1];
			}else{
				list($width, $height) = explode('x', $options['size']);
			}
// bug?
			// unset($options['center']);
			unset($options['size']);
		}else{
			$width =  $lib3gk->_params['default_screen_size'][0];
			$height = $width;
		}
		list($width, $height) = $lib3gk->stretch_image_size($width, $height);
// add
		$width = (int) $width;
		$height = (int) $height;
		
		//markers
		//
		if(!empty($options['markers'])){
			$markers = $options['markers'];
			if(is_array($markers)){
				if(!is_array($markers[0])){
					$arr = $markers;
					$markers = array($arr);
				}
				$str = '';
				foreach($markers as $fvalue){
					if($str != ''){
						$str .= $sep;
					}
					$str .= array_shift($fvalue).','.array_shift($fvalue);
					$s = '';
					foreach($fvalue as $fvalue2){
						$s .= $fvalue2;
					}
					if($s != ''){
						$str .= ','.$s;
					}
				}
				$markers = $str;
			}
			unset($options['markers']);
		}
		
		//path
		//
		if(!empty($options['path'])){
			$path = $options['path'];
			if(!isset($path['rgb']) && !isset($path['rgba'])){
				return null;
			}
			if(empty($path['points']) || count($path['points']) < 1){
				return null;
			}
			$points = $path['points'];
			unset($path['points']);
			
			$str = '';
			foreach($path as $fkey => $fvalue){
				if($str != ''){
					$str .= ',';
				}
				$str .= $fkey.':'.$fvalue;
			}
			foreach($points as $fvalue){
				$str .= $sep;
				$str .= $fvalue[0].','.$fvalue[1];
			}
			$path = $str;
			unset($options['path']);
		}
		
		//span
		//
		if(!empty($options['span'])){
			$span = $options['span'];
			if(is_array($span)){
				$span = $span[0].','.$span[1];
			}else{
				return null;
			}
			unset($options['span']);
		}
		
		//sensor
		//
		$sensor = $options['sensor'];
		unset($options['sensor']);
		
		//api_key
		//
		if($api_key === null){
			if(!empty($options['key'])){
				$api_key = $options['key'];
			}else
			if(!empty($lib3gk->_params['google_api_key'])){
				$api_key = $lib3gk->_params['google_api_key'];
			}else{
				return null;
			}
			unset($options['key']);
		}
		
		$url = 'http://maps.google.com/staticmap?';
		$url .= 'center='.$lat.','.$lon;
		$url .= '&size='.$width.'x'.$height;
		if(!empty($markers)){
			$url .= '&markers='.$markers;
		}
		if(!empty($path)){
			$url .= '&path='.$path;
		}
		if(!empty($span)){
			$url .= '&span='.$span;
		}
		foreach($options as $fkey => $fvalue){
			$url .= '&'.$fkey.'='.$fvalue;
		}
		$url .= '&sensor='.($sensor ? 'true' : 'false');
		$url .= '&key='.$api_key;
		
		return $lib3gk->image($url, array('width' => $width, 'height' => $height), false);
	}
}
