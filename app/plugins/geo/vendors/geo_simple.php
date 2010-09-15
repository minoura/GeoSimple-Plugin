<?php
class GeoSimple {
	
	#########################################################################
	/**
	 * 2地点間の距離(Km)計算
	 * $decimal は小数点以下の桁数
	 */
	#########################################################################
	function distance($lat1, $lng1, $lat2, $lng2, $decimal=5 ){
		if( (abs($lat1-$lat2) < 0.00001) && (abs($lng1-$lng2) < 0.00001) ){
			$distance = 0;
		}else{
			$lat1 = $lat1*pi()/180;$lng1 = $lng1*pi()/180;
			$lat2 = $lat2*pi()/180;$lng2 = $lng2*pi()/180;
	
			$A = 6378140;
			$B = 6356755;
			$F = ($A-$B)/$A;
	
			$P1 = atan(($B/$A)*tan($lat1));
			$P2 = atan(($B/$A)*tan($lat2));
	
			$X = acos( sin($P1)*sin($P2) + cos($P1)*cos($P2)*cos($lng1-$lng2) );
			$L = ($F/8)*( (sin($X)-$X)*pow((sin($P1) + sin($P2)),2)/pow(cos($X/2) ,2) - (sin($X)-$X)*pow(sin($P1)-sin($P2),2)/pow(sin($X),2) );
	
			$distance = $A*($X+$L);
			$decimal_no=pow(10,$decimal);
			$distance = round($decimal_no*$distance/1000)/$decimal_no;
		}
		$format='%0.'.$decimal.'f';
		return sprintf($format,$distance);
	}
}
