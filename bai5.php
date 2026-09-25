<?php

$error = "";

$negative = "";
$positive = "";
$zero = 0;

$countNegative = 0;
$countPositive = 0;
$countZero = 0;


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $numbers = $_POST["numbers"] ?? [];


    // Kiểm tra đủ 10 phần tử
    if (count($numbers) != 10) {

        $error = "Vui lòng nhập đúng 10 phần tử.";
    } else {

        $valid = true;


        // Kiểm tra từng phần tử
        foreach ($numbers as $number) {

            if (
                $number === "" ||
                filter_var($number, FILTER_VALIDATE_INT) === false
            ) {

                $valid = false;
                break;
            }
        }


        if (!$valid) {

            $error = "Tất cả các phần tử phải là số nguyên.";
        } else {


            // Phân loại các phần tử
            foreach ($numbers as $number) {

                $number = (int)$number;


                if ($number < 0) {

                    $negative .= $number . ", ";
                    $countNegative++;
                } elseif ($number > 0) {

                    $positive .= $number . ", ";
                    $countPositive++;
                } else {

                    $countZero++;
                }
            }


            // Xóa dấu phẩy cuối cùng
            $negative = rtrim($negative, ", ");
            $positive = rtrim($positive, ", ");
        }
    }
}

?>


<h1>📊 Bài 5 — Mảng 10 phần tử</h1>


<p>
    Nhập vào một mảng số nguyên gồm 10 phần tử.
</p>


<form method="post" data-action="bai5.php">


    <div class="number-grid">


        <?php for ($i = 0; $i < 10; $i++): ?>


            <div>

                <label>
                    Phần tử <?= $i + 1 ?>:
                </label>


                <input
                    type="number"
                    name="numbers[]"
                    step="1"
                    required
                    placeholder="Nhập số">

            </div>


        <?php endfor; ?>


    </div>


    <button type="submit">
        Xử lý mảng
    </button>


</form>



<?php if ($error !== ""): ?>


    <p class="error">

        ❌ <?= htmlspecialchars($error) ?>

    </p>


<?php endif; ?>



<?php if ($_SERVER["REQUEST_METHOD"] == "POST" && $error === ""): ?>


    <div class="result-box">


        <h2>📋 Kết quả</h2>


        <p>

            <strong>Số phần tử âm:</strong>

            <?= $countNegative ?>

        </p>


        <p>

            <strong>Các phần tử âm:</strong>

            <?= $negative !== ""
                ? htmlspecialchars($negative)
                : "Không có"
            ?>

        </p>


        <p>

            <strong>Số phần tử dương:</strong>

            <?= $countPositive ?>

        </p>


        <p>

            <strong>Các phần tử dương:</strong>

            <?= $positive !== ""
                ? htmlspecialchars($positive)
                : "Không có"
            ?>

        </p>


        <p>

            <strong>Số phần tử bằng 0:</strong>

            <?= $countZero ?>

        </p>


    </div>


<?php endif; ?>