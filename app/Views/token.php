<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

Token:<br>
<input type="text" value="<?php echo $token; ?>">

<?= $this->endSection() ?>