<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Expense Entry</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://unpkg.com/html5-qrcode"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-3">

  <h4 class="text-center mb-3">📷 Scan Guest QR</h4>
  <div id="reader" style="width:100%; max-width:350px; margin:auto;"></div>

  <form action="add_expense.php" method="POST" class="mt-4" style="max-width:400px; margin:auto;">
    <input type="hidden" name="booking_id" id="booking_id" required>

    <div class="mb-3">
      <label for="item_name" class="form-label">Item</label>
      <input type="text" name="item_name" id="item_name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="amount" class="form-label">Amount (Rs)</label>
      <input type="number" name="amount" id="amount" step="0.01" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="added_by" class="form-label">Source</label>
      <select name="added_by" id="added_by" class="form-select" required>
        <option value="">Select</option>
        <option value="Bar">Bar</option>
        <option value="Restaurant">Restaurant</option>
        <option value="Room Service">Room Service</option>
      </select>
    </div>

    <button class="btn btn-primary w-100">Add Expense</button>
  </form>

  <script>
    function onScanSuccess(decodedText) {
      // Expected format: booking_2
      const bookingId = decodedText.replace("booking_", "");
      document.getElementById("booking_id").value = bookingId;
    }

    Html5Qrcode.getCameras().then(devices => {
      if (devices.length) {
        const html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(
          { facingMode: "environment" }, // Use rear camera
          { fps: 10, qrbox: 200 },
          onScanSuccess
        );
      }
    });
  </script>

</body>
</html>
