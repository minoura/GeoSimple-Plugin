<?php
/**
 * GoogleStaticMapとの連携サンプル
 */
class GmapsController extends AppController{
	var $name   = 'Gmaps';
	var $uses   = array('Station');
	var $components = array('Geo.GeoSimple');
	var $helpers    = array('Geo.GeoSimple', 'Geo.GeoStaticMap');
	
	/**
	 * pattern1 GPSの位置を地図に表示
	 */
	function gps1(){}
	function map1(){
		// GPS緯度経度を取得(params['named']に保持される)
		$this->GeoSimple->getGPS();
	}
	
	/**
	 * pattern2 GPSの位置と近隣情報を10件毎に表示
	 *          - 地図を拡大・縮小するリンクをつける
	 *          - 地図の中心を選択対象をにするリンクをつける
	 *          - 地図をに上下左右に移動するリンクをつける
	 */
	function gps2(){}
	function map2(){
		$options = array();
		$options['limit'] = 10;
		
		// 現在地を取得し緯度経度検索を行う(位置検索オプションを指定)
		$options['geo'] = $this->GeoSimple->getGPS();
		$options['geo']['distance'] = 5;    // 半径5Km以内
		$options['geo']['recalc']   = true; // 距離の再計算を実施
		
		// 検索
		$results = $this->Station->find('all', $options);
		$this->set(compact('results', 'options'));
	}
	
	/**
	 * pattern3 改ページ付きで実行
	 */
	function gps3(){}
	function map3(){
		$options = array(
			'limit' => 10, // 10件で改ページ
		);
		$options['geo'] = $this->GeoSimple->getGPS();
		$options['geo']['distance'] = 20; // 半径20Km以内
		$options['geo']['recalc'] = true; // 距離の再計算を実施
		
		// behaviorの検索条件生成メソッド
		$this->paginate = $this->Station->geoFindOptions($options);
		
		// 検索
		$results = $this->paginate('Station');
		$this->set(compact('results', 'options'));
	}
}

