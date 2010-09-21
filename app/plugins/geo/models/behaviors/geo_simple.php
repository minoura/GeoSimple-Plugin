<?php
// 距離計算ライブラリの読込み
App::import('Vendor', 'Geo.GeoSimple');

class GeoSimpleBehavior extends ModelBehavior {
	
	// 緯度経度カラム
	var $geoFields = array('lat' => 'lat', 'lng' => 'lng');
	
	// 位置検索時のパラメータ
	var $geoOptions = array(
		'lat'      => 35.683096,
		'lng'      => 139.766264,
		'distance' => false,
		'recalc'   => false,
	);
	
	// find時のコールバック
	var $callbackFind = true;
	
	#########################################################################
	/**
	 * 設定
	 */
	#########################################################################
	function setup(&$model, $configs = array()) {
		foreach($configs as $key=>$value){
			if(isset($this->{$key})){
				$this->{$key} = $value;
			}
		}
	}
	
	#########################################################################
	/**
	 * コールバックメソッド　検索前
	 * 検索オプション(order, conditions)を設定
	 */
	#########################################################################
	function beforeFind(&$model, $queryData){
		if(!$this->callbackFind) return $queryData;
		if(isset($queryData['geo'])){
			$queryData = $this->geoFindOptions($model, $queryData, $queryData['geo']);
		}
		return $queryData;
	}
	
	#########################################################################
	/**
	 * コールバックメソッド　検索後
	 * 距離の再計算と並び替えを実行
	 */
	#########################################################################
	function afterFind(&$model, $results, $primary = true) {
		if(!$this->callbackFind) return $results;
		
		// 距離をより正確に再計算して並び替え
		if($this->geoOptions['recalc'] && !empty($results)){
			if($primary){
				$distanceArr = array();
				foreach($results as $i=>$_){
					if(!isset($_[$model->alias])) return $results;
					$dis = $results[$i][$model->alias]['__distance'] = $this->distance(
						  $this->geoOptions['lat']
						, $this->geoOptions['lng']
						, $_[$model->alias][$this->geoFields['lat']]
						, $_[$model->alias][$this->geoFields['lng']]
					);
					$distanceArr[$dis] = $i;
				}
				ksort($distanceArr);
				$ret = array();
				foreach($distanceArr as $dis => $i){
					if($this->geoOptions['distance'] && $dis > $this->geoOptions['distance']){
						break;
					}
					$ret[] = $results[$i];
				}
				$results = $ret;
			}else{
				$dis = $results['__distance'] = $this->distance(
					  $this->geoOptions['lat']
					, $this->geoOptions['lng']
					, $_[$model->alias][$this->geoFields['lat']]
					, $_[$model->alias][$this->geoFields['lng']]
				);
				if($this->geoOptions['distance'] && $dis > $this->geoOptions['distance']){
					$results = array();
				}
			}
		}
		return $results;
	}
	
	#########################################################################
	/**
	 * 検索条件等を設定
	 * $geoOptions = array(
	 *    'lat' => 緯度,
	 *    'lng' => 経度,
	 *    'latlng' => 緯度,経度,
	 *    'distance' => 10, // 概算10Km以内で検索 or false:指定しない
	 *    'recalc' => false, // true:取得後に再度距離計算をPHP側で実施
	 * )
	 */
	#########################################################################
	function geoFindOptions(&$model, $findOptions = array(), $geoOptions = array()){
		$this->geoOptions = $geoOptions = array_merge($this->geoOptions, $geoOptions);
		if(isset($geoOptions['latlng']) && $geoOptions['latlng']){
			list($geoOptions['lat'], $geoOptions['lng']) = explode(',', $geoOptions['latlng'], 2);
		}
		$geoOptions['lat'] = (float)$geoOptions['lat'];
		$geoOptions['lng'] = (float)$geoOptions['lng'];
		
		$geoParams = array();
		$geoParams[] = $model->alias. '.' .$this->geoFields['lat'];
		$geoParams[] = $geoOptions['lat'];
		$geoParams[] = $model->alias. '.' .$this->geoFields['lng'];
		$geoParams[] = $geoOptions['lng'];
		
		$findOptions['order'] = vsprintf('power(abs(%s - %s), 2) + power(abs(%s - %s), 2)', $geoParams);
		
		// 距離制限をつける
		if($geoOptions['distance']){
			$geoParams[] = $geoOptions['distance'] * 0.01; // 0.01で約1Km
			if(!isset($findOptions['conditions'])) $findOptions['conditions'] = array();
			$findOptions['conditions'][] = vsprintf('power(abs(%s - %s), 2) + power(abs(%s - %s), 2) <= power(%s, 2)', $geoParams);
		}
		return $findOptions;
	}
	
	
	#########################################################################
	/**
	 * 通常のfindメソッドのコールバックを使わない検索
	 */
	#########################################################################
	function geoFind(&$model, $type = 'all', $findOptions = array(), $geoOptions = array()) {
		if(isset($queryData['geo']) && empty($geoOptions)){
			$geoOptions = $queryData['geo'];
		}
		$findOptions = $this->geoFindOptions($model, $findOptions, $geoOptions);
		
		$model->Behaviors->disable('GeoSimple');
		$results = $model->find($type, $findOptions);
		$model->Behaviors->enable('GeoSimple');
		
		return $this->afterFind($model, $results, true);
	}
	
	#########################################################################
	/**
	 * 2地点間の距離計算
	 */
	#########################################################################
	function distance(){
		$args = func_get_args();
		return call_user_func_array(array('GeoSimple', 'distance'), $args);
	}
}
