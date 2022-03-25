<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="http://172.16.16.128/codeigniter4/auth-test/public/css/bootstrap.min.css">
    
    <script src="http://172.16.16.128/codeigniter4/auth-test/public/js/bootstrap.min.js" defer></script>

    <link rel="stylesheet" href="http://172.16.16.128/codeigniter4/auth-test/public/css/main.css">
</head>
<body>
    <?= view('Views\_navbar') ?>
    

    <main role="main" class="container">
        <?= $this->renderSection('main') ?>
    </main>

    <div class="before-footer"><?= $this->renderSection('beforeFooter') ?></div>
    <?= view('Views\_footer') ?>
    
    
    <?= $this->renderSection('pageScripts') ?>
</body>
</html>