<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<div class="alert alert-danger">
    Niepoprawny kod paczki: <?php echo $code; ?>
</div>


<?= $this->endSection() ?>