<?php

$error = "";
$result = "";
$inputSeconds = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $input = trim($_POST["seconds"] ?? "");

    // Kiểm tra rỗng
    if ($input === "") {

        $error = "Vui lòng nhập số giây.";

        // Kiểm tra số nguyên
    } elseif (filter_var($input, FILTER_VALIDATE_INT) === false) {

        $error = "Số giây phải là số nguyên.";
    } else {

        $seconds = (int)$input;

        // Kiểm tra số không âm
        if ($seconds < 0) {

            $error = "Số giây không được nhỏ hơn 0.";
        } else {

            // Lưu số giây đã nhập
            $inputSeconds = $seconds;

            // Tính giờ
            $hours = intdiv($seconds, 3600);

            // Tính phút
            $minutes = intdiv($seconds % 3600, 60);

            // Tính giây còn lại
            $secondsRemaining = $seconds % 60;

            // Định dạng HH:MM:SS
            $result = sprintf(
                "%02d:%02d:%02d",
                $hours,
                $minutes,
                $secondsRemaining
            );
        }
    }
}

?>

<h1>⏱️ Bài 6 — Đổi giây thành giờ:phút:giây</h1>

<p>
    Nhập số giây và chuyển đổi sang định dạng
    <strong>giờ:phút:giây</strong>.
</p>

<p>
    <strong>Ví dụ:</strong> 3769 giây → 01:02:49
</p>

<form method="post" data-action="bai6.php">

    <label for="seconds">
        Nhập số giây:
    </label>

    <input
        type="number"
        id="seconds"
        name="seconds"
        min="0"
        step="1"
        required
        placeholder="Ví dụ: 3769">

    <button type="submit">
        Chuyển đổi
    </button>

</form>


<?php if ($error !== ""): ?>

    <p class="error">
        ❌ <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>


<?php if ($result !== ""): ?>

    <div class="result-box">

        <h2>📥 Số giây đã nhập:</h2>

        <div class="input-result">
            <?= htmlspecialchars($inputSeconds) ?> giây
        </div>

        <h2>⏰ Thời gian:</h2>

        <div class="time-result">
            <?= htmlspecialchars($result) ?>
        </div>

    </div>

<?php endif; ?>