<?php
include_once __DIR__ . '/../config.php';

$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}

// Handle Add/Edit Withdrawal Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $item_id = $conn->real_escape_string($_POST['item_id']);
    $recipient_name = $conn->real_escape_string($_POST['recipient_name']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    $withdrawal_date = $conn->real_escape_string($_POST['withdrawal_date']);
    $remarks = $conn->real_escape_string($_POST['remarks']);

    if ($action == 'add') {
        $sql = "INSERT INTO withdrawal_transactions (item_id, recipient_name, quantity, withdrawal_date, remarks)
                VALUES ('$item_id', '$recipient_name', '$quantity', '$withdrawal_date', '$remarks')";
        if ($conn->query($sql) === TRUE) {
            // Decrease item stock
            $conn->query("UPDATE items SET stock = stock - $quantity WHERE id = '$item_id'");
            header("Location: index.php?page=withdrawal_transactions");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif ($action == 'edit') {
        $id = $conn->real_escape_string($_POST['id']);
        $old_quantity_sql = $conn->query("SELECT quantity FROM withdrawal_transactions WHERE id = '$id'");
        $old_quantity = $old_quantity_sql->fetch_assoc()['quantity'];
        $stock_change = $quantity - $old_quantity;

        $sql = "UPDATE withdrawal_transactions SET
                item_id = '$item_id',
                recipient_name = '$recipient_name',
                quantity = '$quantity',
                withdrawal_date = '$withdrawal_date',
                remarks = '$remarks'
                WHERE id = '$id'";
        if ($conn->query($sql) === TRUE) {
            // Adjust item stock based on quantity change
            $conn->query("UPDATE items SET stock = stock - $stock_change WHERE id = '$item_id'");
            header("Location: index.php?page=withdrawal_transactions");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Handle Delete Withdrawal Transaction
if (isset($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);
    $transaction_sql = $conn->query("SELECT item_id, quantity FROM withdrawal_transactions WHERE id = '$id'");
    $transaction_data = $transaction_sql->fetch_assoc();
    $item_id_to_return = $transaction_data['item_id'];
    $quantity_to_return = $transaction_data['quantity'];

    $sql = "DELETE FROM withdrawal_transactions WHERE id = '$id'";
    if ($conn->query($sql) === TRUE) {
        // Increase item stock when deleting the transaction
        $conn->query("UPDATE items SET stock = stock + $quantity_to_return WHERE id = '$item_id_to_return'");
        header("Location: index.php?page=withdrawal_transactions");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Fetch consumable items for dropdown
$sql_consumable_items = "SELECT id, name, stock FROM items WHERE type = 'consumable'";
$result_consumable_items = $conn->query($sql_consumable_items);
$consumable_items = [];
if ($result_consumable_items->num_rows > 0) {
    while ($row = $result_consumable_items->fetch_assoc()) {
        $consumable_items[] = $row;
    }
}

// Fetch Withdrawal Transactions
$sql = "SELECT wt.*, i.name as item_name, i.stock as item_stock FROM withdrawal_transactions wt JOIN items i ON wt.item_id = i.id WHERE i.type = 'consumable'";
if ($search_query) {
    $sql .= " AND (i.name LIKE '%$search_query%' OR wt.recipient_name LIKE '%$search_query%')";
}
$sql .= " ORDER BY wt.withdrawal_date DESC";
$result = $conn->query($sql);

$withdrawal_transactions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $withdrawal_transactions[] = $row;
    }
}

$edit_transaction = null;
if (isset($_GET['edit'])) {
    $id = $conn->real_escape_string($_GET['edit']);
    $edit_sql = "SELECT * FROM withdrawal_transactions WHERE id = '$id'";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_transaction = $edit_result->fetch_assoc();
    }
}
?>

<h1>Pengambilan Barang Habis Pakai</h1>

<button class="btn btn-primary" onclick="openModal('withdrawalModal')">Tambah Pengambilan Barang</button>

<div id="withdrawalModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeModal('withdrawalModal')">&times;</span>
        <h2><?php echo $edit_transaction ? 'Edit' : 'Tambah'; ?> Pengambilan Barang</h2>
        <form action="index.php?page=withdrawal_transactions" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_transaction ? 'edit' : 'add'; ?>">
            <?php if ($edit_transaction): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_transaction['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="item_id">Nama Barang:</label>
                <select id="item_id" name="item_id" required>
                    <option value="">Pilih Barang</option>
                    <?php foreach ($consumable_items as $item): ?>
                        <option value="<?php echo htmlspecialchars($item['id']); ?>"
                            <?php echo ($edit_transaction && $edit_transaction['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['name']); ?> (Stock: <?php echo htmlspecialchars($item['stock']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="recipient_name">Nama Pengambil Barang:</label>
                <input type="text" id="recipient_name" name="recipient_name" value="<?php echo htmlspecialchars($edit_transaction['recipient_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Jumlah Barang:</label>
                <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($edit_transaction['quantity'] ?? ''); ?>" required min="1">
            </div>

            <div class="form-group">
                <label for="withdrawal_date">Tanggal Ambil:</label>
                <input type="date" id="withdrawal_date" name="withdrawal_date" value="<?php echo htmlspecialchars($edit_transaction['withdrawal_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <label for="remarks">Keterangan:</label>
                <textarea id="remarks" name="remarks"><?php echo htmlspecialchars($edit_transaction['remarks'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $edit_transaction ? 'Update' : 'Tambah'; ?> Pengambilan</button>
            <button type="button" class="btn btn-warning" onclick="closeModal('withdrawalModal')">Batal</button>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        window.location.href = 'index.php?page=withdrawal_transactions'; // Reload to clear edit state
    }

    // Open modal if edit parameter is present
    <?php if ($edit_transaction): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('withdrawalModal');
        });
    <?php endif; ?>
</script>

<div class="search-box">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="withdrawal_transactions">
        <input type="text" name="search" placeholder="Cari nama barang atau pengambil..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Cari</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Stock Barang Saat Pengambilan</th>
            <th>Nama Pengambil Barang</th>
            <th>Jumlah</th>
            <th>Tanggal Ambil</th>
            <th>Keterangan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($withdrawal_transactions) > 0): ?>
            <?php foreach ($withdrawal_transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['item_stock']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['recipient_name']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['withdrawal_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['remarks']); ?></td>
                    <td>
                        <a href="index.php?page=withdrawal_transactions&edit=<?php echo htmlspecialchars($transaction['id']); ?>" class="btn btn-warning">Edit</a>
                        <a href="index.php?page=withdrawal_transactions&delete=<?php echo htmlspecialchars($transaction['id']); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus transaksi ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Tidak ada data transaksi pengambilan barang habis pakai.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
