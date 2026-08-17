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
		return array(
		'SAVE10' => array('type' => 'percent', 'value' => 10, 'min_order' => 0, 'max_discount' => 0),
		'SAVE20' => array('type' => 'percent', 'value' => 20, 'min_order' => 500000, 'max_discount' => 0),
		'FIRST50' => array('type' => 'fixed', 'value' => 50000, 'min_order' => 0, 'max_discount' => 0),
		'BOOK50FF' => array('type' => 'special', 'value' => 0, 'min_order' => 0, 'max_discount' => 0),
	'BOOK5OFF' => array('type' => 'special', 'value' => 0, 'min_order' => 0, 'max_discount' => 0),
		'FREESHIP' => array('type' => 'shipping', 'value' => 0, 'min_order' => 0, 'max_discount' => 0),
		'WEEKEND' => array('type' => 'percent', 'value' => 15, 'min_order' => 50000, 'max_discount' => 0)
		);
		}

		function calculate_voucher_discount($code, $subtotal, $cart = array()){
		$code = strtoupper(trim((string)$code));
		$vouchers = available_vouchers();
		if(!isset($vouchers[$code])){
		return array('valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn');
		}
		$voucher = $vouchers[$code];
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
		return array('valid' => true, 'code' => $code, 'type' => $voucher['type'], 'value' => $voucher['value'], 'discount' => max(0, $discount), 'message' => 'Áp dụng mã ' . $code . ' thành công');
		}

		function current_cart_totals($cart){
		$subtotal = total_price($cart);
		$discount = 0;
		$voucher = null;
		if(!empty($_SESSION['voucher_code'])){
		$voucher = calculate_voucher_discount($_SESSION['voucher_code'], $subtotal, $cart);
		if(!$voucher['valid']){
		unset($_SESSION['voucher_code'], $_SESSION['voucher_type'], $_SESSION['discount_percent']);
		$voucher = null;
		}else{
		$discount = $voucher['discount'];
		}
		}
		return array('subtotal' => $subtotal, 'discount' => $discount, 'total' => max(0, $subtotal - $discount), 'voucher' => $voucher, 'items' => total_items($cart));
		}
	?>