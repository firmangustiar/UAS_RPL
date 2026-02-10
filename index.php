<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris Barang</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Inventaris</h2>
        <ul>
            <li><a href="index.php?page=dashboard">Dashboard</a></li>
            <li><a href="index.php?page=items">Manajemen Barang</a></li>
            <li><a href="index.php?page=borrow_transactions">Transaksi Peminjaman</a></li>
            <li><a href="index.php?page=withdrawal_transactions">Pengambilan Barang Habis Pakai</a></li>
        </ul>
    </div>
    <div class="content">
        <?php
        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'items':
                include 'pages/items.php';
                break;
            case 'borrow_transactions':
                include 'pages/borrow_transactions.php';
                break;
            case 'withdrawal_transactions':
                include 'pages/withdrawal_transactions.php';
                break;
            default:
                include 'pages/dashboard.php';
                break;
        }
        ?>
    </div>
</body>
</html>
