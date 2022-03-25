<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<h1>Package:</h1>

<h2>Kod włożenia do paczkomatu: <?php echo $package->code; ?></h2>
<img src="<?php echo $inQR; ?>" alt="QR Code" />
<h2>Kod wyjecia z paczkomatu: <?php echo $package->recipient_code; ?></h2>
<img src="<?php echo $outQR; ?>" alt="QR Code" />

<pre>
<?php 
print_r($package); 
//print_r(get_class_methods($package));
?>
</pre>

<?= $this->endSection() ?>