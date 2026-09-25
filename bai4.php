<?php

$error = "";
$result = "";
$n = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $input = trim($_POST["number"] ?? "");

    // Kiểm tra rỗng
    if ($input === "") {

        $error = "Vui lòng nhập n.";
    }
    // Kiểm tra số nguyên
    elseif (filter_var($input, FILTER_VALIDATE_INT) === false) {

        $error = "n phải là số nguyên.";
    } else {

        $n = (int)$input;

        // Kiểm tra số nguyên dương
        if ($n <= 0) {

            $error = "n phải là số nguyên dương.";
        } else {

            // Tìm các ước của n
            for ($i = 1; $i <= $n; $i++) {

                if ($n % $i == 0) {

                    $result .= $i . " ";
                }
            }
        }
    }
}

?>

<h1>🔢 Bài 4 — Liệt kê các ước số</h1>

<p>
    Nhập một số nguyên dương n để liệt kê tất cả các ước của n.
</p>

<form method="post" data-action="bai4.php">

    <label for="number">
        Nhập số nguyên dương n:
    </label>

    <input
        type="number"
        id="number"
        name="number"
        min="1"
        step="1"
        required
        placeholder="Ví dụ: 12">

    <button type="submit">
        Liệt kê ước
    </button>

</form>


<?php if ($error !== ""): ?>

    <p class="error">
        ❌ <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>


<?php if ($result !== ""): ?>

    <div class="result-box">

        <h2>
            Các ước của <?= htmlspecialchars($n) ?>:
        </h2>

        <p class="number-list">
            <?= htmlspecialchars(trim($result)) ?>
        </p>

    </div>

<?php endif; ?>