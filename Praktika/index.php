<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Аптека</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="layout">

    <!-- MENU GAUCHE -->
    <div class="sidebar">

        <h2> ⚕️Аптека</h2>

        <a href="medicines.php" class="menu-link">
            💊 Лекарства
        </a>

        <a href="employees.php" class="menu-link">
            🥼 Сотрудники
        </a>

        <a href="clients.php" class="menu-link">
            👥 Клиенты
        </a>

        <a href="sales.php" class="menu-link">
            🛒 Продажи
        </a>

    </div>


    <!-- CONTENU DROITE -->
    <div class="content">
        <div class="content-header">
            <h1>Система управления аптекой</h1>
        </div>

        <div class="menu">
        <a href="medicines.php" class="card-link">
            <div class="card">
                <h2> 💊 Лекарства</h2>
                <p>
                   Управление лекарственными средствами, 
                    запасами и сроками годности.
                </p>
            </div>
         </a>
            <a href="employees.php" class="card-link">
                <div class="card">
                <h2> 🥼 Сотрудники</h2>
                <p>
                    Управление персоналом.
                </p>
            </div>
            </a>
            
            <a href="clients.php" class="card-link" >
                <div class="card">
                <h2> 👥 Клиенты</h2>
                <p>
                    Информация о клиентах
                    и история покупок.
                </p>
            </div>
            </a>
            
            <a href="sales.php" class="card-link">
                <div class="card">
                <h2> 🛒 Продажи</h2>
                <p>
                    Управление продажами
                    и запасами.
                </p>
            </div>
            </a>
            

        </div>

    </div>

</div>

</body>
</html>
