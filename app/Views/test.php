<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<h1>test:</h1>

<pre>
<?php 
var_dump($test); 
?>
</pre>

<?= $this->endSection() ?>