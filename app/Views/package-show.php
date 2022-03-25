<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<div class="row">
    <div class="col-4"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/add">Generuj nową paczkę</a></div>
    <div class="col-4 text-center"></div>
    <div class="col-4 text-end"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/packages/list">Przejdz do listy paczek</a></div>
</div>
<?php hashId($package); ?>
<h1>Paczka (<?php echo $package->id; ?>):</h1>

<h2>Kod włożenia do paczkomatu: <?php echo $package->code; ?></h2>
<img src="<?php echo $qr['in']; ?>" alt="QR Code" />
<div class="code39 font-size-barcode mt-4" style="color:black;"><?php echo $package->code; ?></div>
<h2>Kod wyjecia z paczkomatu: <?php echo $package->recipient_code; ?></h2>
<?php if(isset($qr['out'])){ ?>
<img src="<?php echo $qr['out']; ?>" alt="QR Code" />
<?php } ?>
<h2>Dane dodatkowe:</h2>
<p>Paczkomat: <?php echo $package->locker_id; ?></p>
<p>ID skrytki: <?php echo $package->cell_sort_id; ?></p>
<p>Data umieszczenia: <?php echo $package->inserted_at; ?></p>
<p>Data wyciągnięcia: <?php echo $package->removed_at; ?></p>
<p>Data utworzenia: <?php echo $package->created_at; ?></p>
<p>Data aktualizacji: <?php echo $package->updated_at; ?></p>

<h2>Obiekt paczki:</h2>
<pre>
<?php print_r($package); ?>
</pre>

<?= $this->endSection() ?>