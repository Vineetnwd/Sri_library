<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$id  = (int)($_GET['id'] ?? 0);

if($id <= 0){
    set_flash('danger', 'Invalid expense ID.');
    redirect('center/expenses.php');
}

// Fetch the existing expense
$q_exp = mysqli_query($conn, "SELECT * FROM expenses WHERE id='$id' AND center_id='$cid'");
$expense = mysqli_fetch_assoc($q_exp);

if(!$expense){
    set_flash('danger', 'Expense not found.');
    redirect('center/expenses.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $category_id = (int)$_POST['category_id'];
    $amount      = (float)$_POST['amount'];
    $expense_date= clean($_POST['expense_date']);
    $source      = clean($_POST['source']);
    $remark      = clean($_POST['remark']);

    if(empty($category_id) || empty($amount) || empty($expense_date)){
        set_flash('danger', 'Please fill all required fields.');
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE expenses SET category_id=?, amount=?, expense_date=?, source=?, remark=? WHERE id=? AND center_id=?");
        mysqli_stmt_bind_param($stmt, "idsssii", $category_id, $amount, $expense_date, $source, $remark, $id, $cid);
        if(mysqli_stmt_execute($stmt)){
            set_flash('success', 'Expense updated successfully!');
            redirect('center/expenses.php');
        } else {
            set_flash('danger', 'Error updating expense.');
        }
    }
}

// Fetch categories
$cats = mysqli_query($conn, "SELECT id, name FROM expense_categories WHERE center_id='$cid' ORDER BY name ASC");
$categories = [];
while($c = mysqli_fetch_assoc($cats)) $categories[] = $c;
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">
    <?= get_flash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
            <i class="fa fa-edit me-2 text-primary"></i> Edit Expense
        </h4>
        <a href="expenses.php" class="btn btn-light border btn-sm fw-semibold">
            <i class="fas fa-arrow-left me-1"></i> Back to Expenses
        </a>
    </div>

    <div class="row">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow border-0" style="border-radius:12px;">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:12px;text-transform:uppercase;">Date <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" class="form-control" value="<?= htmlspecialchars($expense['expense_date']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:12px;text-transform:uppercase;">Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control fw-bold text-danger" value="<?= htmlspecialchars($expense['amount']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:12px;text-transform:uppercase;">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach($categories as $c){ ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $expense['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:12px;text-transform:uppercase;">Source</label>
                                <select name="source" class="form-select">
                                    <?php 
                                    $sources = ['Cash', 'Bank', 'UPI', 'Card'];
                                    foreach($sources as $s){ 
                                    ?>
                                        <option value="<?= $s ?>" <?= $s == $expense['source'] ? 'selected' : '' ?>><?= $s == 'Bank' ? 'Bank Transfer' : ($s == 'Card' ? 'Credit/Debit Card' : $s) ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted fw-semibold" style="font-size:12px;text-transform:uppercase;">Remark / Details</label>
                                <textarea name="remark" class="form-control" rows="3"><?= htmlspecialchars($expense['remark']) ?></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary fw-semibold px-4 w-100 py-2">
                                    <i class="fas fa-save me-2"></i>Update Expense
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "layout/footer.php"; ?>
