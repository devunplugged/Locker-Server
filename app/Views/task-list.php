<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<div class="row">
    <div class="col-4"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/add">Generuj nową paczkę</a></div>
    <div class="col-4 text-center"></div>
    <div class="col-4 text-end"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/packages/list">Przejdz do listy paczek</a></div>
</div>
<h1>Lista Zadań</h1>
<table class="table table-dark table-striped table-hover mt-2" style="font-size:0.9em">
    <thead>
        <tr>
            <td>ID</td>
            <td>Typ</td>
            <td>Zadanie</td>
            <td>Paczkomat</td>
            <td>Skrytka</td>
            <td>Paczka</td>
            <td>Opis</td>
            <td>Status</td>
            <td>Wykonano</td>
            <td>Utworzono</td>
            <td>Aktualizowano</td>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($tasks as $task){
        hashId($task);
        echo "<tr>";
            echo "<td>$task->id</td>";
            echo "<td>$task->type</td>";
            echo "<td>$task->task</td>";
            echo "<td><a class='btn btn-secondary btn-sm' href='http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/".$task->client_id."'>$task->client_id</a></td>";
            echo "<td>$task->value</td>";
            echo "<td>$task->package_id</td>";
            echo "<td>$task->description</td>";
            echo "<td>$task->status</td>";
            echo "<td>$task->done_at</td>";
            echo "<td>$task->created_at</td>";
            echo "<td>$task->updated_at</td>";
        echo "</tr>";
    }
?>
    </tbody>
</table>


<?= $this->endSection() ?>