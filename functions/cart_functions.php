<?php
	/*
		loop through array of $_SESSION['cart'][book_isbn] => number
		get isbn => take from database => take book price
		price * number (quantity)
		return sum of price
	*/
	function total_price($cart){
		$price = 0.0;
		if(is_array($cart)){
		  	foreach($cart as $isbn => $qty){
		  		$bookprice = getbookprice($isbn);
		  		if($bookprice){
		  			$price += $bookprice * $qty;
		  		}
		  	}
		}
		return $price;
	}

	/*
		loop through array of $_SESSION['cart'][book_isbn] => number
		$_SESSION['cart'] is associative array which is [book_isbn] => number of books for each book_isbn
		calculate sum of books
	*/
		function total_items($cart){
		$items = 0;
		if(is_array($cart)){
		foreach($cart as $isbn => $qty){
		$items += max(0, (int)$qty);
		}
		}
		return $items;
		}

		function available_vouchers(){
		return array();
		}

		function calculate_voucher_discount($code, $subtotal, $cart = array(), $userid = 0){
		$code = strtoupper(trim((string)$code));
		$vouchers = available_vouchers();
	if (function_exists('db_connect')) {
	$promoConn = db_connect();
	$promoCode = mysqli_real_escape_string($promoConn, $code);
	$promoResult = mysqli_query($promoConn, "SELECT id, type, value, min_order, expires_at FROM promotions WHERE code = '{$promoCode}' AND active = 1 AND expires_at >= CURDATE() LIMIT 1");
	if ($promoResult && ($promoRow = mysqli_fetch_assoc($promoResult))) {
	$promotionId = (int)$promoRow['id'];
	$alreadyUsed = false;
	if ($userid > 0) {
		$usageResult = mysqli_query($promoConn, "SELECT id FROM promotion_usages WHERE promotion_id = $promotionId AND userid = " . (int)$userid . " LIMIT 1");
		$alreadyUsed = $usageResult && mysqli_num_rows($usageResult) > 0;
	}
	$vouchers[$code] = array('id' => $promotionId, 'type' => $promoRow['type'], 'value' => (float)$promoRow['value'], 'min_order' => (float)$promoRow['min_order'], 'max_discount' => 0, 'already_used' => $alreadyUsed);
	}
	mysqli_close($promoConn);
	}
		if(!isset($vouchers[$code])){
		return array('valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn');
		}
		$voucher = $vouchers[$code];
		if (!empty($voucher['already_used'])) {
		return array('valid' => false, 'message' => 'Bạn đã sử dụng mã ' . $code . ' trước đó');
		}
		if($subtotal < $voucher['min_order']){
		return array('valid' => false, 'message' => 'Mã giảm giá yêu cầu tối thiểu đơn hàng ' . number_format($voucher['min_order'], 0, ',', '.') . 'đ');
		}
		if($voucher['type'] === 'special' && total_items($cart) < 4){
		return array('valid' => false, 'message' => 'Mã ' . $code . ' yêu cầu mua tối thiểu 4 cuốn sách');
		}
		$discount = 0;
		if($voucher['type'] === 'percent'){
		$discount = $subtotal * ((float)$voucher['value'] / 100);
		if($voucher['max_discount'] > 0) $discount = min($discount, $voucher['max_discount']);
		}elseif($voucher['type'] === 'fixed'){
		$discount = min($subtotal, (float)$voucher['value']);
		}elseif($voucher['type'] === 'special'){
		$prices = array();
		foreach($cart as $isbn => $qty){
		$unitPrice = (float)getbookprice($isbn);
		for($i = 0; $i < (int)$qty; $i++) $prices[] = $unitPrice;
		}
			sort($prices, SORT_NUMERIC);
		$discount = !empty($prices) ? $prices[0] : 0;
		}
		return array('valid' => true, 'promotion_id' => (int)($voucher['id'] ?? 0), 'code' => $code, 'type' => $voucher['type'], 'value' => $voucher['value'], 'discount' => max(0, $discount), 'message' => 'Áp dụng mã ' . $code . ' thành công');
		}

		function current_cart_totals($cart){
		$subtotal = total_price($cart);
		$discount = 0;
		$voucher = null;
		if(!empty($_SESSION['voucher_code'])){
		$voucher = calculate_voucher_discount($_SESSION['voucher_code'], $subtotal, $cart, (int)($_SESSION['userid'] ?? 0));
		if(!$voucher['valid']){
		unset($_SESSION['voucher_code'], $_SESSION['voucher_type'], $_SESSION['discount_percent']);
		$voucher = null;
		}else{
		$discount = $voucher['discount'];
		}
		}
		$shippingFee = ($voucher && $voucher['type'] === 'shipping') ? 0 : 30000;
		return array('subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shippingFee, 'total' => max(0, $subtotal - $discount + $shippingFee), 'voucher' => $voucher, 'items' => total_items($cart));
		}
	?>