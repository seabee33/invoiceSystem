<?php
$conn = new mysqli("DB_HOST_HERE", "DB_USERNAME_HERE", "DB_PASSWORD_HERE", "DB_Name_HERE");
$totalGST = 0;
$totalNet = 0;
$totalIncGST = 0;

if(isset($_GET['InvoiceID'])){
	$invoiceID = htmlspecialchars($_GET['InvoiceID']);

	$checkInvoiceIDSTMT = $conn->prepare("SELECT creation_date, due_date, to_name, to_mobile, to_email, to_address, invoice_no, paid_status FROM invoices WHERE uid=?");
	$checkInvoiceIDSTMT->bind_param("s", $invoiceID);
	$checkInvoiceIDSTMT->execute();
	$checkInvoiceIDResult = $checkInvoiceIDSTMT->get_result();

	if($checkInvoiceIDResult->num_rows == 1){
		$rowData = $checkInvoiceIDResult->fetch_assoc();

		$creationDateOriginal = $rowData['creation_date'];
		$creationDate = date("d M Y", strtotime($creationDateOriginal));
		$toEmail = $rowData['to_email'];
		$toAddress = $rowData['to_address'];
		$invoiceNo = $rowData['invoice_no'];
		$paidStatus = $rowData['paid_status'];

		$dueDate =  new DateTime($creationDate);
		$dueDate->modify('+14 days');
		$dueDate = $dueDate->format("d M Y");

		$toName = $rowData['to_name'];
		$toMobileOriginal = $rowData['to_mobile'];
		if(strval($toMobileOriginal)[0] === '4'){
			(string)$toMobile = '0' . substr($toMobileOriginal, 0, 3) . ' ' . substr($toMobileOriginal, 3, 3) . ' ' . substr($toMobileOriginal, 6, 3);
		}

		$invoiceItems = $conn->prepare("SELECT item_desc, item_amt, total_inc_gst FROM invoice_items WHERE uid=?");
		$invoiceItems->bind_param("s", $invoiceID);
		$invoiceItems->execute();
		$invoiceItemsResult = $invoiceItems->get_result();

		if($invoiceItemsResult->num_rows == 0){
			echo "<script>alert('Something went horrible wrong');</script>";
		} else {
			while($invoiceItem = $invoiceItemsResult->fetch_assoc()){
				$itemDescArray[] = $invoiceItem['item_desc'];
				$itemAmtArray[] = $invoiceItem['item_amt'];
				$itemTotalPriceArray[] = $invoiceItem['total_inc_gst'];
			}
		}

		if(count($itemAmtArray) == count($itemTotalPriceArray)){
			$itemTotals = array();

			for ($i = 0; $i < count($itemAmtArray); $i++){
				$itemTotals[] = $itemAmtArray[$i] * $itemTotalPriceArray[$i];
			}
		}

	}
	$checkInvoiceIDResult->close();
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>View Invoice</title>
	<link rel="stylesheet" href="assets/styles.css">
</head>
<body>


<div id="topButtons" class="topButtons">
	<p><button style="padding: 5px 15px; border-radius:50px; border: 1px solid grey;" onclick="printMe()" >Print</button></p>
</div>



<div class="invoiceBox">

	<div class="rowSplit">
		<div class="topRow">
			<h2><strong>Easy Eol</strong></h2>
			<p style="line-height: 20px;"> <img src="assets/img/map-pin.svg" alt=""> Address Line 1 <br>
				Address Line 2
			</p>
			<p><img src="assets/img/phone.svg" alt="">Company Mobile</p>
			<p><img src="assets/img/mail.svg" alt="">Company Email</p>
			<p><img src="assets/img/link.svg" alt="">Company Mobile</p>
			<p><img src="assets/img/briefcase.svg" alt="">ABN: Company ABN</p>
		</div>
		<div>
			<img class="companyIcon" src="assets/img/easyeollogo.jpeg" alt="Company Icon">
		</div>
	</div>

	<div class="rowSplit">
		<div>
			<h1>Tax Invoice</h1>
			<p><strong>Invoice To</strong></p>
			<p><?php echo $toName; ?></p>
			<p><?php echo $toMobile; ?></p>
			<p><?php echo $toEmail; ?></p>
			<p><?php echo $toAddress; ?></p>
		</div>
		<div>
			<p class="rowSplit2"><strong>Invoice No: </strong> <span><?php echo $invoiceNo; ?></span></p>
			<p class="rowSplit2"><strong>Date: </strong> <span><?php echo $creationDate; ?></span></p>
			<p class="rowSplit2"><strong>Due Date: </strong> <span><?php echo $dueDate; ?></span></p>
			<p class="rowSplit2"><strong>Due Term: </strong> <span>14 Days</span></p>
		</div>
	</div>


	<?php



	?>



	
	<div class="itemRow">
		<div style="flex:5">
			<p>Item</p>
			<?php foreach($itemDescArray as $itemDesc){echo "<p> $itemDesc </p>";} ?>
		</div>
		<div style="flex:1">
			<p>Quantity</p>
			<?php foreach($itemAmtArray as $itemAmt){echo "<p> $itemAmt </p>";} ?>
		</div>
		<div style="flex:1">
			<p>Rate</p>
			<?php foreach($itemTotalPriceArray as $itemTotalPrice){echo "<p> $$itemTotalPrice </p>";} ?>
		</div>
		<div style="width:120px;">
			<p>GST Included</p>
			<?php foreach($itemTotalPriceArray as $totalItemCost){
				$itemGST = $totalItemCost / 11;
				$itemGST = round($itemGST,2);

				$totalGST = $totalGST + $itemGST;
				$totalNet = $totalNet + ($totalItemCost - $totalGST);
				$totalIncGST = $totalIncGST + $totalItemCost;

				echo "<script> console.log('$totalGST')</script>";
				echo "<p> $$itemGST </p>";
			} ?>
		</div>
		<div style="flex:1">
			<p>Amount</p>
			<?php foreach($itemTotals as $itemTotal){ echo "<p> $$itemTotal </p>"; } ?>
		</div>
	</div>

	<div class="rowSplit">
		<div style="line-height: 25px;">
			<p>To pay by bank transfer: </p>
			<p><strong>BSB: </strong> 123 123</p>
			<p><strong>Account Num: </strong> 123 123 123</p>
			<p><strong>Account Name:</strong> Easy EOL</p>
			<p><strong>Reference:</strong> <?php echo $invoiceNo ?></p>
		</div>
		<div class="paymentBit">
			<p><span><strong>Sub Total </strong> (Ex GST):</span> $<?php echo $totalNet ?></p>
			<p><strong>GST Included: </strong> $<?php echo $totalGST ?></p>
			<p><strong style="margin-top: 9px;">Total Balance Due: </strong> <strong style="font-size:30px;">$<?php echo $totalIncGST ?></strong></p>
		</div>
	</div>

	<p>BAS Summary:</p>
	<div class="itemRow2">
		<div style="flex:1">
			<p>GST Rate</p>
			<p>10%</p>
		</div>
		<div style="flex:1">
			<p>GST</p>
			<p><?php echo $totalGST; ?></p>
		</div>
		<div style="flex:1">
			<p>Net</p>
			<p><?php echo $totalNet; ?></p>
		</div>
	</div>



</div>









<script>
	function printMe(){
		var topButtons = document.getElementById('topButtons');
		topButtons.style.display = "none";
		window.print();
		topButtons.style.display = "block";
	}
</script>
</body>
</html>
