<?php
	if(Configure::version() < 1.3){
		$paginator->options['url'] = $this->params['named'];
	}
?>

<?php /*** 地図 ***/ ?>
<?php $results = $geoStaticMap->setResults($results, 'Station'); ?>
<?php echo $geoStaticMap->render(); ?>

<?php /*** 拡大縮小のリンク ***/ ?>
<br /><br />
[<?php echo $geoStaticMap->linkZoom('拡大', 1); ?>]
[<?php echo $geoStaticMap->linkZoom('縮小', -1); ?>]
[<?php echo $geoStaticMap->linkCenterGPS('現在地'); ?>]

<?php /*** 上下左右のリンク ***/ ?>
<br /><br />
[<?php echo $geoStaticMap->linkL('←'); ?>]
[<?php echo $geoStaticMap->linkR('→'); ?>]
[<?php echo $geoStaticMap->linkT('↑'); ?>]
[<?php echo $geoStaticMap->linkB('↓'); ?>]

<?php /*** 検索結果 ***/ ?>
<br /><br />
<?php foreach($results as $i => $_): ?>
<div>
	<?php echo $i; ?>:
	<?php echo $geoStaticMap->linkCenter($_['Station']['name'], $_['Station']['lat'], $_['Station']['lng']); ?>
</div>
<?php endforeach; ?>

<?php /*** 改ページ ***/ ?>
<br />
<?php echo $paginator->numbers(); ?>
