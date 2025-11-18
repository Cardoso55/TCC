<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css"> 
    <link rel="stylesheet" href="/TCC/public/css/dashboard.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
        <div class="main-content">
            <h2 class="title">Dashboard</h2>
            <div class="cards">
                <div class="card">
                    <h2>Card Title 1</h2>
                    <p>This is some content for card 1.</p>
                </div>
                <div class="card">
                    <h2>Card Title 2</h2>
                    <p>This is some content for card 2.</p>
                </div>
                <div class="card">
                    <h2>Card Title 3</h2>
                    <p>This is some content for card 3.</p>
                </div>
                <div class="card">
                    <h2>Card Title 4</h2>
                    <p>This is some content for card 4.</p>
                </div>
            </div>

            <div class="graphics">
                <div class="graphic">
                    <h2>Graphic Section</h2>
                    <p>This section can be used for displaying graphics or charts.</p>
                </div>
                <div class="graphic">
                    <h2>Graphic Section</h2>
                    <p>This section can be used for displaying graphics or charts.</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
