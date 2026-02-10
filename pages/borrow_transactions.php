<?php
include_once __DIR__ . '/../config.php';

$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}

// Handle Add/Edit Borrow Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $item_id = $conn->real_escape_string($_POST['item_id']);
    $borrower_name = $conn->real_escape_string($_POST['borrower_name']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    $borrow_date = $conn->real_escape_string($_POST['borrow_date']);
    $return_status = isset($_POST['return_status']) ? 1 : 0;
    $return_date = $return_status ? "'" . $conn->real_escape_string($_POST['return_date']) . "'" : "NULL";

    if ($action == 'add') {
        $sql = "INSERT INTO borrow_transactions (item_id, borrower_name, quantity, borrow_date, return_status, return_date)
                VALUES ('$item_id', '$borrower_name', '$quantity', '$borrow_date', '$return_status', $return_date)";
        if ($conn->query($sql) === TRUE) {
            // Decrease item stock
            $conn->query("UPDATE items SET stock = stock - $quantity WHERE id = '$item_id'");
            header("Location: index.php?page=borrow_transactions");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif ($action == 'edit') {
        $id = $conn->real_escape_string($_POST['id']);
        $old_quantity_sql = $conn->query("SELECT quantity FROM borrow_transactions WHERE id = '$id'");
        $old_quantity = $old_quantity_sql->fetch_assoc()['quantity'];
        $stock_change = $quantity - $old_quantity;

        $sql = "UPDATE borrow_transactions SET
                item_id = '$item_id',
                borrower_name = '$borrower_name',
                quantity = '$quantity',
                borrow_date = '$borrow_date',
                return_status = '$return_status',
                return_date = $return_date
                WHERE id = '$id'";
        if ($conn->query($sql) === TRUE) {
            // Adjust item stock based on quantity change
            $conn->query("UPDATE items SET stock = stock - $stock_change WHERE id = '$item_id'");
            header("Location: index.php?page=borrow_transactions");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Handle Delete Borrow Transaction
if (isset($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);
    $transaction_sql = $conn->query("SELECT item_id, quantity, return_status FROM borrow_transactions WHERE id = '$id'");
    $transaction_data = $transaction_sql->fetch_assoc();
    $item_id_to_return = $transaction_data['item_id'];
    $quantity_to_return = $transaction_data['quantity'];
    $return_status_on_delete = $transaction_data['return_status'];

    $sql = "DELETE FROM borrow_transactions WHERE id = '$id'";
    if ($conn->query($sql) === TRUE) {
        // If the item was not returned, increase stock when deleting the transaction
        if (!$return_status_on_delete) {
            $conn->query("UPDATE items SET stock = stock + $quantity_to_return WHERE id = '$item_id_to_return'");
        }
        header("Location: index.php?page=borrow_transactions");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Fetch non-consumable items for dropdown
$sql_non_consumable_items = "SELECT id, name FROM items WHERE type = 'non-consumable'";
$result_non_consumable_items = $conn->query($sql_non_consumable_items);
$non_consumable_items = [];
if ($result_non_consumable_items->num_rows > 0) {
    while ($row = $result_non_consumable_items->fetch_assoc()) {
        $non_consumable_items[] = $row;
    }
}

// Fetch Borrow Transactions
$sql = "SELECT bt.*, i.name as item_name FROM borrow_transactions bt JOIN items i ON bt.item_id = i.id WHERE i.type = 'non-consumable'";
if ($search_query) {
    $sql .= " AND (i.name LIKE '%$search_query%' OR bt.borrower_name LIKE '%$search_query%')";
}
$sql .= " ORDER BY bt.borrow_date DESC";
$result = $conn->query($sql);

$borrow_transactions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $borrow_transactions[] = $row;
    }
}

$edit_transaction = null;
if (isset($_GET['edit'])) {
    $id = $conn->real_escape_string($_GET['edit']);
    $edit_sql = "SELECT * FROM borrow_transactions WHERE id = '$id'";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_transaction = $edit_result->fetch_assoc();
    }
}
?>

<h1>Transaksi Peminjaman Barang Tidak Habis Pakai</h1>

<button class="btn btn-primary" onclick="openModal('borrowModal')">Tambah Transaksi Peminjaman</button>

<div id="borrowModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeModal('borrowModal')">&times;</span>
        <h2><?php echo $edit_transaction ? 'Edit' : 'Tambah'; ?> Transaksi Peminjaman</h2>
        <form action="index.php?page=borrow_transactions" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_transaction ? 'edit' : 'add'; ?>">
            <?php if ($edit_transaction): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_transaction['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="item_id">Nama Barang:</label>
                <select id="item_id" name="item_id" required>
                    <option value="">Pilih Barang</option>
                    <?php foreach ($non_consumable_items as $item): ?>
                        <option value="<?php echo htmlspecialchars($item['id']); ?>"
                            <?php echo ($edit_transaction && $edit_transaction['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="borrower_name">Nama Peminjam:</label>
                <input type="text" id="borrower_name" name="borrower_name" value="<?php echo htmlspecialchars($edit_transaction['borrower_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Jumlah Barang:</label>
                <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($edit_transaction['quantity'] ?? ''); ?>" required min="1">
            </div>

            <div class="form-group">
                <label for="borrow_date">Tanggal Meminjam:</label>
                <input type="date" id="borrow_date" name="borrow_date" value="<?php echo htmlspecialchars($edit_transaction['borrow_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <input type="checkbox" id="return_status" name="return_status" <?php echo ($edit_transaction && $edit_transaction['return_status']) ? 'checked' : ''; ?>>
                <label for="return_status">Sudah Dikembalikan</label>
            </div>

            <div class="form-group" id="return_date_group" style="<?php echo ($edit_transaction && $edit_transaction['return_status']) ? '' : 'display:none;'; ?>">
                <label for="return_date">Tanggal Pengembalian:</label>
                <input type="date" id="return_date" name="return_date" value="<?php echo htmlspecialchars($edit_transaction['return_date'] ?? date('Y-m-d')); ?>">
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $edit_transaction ? 'Update' : 'Tambah'; ?> Transaksi</button>
            <button type="button" class="btn btn-warning" onclick="closeModal('borrowModal')">Batal</button>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        window.location.href = 'index.php?page=borrow_transactions'; // Reload to clear edit state
    }

    document.getElementById('return_status').addEventListener('change', function() {
        var returnDateGroup = document.getElementById('return_date_group');
        if (this.checked) {
            returnDateGroup.style.display = 'block';
        } else {
            returnDateGroup.style.display = 'none';
        }
    });

    // Open modal if edit parameter is present
    <?php if ($edit_transaction): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('borrowModal');
        });
    <?php endif; ?>
</script>

<div class="search-box">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="borrow_transactions">
        <input type="text" name="search" placeholder="Cari nama barang atau peminjam..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Cari</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Nama Peminjam</th>
            <th>Nama Barang</th>
            <th>Jumlah</th>
            <th>Tanggal Meminjam</th>
            <th>Status Pengembalian</th>
            <th>Tanggal Pengembalian</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($borrow_transactions) > 0): ?>
            <?php foreach ($borrow_transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['borrower_name']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                    <td><?php echo $transaction['return_status'] ? 'Sudah Dikembalikan' : 'Belum Dikembalikan'; ?></td>
                    <td><?php echo htmlspecialchars($transaction['return_date'] ?? '-'); ?></td>
                    <td>
                        <a href="index.php?page=borrow_transactions&edit=<?php echo htmlspecialchars($transaction['id']); ?>" class="btn btn-warning">Edit</a>
                        <a href="index.php?page=borrow_transactions&delete=<?php echo htmlspecialchars($transaction['id']); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus transaksi ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Tidak ada data transaksi peminjaman.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
