<?php
// 携帯ライブラリ
require_once(VENDORS.'ecw'.DS.'lib3gk.php');

/**
 * GeoStaticMapHelper for Google Static Maps Ver2
 * 
 * 地図の初期状態は以下のnamedパラメータで指定
 *    gpslat	現在地の緯度
 *    gpslng	現在地の経度
 *    zoom		縮尺サイズ
 *    clat		現在地以外の中心地の緯度
 *    clng		現在地以外の中心地の経度
 *    plat		現在地からのパスを表示する際の緯度
 *    plng		現在地からのパスを表示する際の経度
 * 
 */
class GeoStaticMapHelper extends Helper {
	
	// 利用Helper
	var $helpers = array('Html');
	
	// Google Static Maps URL
	var $baseUrl = 'http://maps.google.co.jp/maps/api/staticmap?1';
	
	// GPS緯度経度
	var $gps = array(
		'lat' => 35.683096,
		'lng' => 139.766264,
	);
	
	// マーカーの色・サイズ
	var $gpsColor     = 'red';
	var $gpsSize      = '';
	var $resultsColor = 'blue';
	var $resultsSize  = 'mid'; // or tiny, small, null
	
	// マーカー配列
	var $markers      = array();
	var $markersIndex = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	
	// 検索結果 A => array('lat'=>'緯度', 'lng'=>'経度')
	var $results = array();
	
	// 中心地点
	var $center = array();
	
	// ズームサイズ
	var $zoom = 14;
	
	// パス生成
	var $path = array(
		'color'  => '0xFF00007F', // 線の色
		'weight' => '4',          // 線の太さ
		'points' => array(),
	);
	
	// 地図サイズ
	var $sizeW = 240;
	var $sizeH = 240;
	
	// 1回の移動時の地図サイズに対する割合(%)
	var $moveLen = 50;
	
	// GoogleStaticMaps 他パラメータ
	var $gparams = array(
		// 'format' => 'jpg', 
		// 'maptype' => 'hybrid',
		'mobile' => 'true',
		'sensor' => 'false',
	);
	
	#####################################################
	/**
	 * 初期設定
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
		
		$params = $this->_getParams();
		
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
	
	function _getParams(){
		// リンクに引き継ぐパラメータはnamedに設定しておく
		$params = $this->params['named'];
		return $params;
	}
	
	#####################################################
	/**
	 * setter
	 */
	#####################################################
	function setGPS($lat, $lng = null){
		if(is_array($lat)){
			list($lat, $lng) = $this->_splitLatLng($lat);
		}
		$this->gps = array(
			'lat' => (float)$lat,
			'lng' => (float)$lng,
		);
		return $this;
	}
	
	function setCenter($lat, $lng = null){
		if(is_array($lat)){
			list($lat, $lng) = $this->_splitLatLng($lat);
		}
		$this->center = array(
			'lat' => (float)$lat,
			'lng' => (float)$lng,
		);
		return $this;
	}
	
	function setPath($lat, $lng = null){
		if(is_array($lat)){
			list($lat, $lng) = $this->_splitLatLng($lat);
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
	
	function setMarker($latlng, $color = 'red', $label = '', $size = ''){
		list($lat, $lng) = $this->_splitLatLng($latlng);
		$this->markers[] = array((float)$lat, (float)$lng, $color, $label, $size);
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
	
	function _splitLatLng($latlng){
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
	 * 検索結果の位置情報をマーカーにセットし、マーク時のアルファベットをキーにした配列を返す
	 *
	 * @param array $results find('all')形式の結果配列
	 * @param string $model 結果配列のモデル名
	 * @param string $lat 緯度カラム名
	 * @param string $lng 経度カラム名
	 * @param string $size 'tiny', 'small', 'mid', null 
	 *
	 * @return array 配列インデックスを変更した結果配列
	 */
	#####################################################
	function setResults($results, $model='', $lat='lat', $lng='lng', $size = null){
		if(is_null($size)){
			$size = $this->resultsSize;
		}
		if(empty($results)) {
			return array();
		}
		if(!isset($results[0])){
			$results = array($results);
		}
		
		$return = array();
		foreach($results as $i => $_){
			if($size === 'mid' || !$size){
				if(!isset($this->markersIndex[$i])){
					break;
				}
			}
			if(isset($_[$model])){
				$res =& $_[$model];
			}else{
				$res =& $_;
			}
			if(isset($res[$lat]) && $res[$lat] > 0 && isset($res[$lng]) && $res[$lng] > 0){
				$this->setMarker(array($res[$lat], $res[$lng]), $this->resultsColor, $this->markersIndex[$i], $size);
				if($size === 'mid' || !$size){
					$marker = $this->markersIndex[$i];
				}else{
					$marker = $i;
				}
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
	 * 
	 * @param string $title リンク文字列
	 * @param int $step 拡大縮小(1,-1,2,-2 ...)
	 * @param array $htmlAttributes 
	 * 
	 * @return string Html->link()
	 */
	#####################################################
	function linkZoom($title, $step, $htmlAttributes = array()){
		$params = $this->_getParams();
		$params['zoom'] = $this->zoom + $step;
		return $this->Html->link($title, $params, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 中心配置用のリンク
	 * 
	 * @param string $title リンク文字列
	 * @param float $lat 中心地緯度
	 * @param float $lng 中心地経度
	 * @param bool $path 現在地へのパスを表示するかどうか
	 * @param array $htmlAttributes 
	 * 
	 * @return string Html->link()
	 */
	#####################################################
	function linkCenter($title, $lat, $lng, $path=true, $htmlAttributes = array()){
		$lat = (float)$lat;
		$lng = (float)$lng;
		$params = $this->_getParams();
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
	 * 
	 * @param string $title リンク文字列
	 * @param array $htmlAttributes 
	 * 
	 * @return string Html->link()
	 */
	#####################################################
	function linkCenterGPS($title, $htmlAttributes = array()){
		$params = $this->_getParams();
		if(isset($params['clat'])) unset($params['clat']);
		if(isset($params['clng'])) unset($params['clng']);
		if(isset($params['plat'])) unset($params['plat']);
		if(isset($params['plng'])) unset($params['plng']);
		return $this->Html->link($title, $params, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 移動系のリンク
	 *
	 * @param string $title リンク文字列
	 * @param array $htmlAttributes 
	 * 
	 * @return string Html->link()
	 */
	#####################################################
	function linkL($title, $htmlAttributes = array()){
		$len = (int)($this->sizeW * $this->moveLen / 100);
		$c = $this->_move(-($len), 0);
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	function linkR($title, $htmlAttributes = array()){
		$len = (int)($this->sizeW * $this->moveLen / 100);
		$c = $this->_move($len, 0);
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	function linkT($title, $htmlAttributes = array()){
		$len = (int)($this->sizeH * $this->moveLen / 100);
		$c = $this->_move(0, -($len));
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	function linkB($title, $htmlAttributes = array()){
		$len = (int)($this->sizeH * $this->moveLen / 100);
		$c = $this->_move(0, $len);
		return $this->linkCenter($title, $c['lat'], $c['lng'], false, $htmlAttributes);
	}
	
	#####################################################
	/**
	 * 移動地点の計算
	 * 参考)http://hirokawa.netflowers.jp/entry/26247/
	 */
	#####################################################
	function _move($x = 0, $y = 0){
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
	 * 地図表示 デフォルトではGPS位置を中心地とする
	 * 
	 * @return string imgタグ
	 */
	#####################################################
	function render(){
		$url = $this->getStaticMaps();
		
		$lib3gk = Lib3gk::get_instance();
		return $lib3gk->image($url, array('width' => $this->sizeW, 'height' => $this->sizeH), false);
	}
	
	#####################################################
	/**
	 * Google Static Maps 用のURLを生成
	 */
	#####################################################
	function getStaticMaps(){
		$gparams = $this->gparams;
		$sep = '%7C'; // '|'
		
		// GPS地点をマーク
		if(!empty($this->gps)){
			$this->setMarker($this->gps, $this->gpsColor, '', $this->gpsSize);
			
			// 中心点が設定されていなければ中心に配置
			if(empty($this->center)){
				$this->setCenter($this->gps);
			}
		}
		
		// center
		$gparams['center'] = sprintf('%s,%s', $this->center['lat'], $this->center['lng']);
		
		// zoom
		$gparams['zoom'] = $this->zoom;
		
		// size
		$gparams['size'] = sprintf('%sx%s', $this->sizeW, $this->sizeH);
		
		// path
		if(!empty($this->path['points'])){
			$path = array();
			foreach($this->path as $k => $v){
				if($k != 'points'){
					$path[] = sprintf('%s:%s', $k, $v);
				}else{
					$path[] = sprintf('%s,%s', $v[0][0], $v[0][1]);
					$path[] = sprintf('%s,%s', $v[1][0], $v[1][1]);
				}
			}
			$gparams['path'] = implode($sep, $path);
		}
		
		// markers
		if(!empty($this->markers)){
			foreach($this->markers as $arr){
				list($lat, $lng, $color, $label, $size) = $arr;
				$mark = array();
				$mark[] = sprintf('size:%s', $size);
				$mark[] = sprintf('color:%s', $color);
				$mark[] = sprintf('label:%s', $label);
				$mark[] = sprintf('%s,%s', $lat, $lng);
				$gparams['markers'][] = implode($sep, $mark);
			}
		}
		
		// url
		$url = $this->baseUrl;
		foreach($gparams as $k => $v){
			if(is_array($v)){
				foreach($v as $v){
					$url .= sprintf('&%s=%s', $k, $v);
				}
			}else{
				$url .= sprintf('&%s=%s', $k, $v);
			}
		}
		return $url;
	}
}
