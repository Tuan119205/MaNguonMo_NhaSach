<?php

$numbers = [];
$message = "";
$error = "";
$stopped = false;

// Lấy dữ liệu từ POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Nhập lại từ đầu
    if (isset($_POST["reset"])) {
        $numbers = [];
        $message = "Đã bắt đầu lại từ đầu.";
        $stopped = false;
    }

    // Nhập số
    elseif (isset($_POST["number"])) {

        $input = trim($_POST["number"]);

        // Kiểm tra dữ liệu
        if ($input === "") {
            $error = "Vui lòng nhập một số.";
        } elseif (!is_numeric($input)) {
            $error = "Dữ liệu nhập phải là một số.";
        } else {

            $number = (float)$input;

            // Lấy danh sách số đã nhập từ form
            $numbers = isset($_POST["numbers"])
                ? json_decode($_POST["numbers"], true)
                : [];

            // Thêm số mới
            $numbers[] = $number;

            // Nếu nhập 0 thì dừng
            if ($number == 0) {
                $message = "Đã nhập số 0. Chương trình dừng.";
                $stopped = true;
            } else {
                $message = "Đã nhập số " . $number .
                    ". Hãy nhập tiếp hoặc nhập 0 để dừng.";
            }
        }
    }
}
?>

<h1>📝 Bài 1 — Nhập số đến khi nhập 0 thì dừng</h1>


<?php if (!$stopped): ?>

    <form method="post">

        <label>Nhập số:</label>

        <input
            type="number"
            name="number"
            step="any"
            required
            placeholder="Nhập số, nhập 0 để dừng">

        <!-- Lưu danh sách số đã nhập -->
        <input
            type="hidden"
            name="numbers"
            value="<?= htmlspecialchars(json_encode($numbers)) ?>">

        <button type="submit">
            Nhập số
        </button>

    </form>

<?php endif; ?>


<?php if ($error): ?>

    <p class="error">
        <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>


<?php if ($message): ?>

    <p class="success">
        <?= htmlspecialchars($message) ?>
    </p>

<?php endif; ?>


<?php if ($stopped): ?>

    <p class="success">
        ⛔ Đã nhập số 0. Chương trình đã dừng, không thể nhập thêm.
    </p>

    <form method="post">

        <button type="submit" name="reset" value="1">
            Nhập lại
        </button>

    </form>

<?php endif; ?>


<?php if (!empty($numbers)): ?>

    <h2>Các số đã nhập:</h2>

    <ul>

        <?php foreach ($numbers as $number): ?>

            <li>
                <?= htmlspecialchars($number) ?>
            </li>

        <?php endforeach; ?>

    </ul>

<?php endif; ?>