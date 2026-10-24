<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

if (isset($_GET['all'])) {
    // $q = mysqli_query($conn,"SELECT * FROM students WHERE center_id='$cid'");
    $q = mysqli_query($conn,"
        SELECT s.*, f.end_date
        FROM students s
        LEFT JOIN student_fees f ON s.id = f.student_id 
        AND f.status='running'
        WHERE s.center_id='$cid'
    ");
} else {
    if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
        die("No students selected");
    }
    $ids = array_map('intval', $_POST['student_id']);
    $id_list = implode(",", $ids);
    // $q = mysqli_query($conn,"SELECT * FROM students WHERE id IN ($id_list) AND center_id='$cid'");
    $q = mysqli_query($conn,"
        SELECT s.*, f.end_date
        FROM students s
        LEFT JOIN student_fees f ON s.id = f.student_id 
        AND f.status='running'
        WHERE s.id IN ($id_list)
        AND s.center_id='$cid'
    ");
}
$c_info = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM centers WHERE id='$cid'")
);
?>

<style>
body{
    margin:0;
    padding:0;
    font-family:'Segoe UI',sans-serif;
}

.print-area{
    width:100%;
}

/* 5 CARDS IN ONE ROW */
.idout{
    width:19.5%;
    float:left;
    text-align:center;
    margin-bottom:10px;
}

/* CARD SIZE */
.idcard,
.idback{
    width:210px;
    height:350px;
    margin:auto;
    position:relative;
    overflow:hidden;
}

/* FRONT BACKGROUND */
.idcard{
    background:url('../uploads/<?= $c_info['id_front'] ?>') no-repeat center;
    background-size:210px 350px;
    margin:10px;
}

/* BACK BACKGROUND */
.idback{
    background:url('../uploads/<?= $c_info['id_back'] ?>') no-repeat center;
    background-size:210px 350px;
    margin:10px;
}

/* HEADER TEXT */
.card-header{
    position:absolute;
    top:10px;
    width:100%;
    text-align:center;
    color:#fff;
    font-size:14px;
}

/* PHOTO */
.photo-box{
    position:absolute;
    top:90px;
    width:100%;
    text-align:center;
}

.photo-box img{
    width:80px;
    height:90px;
    object-fit:cover;
    border-radius:6px;
}

/* DETAILS */
.details{
    position:absolute;
    top:190px;
    width:100%;
    text-align:center;
    font-size:12px;
}

/* BARCODE */
.barcode{
    position:absolute;
    bottom:15px;
    width:100%;
    text-align:center;
}

/* QR */
.qr{
    position:absolute;
    top:80px;
    width:100%;
    text-align:center;
}

.qr img{
    width:110px;
    height:110px;
}

/* BACK TEXT */
.back-content{
    position:absolute;
    top:200px;
    width:100%;
    text-align:center;
    font-size:12px;
}

/* PAGE BREAK */
.linebr{
    clear:both;
    page-break-after:always;
}
@media print{

    body{
        margin:0;
        padding:0;
        zoom:100%;
    }

    body *{
        visibility:hidden;
    }

    .print-area, .print-area *{
        visibility:visible;
    }

    .print-area{
        position:absolute;
        top:0;
        left:0;
        width:100%;
    }

    /* LOCK CARD SIZE */
    .idcard,
    .idback{
        width:210px !important;
        height:350px !important;
        display:block;
        margin-top:5px;
    }

    .idout{
        width:210px !important;
        float:left;
        margin-right:5px;
    }

    /* PREVENT SCALING */
    html, body{
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @page{
        size:A4 landscape;
        margin:5mm;
    }
}
.card-header{
    position:absolute;
    top:8px;
    width:100%;
    text-align:center;
    color:#fff;
    font-size:12px;
}

.photo-box{
    position:absolute;
    top:100px; /* increased from 90 */
    width:100%;
    text-align:center;
}
</style>

<div class="print-area">

<?php 
$i = 1;

while($row = mysqli_fetch_assoc($q)){
?>

<div class="idout">

    <div class="idcard">
        <div class="card-header">

            <?php if(!empty($c_info['logo'])){ ?>
                <img src="../uploads/<?= htmlspecialchars($c_info['logo']) ?>"
                     style="width:60px; display:block; margin-top:-9px; margin-left:-9px;">
            <?php } ?>
        
            <b style="font-size:12px;">
                <?//= htmlspecialchars($c_info['center_name'] ?? '') ?>
            </b>
        
        </div>

        <div class="photo-box">
            <img src="../uploads/<?= htmlspecialchars($row['photo']) ?>">
        </div>

        <div class="details">
            <b><?= htmlspecialchars($row['name']) ?></b><br>
            ID : <?= htmlspecialchars($row['registration_no']) ?><br>
            Mob. : <?= htmlspecialchars($row['mobile']) ?><br>
            DOJ : <?= htmlspecialchars($row['joining_date']) ?><br>
            Address: <?= htmlspecialchars($row['address']) ?>
        </div>

        <div class="barcode">
            <!--<img src="barcode.php?text=<?= urlencode($row['registration_no']) ?>" height="30">-->
            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?=$row['registration_no']?>" style='height:45px'>
        </div>
    </div>

    <div class="idback">
        <div class="card-header">
            <b>SHREE LIBRARY</b><br>
            (Self Study Centre)
        </div>

        <div class="qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($row['registration_no']) ?>">
        </div>

        <div class="back-content">
            ID: <?= htmlspecialchars($row['registration_no']) ?><br>
            <?= date('d/m/Y') ?><br>
            <?//= date('d/m/Y', strtotime('+1 year')) ?>
            <?= !empty($row['end_date']) ? date('d/m/Y', strtotime($row['end_date'])) : '' ?>
            <br><br>
            Authorized Signatory<br>
            <?php if(!empty($c_info['dir_sign'])){ ?>
                <img src="../uploads/<?= htmlspecialchars($c_info['dir_sign']) ?>" 
                     style="height:50px;object-fit:contain;">
            <?php } else { ?>
                <div style="height:50px;"></div>
            <?php } ?>
        </div>
    </div>

</div>

<?php 
$i++;

if($i % 5 == 1){
    echo "<div class='linebr'></div>";
}

} 
?>

</div>

<script>
window.print();
</script>