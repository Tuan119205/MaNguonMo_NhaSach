<?php

// =====================================================
// HÀM NHÂN 2 SỐ LỚN
// Kết quả trả về dạng STRING
// =====================================================

function nhanChuoi($number, $n)
{
    $carry = 0;
    $result = "";

    // Duyệt từ phải sang trái
    for ($i = strlen($number) - 1; $i >= 0; $i--) {

        $digit = (int)$number[$i];

        $temp = $digit * $n + $carry;

        $result = ($temp % 10) . $result;

        $carry = intdiv($temp, 10);
    }

    // Xử lý phần nhớ
    while ($carry > 0) {

        $result = ($carry % 10) . $result;

        $carry = intdiv($carry, 10);
    }

    return $result;
}


// =====================================================
// HÀM TÍNH GIAI THỪA
// Kết quả trả về STRING
// =====================================================

function giaiThua($n)
{
    $result = "1";

    for ($i = 2; $i <= $n; $i++) {

        $result = nhanChuoi($result, $i);
    }

    return $result;
}


// =====================================================
// XỬ LÝ DỮ LIỆU
// =====================================================

$error = "";
$result = "";
$n = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $input = trim($_POST["number"] ?? "");


    // Kiểm tra rỗng
    if ($input === "") {

        $error = "Vui lòng nhập n.";
    }


    // Kiểm tra có phải số nguyên hay không
    elseif (filter_var($input, FILTER_VALIDATE_INT) === false) {

        $error = "n phải là số nguyên.";
    } else {

        $n = (int)$input;


        // Không cho số âm
        if ($n < 0) {

            $error = "n phải lớn hơn hoặc bằng 0.";
        } else {

            // Tính giai thừa
            // Kết quả là STRING
            $result = giaiThua($n);
        }
    }
}

?>


<!-- =====================================================
     GIAO DIỆN
===================================================== -->

<h1>🧮 Bài 3 — Tính giai thừa</h1>

<p>
    Nhập một số nguyên n để tính giai thừa.
</p>

<p>
    <strong>Công thức:</strong>
    n! = n × (n - 1)!
</p>


<!-- =====================================================
     FORM
===================================================== -->

<form method="post" data-action="bai3.php">

    <label for="number">
        Nhập n:
    </label>

    <input
        type="number"
        id="number"
        name="number"
        min="0"
        step="1"
        required
        placeholder="Ví dụ: 5">

    <button type="submit">
        Tính giai thừa
    </button>

</form>


<!-- =====================================================
     HIỂN THỊ LỖI
===================================================== -->

<?php if ($error !== ""): ?>

    <p class="error">

        ❌ <?= htmlspecialchars($error) ?>

    </p>

<?php endif; ?>


<!-- =====================================================
     HIỂN THỊ KẾT QUẢ
===================================================== -->

<?php if ($result !== ""): ?>

    <div class="result-box">

        <h2>✅ Kết quả:</h2>

        <div class="factorial-result">

            <?= htmlspecialchars($n . "! = " . $result) ?>

        </div>

    </div>

<?php endif; ?>