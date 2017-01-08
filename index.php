<?php

/**
 * Create a message signature
 * @param array $data
 * @param string $key
 * @return string hashed signature
 */
function createSignature(array $data, $key) {
	// Sort by field name
	ksort($data);
	
	// Create the URL encoded signature string
	$ret = http_build_query($data, '', '&');
	
	// Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
	$ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);

	// Hash the signature string and the key together
	return hash('SHA512', $ret . $key);
}

// Signature key entered on MMS. The demo accounts is fixed to this value,
$key = 'Circle4Take40Idea';

// Gateway URL
$url = 'https://gateway.cardstream.com/hosted/';

if (!isset($_POST['responseCode'])) {
	// Send request to gateway
	
	// Request
	$req = array(
		'merchantID' => '100001',
		'action' => 'SALE',
		'type' => 1,
		'countryCode' => 826,
		'currencyCode' => 826,
		'amount' => 1001,
		'orderRef' => 'Test purchase',
 		'transactionUnique' => uniqid(),
		// 'redirectURL' => ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'redirectURL' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
	);

	// Create the signature using the function called below.
	$req['signature'] = createSignature($req, $key);

	echo '<form action="' . htmlentities($url) . '" method="post">' . PHP_EOL;
 	foreach ($req as $field => $value) {
		echo ' <input type="hidden" name="' . $field . '" value="' . htmlentities($value) . '">' . PHP_EOL;
	}
 	echo ' <input type="submit" value="Pay Now">' . PHP_EOL;
	echo '</form>' . PHP_EOL;
} else {
	// Handle the response posted back
	$res = $_POST;
	
	// Extract the return signature as this isn't hashed
	$signature = null;
	if (isset($res['signature'])) {
		$signature = $res['signature'];
		unset($res['signature']);
	}

	// Check the return signature
	if (!$signature || $signature !== createSignature($res, $key)) {
		// You should exit gracefully
		die('Sorry, the signature check failed');
	}

	// Check the response code
	if ($res['responseCode'] === "0") {
		echo "<p>Thank you for your payment.</p>";
		echo '<pre>';var_dump($_POST);echo '</pre>';
	} else {
		echo "<p>Failed to take payment: " . htmlentities($res['responseMessage']) . "</p>";
	}
}