<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resident-Villa - Expense Entry</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://unpkg.com/html5-qrcode"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #reader {
      border: 2px solid #28a745;
      border-radius: 8px;
    }
  </style>
</head>
<body class="bg-light p-3">
  
  <h4 class="text-center mb-3">📷 Scan Guest QR</h4>

  <!-- QR Scanner -->
  <div id="reader" style="width:100%; max-width:350px; margin:auto;"></div>

  <!-- Manual Input Option -->
  <div class="text-center mt-3">
    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleManualInput()">
      Enter Booking ID Manually
    </button>
  </div>

  <!-- Manual Input Form -->
  <div id="manual-input" style="display:none; max-width:400px; margin:20px auto;">
    <div class="card">
      <div class="card-body">
        <input type="text" id="manual-booking-id" class="form-control" placeholder="Enter Booking ID">
        <button class="btn btn-primary btn-sm mt-2" onclick="setBookingIdManually()">Use This ID</button>
      </div>
    </div>
  </div>

  <!-- Expense Form -->
  <form id="expense-form" action="add_expense.php" method="POST" class="mt-4" style="max-width:400px; margin:auto;">
    <input type="hidden" name="booking_id" id="booking_id" required>

    <div class="mb-3">
      <label class="form-label">Booking ID:</label>
      <div id="current-booking-display" class="form-control-plaintext fw-bold text-primary">
        Not scanned yet
      </div>
    </div>

    <div class="mb-3">
      <label for="item_name" class="form-label">Item</label>
      <input type="text" name="item_name" id="item_name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="amount" class="form-label">Amount (Rs)</label>
      <input type="number" name="amount" id="amount" step="0.01" class="form-control" required min="0">
    </div>

    <div class="mb-3">
      <label for="added_by" class="form-label">Source</label>
      <select name="added_by" id="added_by" class="form-select" required>
        <option value="">Select Source</option>
        <option value="Bar">Bar</option>
        <option value="Restaurant">Restaurant</option>
        <option value="Room Service">Room Service</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary w-100" id="submit-btn" disabled>
      Add Expense
    </button>
  </form>

  <script>
    let html5QrCode;
    let isScanning = false;

    function setBookingId(displayId) {
      const hiddenField = document.getElementById('booking_id');
      if (!hiddenField.value) {
        hiddenField.value = displayId;
      }

      document.getElementById('current-booking-display').textContent = displayId;
      document.getElementById('submit-btn').disabled = false;
    }

    function onScanSuccess(decodedText) {
      let bookingId;
      if (decodedText.includes('BOOKING_ID:')) {
        const match = decodedText.match(/BOOKING_ID:(\d+)/);
        if (match) {
          bookingId = match[1];
        } else {
          return;
        }
      } else if (decodedText.includes('booking_')) {
        bookingId = decodedText.replace('booking_', '');
      } else if (decodedText.match(/^\d+$/)) {
        bookingId = decodedText;
      } else {
        return;
      }

      document.getElementById('booking_id').value = decodedText;
      setBookingId(bookingId);

      if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
          isScanning = false;
        });
      }
    }

    function toggleManualInput() {
      const manualDiv = document.getElementById('manual-input');
      manualDiv.style.display = manualDiv.style.display === 'none' ? 'block' : 'none';
    }

    function setBookingIdManually() {
      const manualId = document.getElementById('manual-booking-id').value.trim();
      if (manualId) {
        document.getElementById('booking_id').value = manualId;
        setBookingId(manualId);
        document.getElementById('manual-input').style.display = 'none';
      }
    }

    function startScanner() {
      Html5Qrcode.getCameras().then(devices => {
        if (devices.length > 0) {
          html5QrCode = new Html5Qrcode("reader");

          const cameraId = devices.find(device => 
            device.label.toLowerCase().includes('back') ||
            device.label.toLowerCase().includes('rear')
          )?.id || devices[0].id;

          html5QrCode.start(
            cameraId,
            { fps: 10, qrbox: 200 },
            onScanSuccess
          ).then(() => {
            isScanning = true;
          });
        }
      });
    }

    document.addEventListener('DOMContentLoaded', startScanner);
  </script>
</body>
</html>
