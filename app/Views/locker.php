<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

<div class="row">
    <div class="col-4"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/add">Generuj nową paczkę</a></div>
    <div class="col-4 text-center"></div>
    <div class="col-4 text-end"><a class="btn btn-secondary" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/packages/list">Przejdz do listy paczek</a></div>
</div>
<h1>Paczkomat: <?php echo encodeHashId($lockerIdHash); ?></h1>
<h6>Diagnostyka: <?php echo $diagnostics->temperature; ?>&deg;C <?php echo $diagnostics->humidity; ?>% <?php echo $diagnostics->voltage; ?>V</h6>
<a class="btn btn-secondary btn-sm" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/settings/edit/<?php echo encodeHashId($lockerIdHash); ?>">Ustawienia</a> 
<a class="btn btn-secondary btn-sm" href="http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/cells/reset/<?php echo encodeHashId($lockerIdHash); ?>">Resetuj skrytki</a>
<div>
<input type="checkbox" id="autoupdate" <?php if(isset($_GET['autoreload'])){ echo "checked"; }?>> <label for="autoupdate">Automatyczne odświeżanie</label>
</div>
<h2>Skrytki</h2>
<table class="table table-dark table-striped table-hover mt-2" style="font-size:0.9em">
    <thead>
        <tr>
            <td>ID</td>
            <td>ID skrytki</td>
            <td>ID paczkomatu</td>
            <td>Rozmiar</td>
            <td>Status</td>
            <td>Oczekiwany status</td>
            <td>ID paczki</td>
            <td>Status paczki</td>
            
            <td>Data włożenia</td>
            <td>Data stworzenia</td>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($cellsAndPackages as $cellAndPackage){
        hashId($cellAndPackage);
        //$packageId = encodeHashId($cellAndPackage->pid);
        echo "<tr>";
            echo "<td>$cellAndPackage->cell_id</td>";
            echo "<td>$cellAndPackage->cell_sort_id</td>";
            echo "<td><a class='btn btn-secondary btn-sm' href='http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/$cellAndPackage->locker_id'>$cellAndPackage->locker_id</a></td>";
            echo "<td>".strtoupper($cellAndPackage->size)."</td>";
            echo "<td>$cellAndPackage->cell_status</td>";

            if($cellAndPackage->cell_status == 'closed' && $cellAndPackage->status == 'insert-ready'){
                echo "<td>open</td>";
            }else if($cellAndPackage->cell_status == 'open' && $cellAndPackage->status == 'insert-ready'){
                echo "<td>closed</td>";
            }else if($cellAndPackage->cell_status == 'closed' && $cellAndPackage->status == 'remove-ready'){
                echo "<td>open</td>";
            }else{
                echo "<td>closed</td>";
            }
            
            
            if($cellAndPackage->id !== null){
                echo "<td><a class='btn btn-sm btn-secondary' href='http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/show/$cellAndPackage->id'>$cellAndPackage->id</a></td>";
            }else{
                echo "<td></td>";
            }

            echo "<td>$cellAndPackage->status</td>";
            echo "<td>$cellAndPackage->inserted_at</td>";
            echo "<td>$cellAndPackage->created_at</td>";
            
        echo "</tr>";
    }
?>
    </tbody>
</table>



<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        
        var checkbox = document.querySelector("#autoupdate");
        var reloadPageTimeout;

        if(checkbox.checked){
            reloadPageTimeout = setTimeout(() => {reloadPage()}, 1000);
        }

        checkbox.addEventListener('change', function (){
            if(this.checked){
                reloadPageTimeout = setTimeout(() => {reloadPage()}, 1000);
            }else{
                clearTimeout(reloadPageTimeout);
            }
        });

        function reloadPage(){
            window.location.replace(location.protocol + '//' + location.host + location.pathname+ "?autoreload=1");
        }
    </script>         
<?= $this->endSection() ?>