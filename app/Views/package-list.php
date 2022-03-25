<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<div class="row">
    <div class="col-4"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/add">Generuj nową paczkę</a></div>
    <div class="col-4 text-center"></div>
    <div class="col-4 text-end"></div>
</div>
<h1>Lista Paczek</h1>
<table class="table table-dark table-striped table-hover mt-2" style="font-size:0.9em">
    <thead>
        <tr>
            <td>ID</td>
            <td>Kod (nadawcy)</td>
            <td>Kod (odbiorcy)</td>
            <td>Paczkomat</td>
            <td>Skrytka</td>
            <td>Status</td>
            <td>Włożono</td>
            <td>Wyjęto</td>
            <td>Utworzono</td>
            <td>Aktualizowano</td>
            <td>Akcja</td>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($packages as $package){

        echo "<tr>";
            echo "<td>".encodeHashId($package->id)." ($package->id)</td>";
            echo "<td>$package->code</td>";
            echo "<td>$package->recipient_code</td>";
            echo "<td><a class='btn btn-secondary btn-sm' href='http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/".encodeHashId($package->locker_id)."'>".encodeHashId($package->locker_id)."</a></td>";
            echo "<td>$package->cell_sort_id</td>";
            echo "<td>$package->status</td>";
            echo "<td>$package->inserted_at</td>";
            echo "<td>$package->removed_at</td>";
            echo "<td>$package->created_at</td>";
            echo "<td>$package->updated_at</td>";
            echo "<td><a class='btn btn-sm btn-primary' href='http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/show/".encodeHashId($package->id)."'>Pokaż</a></td>";
        echo "</tr>";
    }
?>
    </tbody>
</table>


<?= $this->endSection() ?>