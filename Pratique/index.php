<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Аптека</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="layout">

    <!-- MENU GAUCHE -->
    <div class="sidebar">

        <h2> Аптека</h2>

        <a href="medicines.php" class="menu-link">
            Лекарства
        </a>

        <a href="employees.php" class="menu-link">
             Сотрудники
        </a>

        <a href="clients.php" class="menu-link">
             Клиенты
        </a>

        <a href="sales.php" class="menu-link">
             Продажи
        </a>

    </div>


    <!-- CONTENU DROITE -->
    <div class="content">

        <h1>Система управления аптекой</h1>

        <div class="menu">

            <div class="card">
                <h2>Лекарства</h2>

                <p>
                    Gestion des médicaments,
                    du stock et des dates
                    d’expiration.
                </p>
            </div>

            <div class="card">
                <h2> Сотрудники</h2>

                <p>
                    Gestion des employés
                    et du personnel.
                </p>
            </div>

            <div class="card">
                <h2> Клиенты</h2>

                <p>
                    Informations des clients
                    et historique.
                </p>
            </div>

            <div class="card">
                <h2>Продажи</h2>

                <p>
                    Gestion des ventes
                    et du stock.
                </p>
            </div>

        </div>

    </div>

</div>

</body>
</html>
