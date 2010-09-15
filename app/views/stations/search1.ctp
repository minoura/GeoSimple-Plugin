CakePHP ver:<?php echo Configure::version(); ?><br /><br />

<?php foreach($results as $i => $_): ?>
<p>
	<?php echo $i+1; ?>：
	<?php echo $_['Station']['name']; ?>
	<?php /*** 概算距離を表示する場合
	約<?php echo $geoSimple->distance($_['Station']['lat'], $_['Station']['lng'], $options['geo']['lat'], $options['geo']['lng'], 2); ?>Km
	***/ ?>
	<?php echo $geoSimple->gmapLink('地図', $_['Station']['lat'], $_['Station']['lng'], 15); ?>
</p>
<?php endforeach; ?>
