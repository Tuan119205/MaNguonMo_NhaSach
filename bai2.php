<?php

// ===============================
// HÀM KIỂM TRA SỐ HOÀN HẢO
// ===============================

function laSoHoanHao($n)
{
    if ($n <= 1) {
        return false;
    }

    $tong = 0;

    for ($i = 1; $i <= $n / 2; $i++) {

        if ($n % $i == 0) {
            $tong += $i;
        }
    }

    return $tong == $n;
}


// ===============================
// XỬ LÝ DỮ LIỆU
// ===============================

$error = "";
$result = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $input = trim($_POST["number"] ?? "");


    // Không được để trống
    if ($input === "") {

        $error = "Vui lòng nhập số.";
    }

    // Phải là số nguyên
    elseif (filter_var($input, FILTER_VALIDATE_INT) === false) {

        $error = "Vui lòng nhập số nguyên.";
    } else {

        $number = (int)$input;


        // Phải lớn hơn 0
        if ($number <= 0) {

            $error = "Vui lòng nhập số nguyên dương.";
        }

        // Kiểm tra số hoàn hảo
        elseif (laSoHoanHao($number)) {

            $result = $number . " là số hoàn hảo.";
        } else {

            $result = $number . " không phải là số hoàn hảo.";
        }
    }
}

?>


<!-- ===============================
     GIAO DIỆN BÀI 2
================================ -->

<h1>🎯 Bài 2 — Kiểm tra số hoàn hảo</h1>

<p>
    Nhập một số nguyên dương để kiểm tra số đó có phải
    là số hoàn hảo hay không.
</p>


<form method="post" data-action="bai2.php">

    <label for="number">
        Nhập số nguyên dương:
    </label>

    <input
        type="number"
        id="number"
        name="number"
        min="1"
        step="1"
        required
        placeholder="Ví dụ: 6">

    <button type="submit">
        Kiểm tra
    </button>

</form>


<?php if ($error !== ""): ?>

    <div class="error">

        ❌ <?php echo htmlspecialchars($error); ?>

    </div>

<?php endif; ?>


<?php if ($result !== ""): ?>

    <div class="success">

        ✅ <?php echo htmlspecialchars($result); ?>

    </div>

<?php endif; ?>