<?php

class PERSON
{
    protected $hoTen;
    protected $ngaySinh;
    protected $queQuan;

    public function __construct($hoTen, $ngaySinh, $queQuan)
    {
        $this->hoTen = $hoTen;
        $this->ngaySinh = $ngaySinh;
        $this->queQuan = $queQuan;
    }

    public function getThongTin()
    {
        return [
            "Họ tên" => $this->hoTen,
            "Ngày sinh" => $this->ngaySinh,
            "Quê quán" => $this->queQuan
        ];
    }
}


class SINHVIEN extends PERSON
{
    private $lop;

    public function __construct(
        $hoTen,
        $ngaySinh,
        $queQuan,
        $lop
    ) {
        parent::__construct(
            $hoTen,
            $ngaySinh,
            $queQuan
        );

        $this->lop = $lop;
    }

    public function getThongTin()
    {
        $thongTin = parent::getThongTin();

        $thongTin["Lớp"] = $this->lop;

        return $thongTin;
    }
}


$error = "";
$sinhVien = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $hoTen = trim($_POST["hoTen"] ?? "");
    $ngaySinh = trim($_POST["ngaySinh"] ?? "");
    $queQuan = trim($_POST["queQuan"] ?? "");
    $lop = trim($_POST["lop"] ?? "");


    // Validate họ tên
    if ($hoTen === "") {

        $error = "Vui lòng nhập họ tên.";

        // Validate ngày sinh
    } elseif ($ngaySinh === "") {

        $error = "Vui lòng nhập ngày sinh.";

        // Validate quê quán
    } elseif ($queQuan === "") {

        $error = "Vui lòng nhập quê quán.";

        // Validate lớp
    } elseif ($lop === "") {

        $error = "Vui lòng nhập lớp.";
    } else {

        $sinhVien = new SINHVIEN(
            $hoTen,
            $ngaySinh,
            $queQuan,
            $lop
        );
    }
}
?>

<h1>👨‍🎓 Bài 7 — Thông tin sinh viên</h1>

<form method="post">

    <label>Họ tên:</label>

    <input
        type="text"
        name="hoTen"
        required
        maxlength="100"
        placeholder="Nhập họ tên">


    <label>Ngày sinh:</label>

    <input
        type="date"
        name="ngaySinh"
        required>


    <label>Quê quán:</label>

    <input
        type="text"
        name="queQuan"
        required
        maxlength="100"
        placeholder="Nhập quê quán">


    <label>Lớp:</label>

    <input
        type="text"
        name="lop"
        required
        maxlength="50"
        placeholder="Nhập lớp">


    <button type="submit">
        Hiển thị thông tin
    </button>

</form>


<?php if ($error): ?>

    <p class="error">
        <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>


<?php if ($sinhVien): ?>

    <?php $thongTin = $sinhVien->getThongTin(); ?>

    <div class="student-result">

        <h2>👤 THÔNG TIN CÁ NHÂN SINH VIÊN</h2>

        <?php foreach ($thongTin as $key => $value): ?>

            <div class="info-row">

                <strong>
                    <?= htmlspecialchars($key) ?>:
                </strong>

                <span>
                    <?= htmlspecialchars($value) ?>
                </span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>