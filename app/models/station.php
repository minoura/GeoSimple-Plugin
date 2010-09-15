<?php
/**
 * Sample Model
 */
class Station extends AppModel{
	var $name      = 'Station';
	var $useTable  = 'stations';
	var $recursive = -1;
	
	var $actsAs = array('Geo.GeoSimple');
}

