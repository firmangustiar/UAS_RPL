<?php
include_once __DIR__ . '/../config.php';

$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}

// Handle Add/Edit Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = $conn->real_escape_string($_POST['name']);
    $type = $conn->real_escape_string($_POST['type']);
    $stock = $conn->real_escape_string($_POST['stock']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $description = $conn->real_escape_string($_POST['description']);

    if ($action == 'add') {
        $sql = "INSERT INTO items (name, type, stock, unit, description)
                VALUES ('$name', '$type', '$stock', '$unit', '$description')";
        if ($conn->query($sql) === TRUE) {
            header("Location: index.php?page=items");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif ($action == 'edit') {
        $id = $conn->real_escape_string($_POST['id']);
        $sql = "UPDATE items SET
                name = '$name',
                type = '$type',
                stock = '$stock',
                unit = '$unit',
                description = '$description'
                WHERE id = '$id'";
        if ($conn->query($sql) === TRUE) {
            header("Location: index.php?page=items");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);
    $sql = "DELETE FROM items WHERE id = '$id'";
    if ($conn->query($sql) === TRUE) {
        header("Location: index.php?page=items");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Fetch Items
$sql = "SELECT * FROM items";
if ($search_query) {
    $sql .= " WHERE name LIKE '%$search_query%' OR description LIKE '%$search_query%'";
}
$sql .= " ORDER BY name ASC";
$result = $conn->query($sql);

$items = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$edit_item = null;
if (isset($_GET['edit'])) {
    $id = $conn->real_escape_string($_GET['edit']);
    $edit_sql = "SELECT * FROM items WHERE id = '$id'";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_item = $edit_result->fetch_assoc();
    }
}
?>

<h1>Manajemen Barang</h1>

<button class="btn btn-primary" onclick="openModal('itemModal')">Tambah Barang Baru</button>

<div id="itemModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeModal('itemModal')">&times;</span>
        <h2><?php echo $edit_item ? 'Edit' : 'Tambah'; ?> Barang</h2>
        <form action="index.php?page=items" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>">
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_item['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Nama Barang:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_item['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="type">Tipe Barang:</label>
                <select id="type" name="type" required>
                    <option value="consumable" <?php echo ($edit_item && $edit_item['type'] == 'consumable') ? 'selected' : ''; ?>>Habis Pakai</option>
                    <option value="non-consumable" <?php echo ($edit_item && $edit_item['type'] == 'non-consumable') ? 'selected' : ''; ?>>Tidak Habis Pakai</option>
                </select>
            </div>

            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($edit_item['stock'] ?? 0); ?>" required min="0">
            </div>

            <div class="form-group">
                <label for="description">Keterangan:</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $edit_item ? 'Update' : 'Tambah'; ?> Barang</button>
            <button type="button" class="btn btn-warning" onclick="closeModal('itemModal')">Batal</button>
        </form>
    </div>
</div>

<div class="search-box">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="items">
        <input type="text" name="search" placeholder="Cari nama barang..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Cari</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Tipe</th>
            <th>Stock</th>
            <th>Keterangan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($items) > 0): ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['type']); ?></td>
                    <td><?php echo htmlspecialchars($item['stock']); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <a href="index.php?page=items&edit=<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-warning">Edit</a>
                        <a href="index.php?page=items&delete=<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus barang ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Tidak ada data barang.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        // Clear form fields or reset if needed
        window.location.href = 'index.php?page=items'; // Reload to clear edit state
    }

    // Open modal if edit parameter is present
    <?php if ($edit_item): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('itemModal');
        });
    <?php endif; ?>
</script>
