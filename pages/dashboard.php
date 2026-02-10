<?php
include_once __DIR__ . '/../config.php';

// Fetch all items
$sql_items = "SELECT * FROM items";
$result_items = $conn->query($sql_items);

$items = [];
if ($result_items->num_rows > 0) {
    while ($row = $result_items->fetch_assoc()) {
        $items[] = $row;
    }
}

// Fetch consumable items with stock 0
$sql_out_of_stock = "SELECT * FROM items WHERE type = 'consumable' AND stock = 0";
$result_out_of_stock = $conn->query($sql_out_of_stock);

$out_of_stock_items = [];
if ($result_out_of_stock->num_rows > 0) {
    while ($row = $result_out_of_stock->fetch_assoc()) {
        $out_of_stock_items[] = $row;
    }
}
?>

<h1>Dashboard</h1>

<div class="dashboard-info">
    <h2>Informasi Semua Stock Barang</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Tipe</th>
                <th>Stock</th>
                <th>Tanggal Input</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['type']); ?></td>
                        <td><?php echo htmlspecialchars($item['stock']); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($item['created_at']))); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Tidak ada data barang.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dashboard-info">
    <h2>Barang Habis Pakai (Stock 0)</h2>
    <?php if (count($out_of_stock_items) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Unit</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($out_of_stock_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Tidak ada barang habis pakai yang stocknya habis.</p>
    <?php endif; ?>
</div>
