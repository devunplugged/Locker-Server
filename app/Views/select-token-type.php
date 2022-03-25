<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

    <form action="/dashboard/generate-token" method="POST">
        <select class="form-select" name="company">
            <?php 
                foreach($companies as $company){
                    echo "<option value='$company->id_hash'>$company->name</option>";
                } 
            ?>
        </select>
        
        <select class="form-select mt-2" name="type" id="client-type-select">
            <option value="">Wybierz typ klienta...</option>
            <option value="locker">Paczkomat</option>
            <option value="staff">Pracownik</option>
            <option value="supervisor">Zarządca</option>
            <option value="admin">Admin</option>
        </select>
        <div class="mt-2 d-none" id="clients-container"></div>
        <button class="btn btn-primary w-100 mt-2" type="submit">Generuj</button>
    </form>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
     <script src="http://172.16.16.128/codeigniter4/auth-test/public/js/token-generation/ajax.js"></script>           
     <script src="http://172.16.16.128/codeigniter4/auth-test/public/js/token-generation/clients-by-type.js"></script>           
<?= $this->endSection() ?>