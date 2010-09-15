<?php
	if(Configure::version() < 1.3){
		$paginator->options['url'] = $this->params['named'];
	}
?>
CakePHP ver:<?php echo Configure::version(); ?><br /><br />

<?php foreach($results as $i => $_): ?>
<p>
	<?php echo $_['Station']['name']; ?>
	約<?php echo $geoSimple->distance($_['Station']['lat'], $_['Station']['lng'], $options['geo']['lat'], $options['geo']['lng'], 2); ?>Km
</p>
<?php endforeach; ?>

<?php echo $paginator->numbers(); ?>
