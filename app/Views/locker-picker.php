<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>


    <select id="pick-locker-select">
    <?php 
        foreach($lockers as $locker){

            echo '<option value="'.encodeHashId($locker->id).'">'.$locker->name.'</option>';

        }
    ?>
    </select>

    <button id="pick-locker-button">Steruj</button>


<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        
        var pickButton = document.getElementById('pick-locker-button');
        var pickSelect = document.getElementById('pick-locker-select');

        pickButton.addEventListener('click', () => {
            window.location.replace("http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/remote/" + pickSelect.value);
        });

    </script>         
<?= $this->endSection() ?>