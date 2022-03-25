<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>
<div class="row">
    <div class="col-4"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/add">Generuj nową paczkę</a></div>
    <div class="col-4 text-center"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/<?php echo $lockerId; ?>">Ten paczkomat</a></div>
    <div class="col-4 text-end"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/packages/list">Przejdz do listy paczek</a></div>
</div>

<?php
if(isset($_GET['saved'])){
?>
<div class="alert alert-success mt-4">Zapisano zmiany</div>
<?php
}
?>
<form class="mt-4" action="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/settings/save" method="POST">

    <input type="hidden" name="locker_id" value="<?php echo $lockerId; ?>">

    <div class="row">
        <div class="col-6">
            request interval:
        </div>
        <div class="col-6">
            <input class="form-control" type="number" name="request_interval" value="<?php echo $details['request_interval']; ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-2 form-control">Zapisz</button>
</form>


<?= $this->endSection() ?>