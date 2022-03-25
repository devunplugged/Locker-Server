<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<div class="row">
    <div class="col-4"></div>
    <div class="col-4 text-center"></div>
    <div class="col-4 text-end"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/packages/list">Przejdz do listy paczek</a></div>
</div>
    <h1>Generuj Paczkę</h1>
    <form action="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/generate" method="POST">
        <div>Rozmiar:</div>
        <select class="form-select" name="size">
            <option value="a">A</option>
            <option value="b">B</option>
        </select>
        <div class="mt-2">ID paczkomatu:</div>
        <input class="form-control mt-2" type="text" name="locker_id" value="06G7JY">

        <button class="btn btn-primary w-100 mt-2" type="submit">Generuj</button>
    </form>

<?= $this->endSection() ?>

