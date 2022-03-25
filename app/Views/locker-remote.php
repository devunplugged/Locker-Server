<?= $this->extend('Views\layout') ?>
<?= $this->section('main') ?>

    <input type="hidden" id="lockerId" value="<?php echo $lockerId; ?>">
    <div id="cells-table-container"></div>
    <div id="tasks-table-container"></div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    
        
    <script src="http://172.16.16.128/codeigniter4/auth-test/public/js/token-generation/ajax.js"></script> 
    <script src="http://172.16.16.128/codeigniter4/auth-test/public/js/token-generation/locker-info.js"></script> 

            
<?= $this->endSection() ?>