CakePHP ver:<?php echo Configure::version(); ?><br /><br />

<?php foreach($results as $i => $_): ?>
<p>
	<?php echo $i+1; ?>：
	<?php echo $_['Station']['name']; ?>
	約<?php echo round($_['Station']['__distance'], 2); ?>Km
</p>
<?php endforeach; ?>
