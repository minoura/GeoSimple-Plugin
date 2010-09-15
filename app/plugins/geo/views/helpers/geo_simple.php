<?php
// 携帯ライブラリ
require_once(VENDORS.'ecw'.DS.'lib3gk.php');

// 距離計算ライブラリの読込み
App::import('Vendor', 'Geo.GeoSimple');

class GeoSimpleHelper extends Helper {
	
	var $helpers = array('Html', 'Javascript');
	
	#####################################################
	/**
	 * GPS機能の判別 (分岐条件は要検証)
	 *   0: PC/iPhone等
	 *   1: 基地局レベル
	 *   2: GPS端末
	 * 
	 */
	#####################################################
	function getGpsType(){
		
		$agent   = @$_SERVER['HTTP_USER_AGENT'];
		$lib3gk  = Lib3gk::get_instance();
		$carrier = $lib3gk->get_carrier();
		
		switch($carrier){
			case KTAI_CARRIER_DOCOMO:
				
				$info = $lib3gk->get_machineinfo();
				if( preg_match('/^DoCoMo\\/2\\.0\\ /', $agent) &&
				    !in_array($info['machine_name'], array(
				 		'F884i','F801i','F905iBiz','SO905iCS','N905iBiz','N905imyu','SO905i','F905i','P905i','N905i','D905i','SH905i','P904i','D904i',
						'F904i','N904i','SH904i','F883iESS','F883iES','F903iBSC','SO903i','F903i','D903i','N903i','P903i','SH903i','SA800i','SA702i',
						'SA700iS','F661i','F884iES','N906iL','P906i','SO906i','SH906i','N906imyu','F906i','N906i','F01A','F03A','F06A',
						'F05A','P01A','P02A','SH01A','SH02A','SH03A','SH04A','N01A','N02A','P07A3','N06A3','N08A3','P08A3','P09A3','N09A3','F09A3',
						'SH05A3','SH06A3','SH07A3'))
				){
					return array($carrier, 2);
				}else{
					return array($carrier, 1);
				}
				
			case KTAI_CARRIER_KDDI:
				return array($carrier, 2);
			
			case KTAI_CARRIER_SOFTBANK:
				if(preg_match("/^(Softbank|Vodafone|MOT\-[CV]|Vemulator)/i", $agent)){
					return array($carrier, 2);
				}else{
					return array($carrier, 1);
				}
		}
		return array(0, 0);
	}
	
	#####################################################
	/**
	 * GPS用のリンクを生成
	 */
	#####################################################
	function geoLink($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true){
		
		// 一旦通常リンクを生成
		$htmlLink = $this->Html->link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle);
		
		// リンクを分解
		$parser = xml_parser_create();
		if(!xml_parse_into_struct($parser, $htmlLink, $vals, $index) || empty($vals)){
			return $htmlLink;
		}
		xml_parser_free($parser);
		
		$attrs = array();
		foreach($vals[0]['attributes'] as $k => $v){
			$k = strtolower($k);
			if($k == 'href'){
				$url = $v;
			}else{
				$attrs[] = sprintf('"%s"="%s"', $k, $v);
			}
		}
		if(isset($vals[0]) && isset($vals[0]['value'])){
			$title = $vals[0]['value'];
		}
		$attr = implode(' ', $attrs);
		
		// リンクURLを絶対パスに変更する
		if(strpos($url, 'http', 0) !== 0){
			$url = 'http://' . $_SERVER['HTTP_HOST']. $url;
		}
		
		list($carrier, $gpsType) = $this->getGpsType();
		
		if($carrier === KTAI_CARRIER_DOCOMO && $gpsType === 1){
			return sprintf('<a href="http://w1m.docomo.ne.jp/cp/iarea?ecode=OPENAREACODE&msn=OPENAREAKEY&posinfo=1&nl=%s" %s>%s</a>', urlencode($url), $attr, $title);
		}
		if($carrier === KTAI_CARRIER_DOCOMO && $gpsType === 2){
			return sprintf('<a href="%s" %s lcs>%s</a>', $url, $attr, $title);
		}
		if($carrier === KTAI_CARRIER_KDDI && $gpsType === 1){
			return sprintf('<a href="device:location?url=%s" %s>%s</a>', $url, $attr, $title);
		}
		if($carrier === KTAI_CARRIER_KDDI && $gpsType === 2){
			return sprintf('<a href="device:gpsone?url=%s&ver=1&datum=0&acry=0&number=0" %s>%s</a>', $url, $attr, $title);
		}
		if($carrier === KTAI_CARRIER_SOFTBANK && $gpsType === 1){
			return sprintf('<a href="location:cell?url=%s" %s>%s</a>', $url, $attr, $title);
		}
		if($carrier === KTAI_CARRIER_SOFTBANK && $gpsType === 2){
			return sprintf('<a href="location:gps?url=%s" %s>%s</a>', $url, $attr, $title);
		}
		
		// 携帯以外はブラウザ側のJavaScriptで位置情報APIにより取得
		$jsPath = DS. 'geo'. DS. 'js'. DS;
		
		// for Android
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
			// $this->Javascript->link('http://code.google.com/intl/ja-JP/apis/gears/gears_init.js', false);
			$this->Javascript->link($jsPath. 'gears_init.js', false);
		}
		$this->Javascript->link($jsPath. 'geo_simple.js', false);
		return sprintf('<a href="javascript:geo_simple_link(\'%s\')" %s>%s</a>', $url, $attr, $title);
	}
	
	
	#####################################################
	/**
	 * GoogleMap用のリンクを生成
	 */
	#####################################################
	function gmapLink($title, $lat, $lng, $zoom=15, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true){
		$url = sprintf('http://maps.google.com/?z=%s&q=%s,%s(%s)', $zoom, $lat, $lng, $title);
		if(!isset($htmlAttributes['target'])){
			$htmlAttributes['target'] = '_brank';
		}
		return $this->Html->link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle);
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
