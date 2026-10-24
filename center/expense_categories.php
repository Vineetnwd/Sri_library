<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

// Handle Form Submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $name = clean($_POST['name']);
    
    if ($_POST['action'] == 'add') {
        mysqli_query($conn, "INSERT INTO expense_categories (center_id, name) VALUES ('$cid', '$name')");
        set_flash('success', 'Category added successfully!');
    } elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE expense_categories SET name='$name' WHERE id='$id' AND center_id='$cid'");
        set_flash('success', 'Category updated successfully!');
    }
    redirect('center/expense_categories.php');
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if category is used
    $check = mysqli_query($conn, "SELECT COUNT(*) as c FROM expenses WHERE category_id='$id' AND center_id='$cid'");
    $usage = mysqli_fetch_assoc($check)['c'];
    
    if ($usage > 0) {
        set_flash('danger', "Cannot delete category! It is being used in $usage expense(s).");
    } else {
        mysqli_query($conn, "DELETE FROM expense_categories WHERE id='$id' AND center_id='$cid'");
        set_flash('success', 'Category deleted successfully!');
    }
    redirect('center/expense_categories.php');
}

// Fetch categories with usage count
$q = mysqli_query($conn, "
    SELECT c.*, 
           (SELECT COUNT(id) FROM expenses WHERE category_id=c.id) as expense_count,
           (SELECT IFNULL(SUM(amount), 0) FROM expenses WHERE category_id=c.id) as total_spent
    FROM expense_categories c 
    WHERE c.center_id='$cid' 
    ORDER BY c.name ASC
");

$categories = [];
while ($r = mysqli_fetch_assoc($q)) {
    $categories[] = $r;
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">
    <?= get_flash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
            <i class="fa fa-tags me-2 text-primary"></i> Expense Categories
        </h4>
        <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openAddModal()">
            <i class="fas fa-plus me-1"></i> Add Category
        </button>
    </div>

    <div class="card shadow border-0" style="border-radius:12px;">
        <div class="card-header bg-white border-bottom py-3">
            <span class="fw-semibold"><i class="fa fa-list me-2 text-primary"></i>Manage Categories</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase text-muted" style="font-size:11px;">Category Name</th>
                        <th class="text-uppercase text-muted text-center" style="font-size:11px;">Usage Count</th>
                        <th class="text-uppercase text-muted text-end" style="font-size:11px;">Total Spent</th>
                        <th class="text-uppercase text-muted text-end pe-4" style="font-size:11px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($categories)){ ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <div class="mb-2"><i class="fas fa-tags fa-2x opacity-25"></i></div>
                                No expense categories found.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach($categories as $c){ ?>
                        <tr>
                            <td class="ps-4">
                                <strong class="text-dark"><?= htmlspecialchars($c['name']) ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:11px;">
                                    <?= $c['expense_count'] ?> expenses
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-danger" style="font-size:13px;">
                                ₹ <?= number_format($c['total_spent'], 0) ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border text-primary fw-semibold" style="font-size:12px;" 
                                        onclick="openEditModal(<?= $c['id'] ?>, '<?= addslashes($c['name']) ?>')">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <?php if($c['expense_count'] == 0){ ?>
                                    <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-danger fw-semibold ms-1" style="font-size:12px;" onclick="return confirm('Delete this category?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php } else { ?>
                                    <button class="btn btn-sm btn-light border text-muted ms-1" style="font-size:12px;" disabled title="Cannot delete category in use">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0" style="background:#f9fafb;">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body py-4" style="background:#f9fafb;">
                    <input type="hidden" name="action" id="actionInput" value="add">
                    <input type="hidden" name="id" id="idInput" value="">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold" style="font-size:12px;text-transform:uppercase;">Category Name</label>
                        <input type="text" name="name" id="nameInput" class="form-control" required placeholder="e.g. Rent, Electricity">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white">
                    <button type="button" class="btn btn-sm btn-light border fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Category';
    document.getElementById('actionInput').value = 'add';
    document.getElementById('idInput').value = '';
    document.getElementById('nameInput').value = '';
}

function openEditModal(id, name) {
    document.getElementById('modalTitle').innerText = 'Edit Category';
    document.getElementById('actionInput').value = 'edit';
    document.getElementById('idInput').value = id;
    document.getElementById('nameInput').value = name;
    var myModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    myModal.show();
}
</script>

<?php include "layout/footer.php"; ?>
