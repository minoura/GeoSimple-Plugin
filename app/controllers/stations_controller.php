<?php

/**
 * Sample Controller
 */
class StationsController extends AppController{
	var $name   = 'Stations';
	var $uses   = array('Station');
	var $components = array('Geo.GeoSimple');
	var $helpers    = array('Geo.GeoSimple');
	
	/**
	 * pattern1 単純に近い順に20件を取得
	 */
	function gps1(){}
	function search1(){
		
		// 通常の検索オプション
		$options = array('limit' => 20);
		
		// 現在地を取得し緯度経度検索を行う
		$options['geo'] = $this->GeoSimple->getGPS();
		
		// 検索
		$results = $this->Station->find('all', $options);
		$this->set(compact('results', 'options'));
	}
	
	/**
	 * pattern2 半径5Km以内のデータを最大10件取得し取得時に距離計算を実施
	 */
	function gps2(){}
	function search2(){
		
		// 通常の検索オプション
		$options = array(
			'limit' => 10,
			// 他の条件を含める場合
			// 'conditions' => array('Station.hoge' => 'hoge'),
		);
		
		// 現在地を取得し緯度経度検索を行う(位置検索オプションを指定)
		$options['geo'] = $this->GeoSimple->getGPS();
		$options['geo']['distance'] = 5;    // 半径5Km以内
		$options['geo']['recalc']   = true; // 距離の再計算を実施
		
		// 検索
		$results = $this->Station->find('all', $options);
		$this->set(compact('results', 'options'));
	}
	
	/**
	 * pattern3 最も近い1件を取得
	 */
	function gps3(){}
	function search3(){
		
		$options = array();
		$options['geo'] = $this->GeoSimple->getGPS();
		$options['geo']['recalc'] = true; // 距離の再計算を実施
		
		// 検索
		$results = $this->Station->find('first', $options);
		$this->set(compact('results', 'options'));
	}
	
	/**
	 * pattern4 pagenatorと連動
	 */
	function gps4(){}
	function search4(){
		
		$options = array(
			'limit' => 10, // 10件づつ改ページ
		);
		$options['geo'] = $this->GeoSimple->getGPS();
		$options['geo']['distance'] = 20; // 半径20Km以内
		
		// behaviorの検索条件生成メソッド
		$this->paginate = $this->Station->geoFindOptions($options);
		
		// 検索
		$results = $this->paginate('Station');
		$this->set(compact('results', 'options'));
	}
}

