<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resident-Villa - Checkout</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://unpkg.com/html5-qrcode"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <style>
    #reader {
      border: 2px solid #28a745;
      border-radius: 8px;
    }
    #invoice {
      max-width: 600px;
      margin: 20px auto;
      display: none;
    }
    #error-message {
      max-width: 400px;
      margin: 20px auto;
      display: none;
    }
  </style>
</head>
<body class="bg-light p-3">
  <h4 class="text-center mb-3">📷 Scan Guest QR for Checkout</h4>

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

  <!-- Error Message -->
  <div id="error-message" class="alert alert-danger"></div>

  <!-- Booking ID Display and Checkout Button -->
  <div class="mt-4" style="max-width:400px; margin:auto;">
    <div class="mb-3">
      <label class="form-label">Booking ID:</label>
      <div id="current-booking-display" class="form-control-plaintext fw-bold text-primary">
        Not scanned yet
      </div>
    </div>
    <button type="button" class="btn btn-success w-100" id="checkout-btn" onclick="performCheckout()" disabled>
      Proceed to Checkout
    </button>
  </div>

  <!-- Invoice Section -->
  <div id="invoice" class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">Invoice for Booking ID: <span id="invoice-booking-id"></span></h5>
    </div>
    <div class="card-body">
      <table class="table table-bordered">
        <tbody>
          <tr>
            <th>Customer Name</th>
            <td id="customer-name"></td>
          </tr>
          <tr>
            <th>Customer Email</th>
            <td id="customer-email"></td>
          </tr>
          <tr>
            <th>Room Number</th>
            <td id="room-number"></td>
          </tr>
          <tr>
            <th>Check-in Date</th>
            <td id="check-in-date"></td>
          </tr>
          <tr>
            <th>Check-out Date</th>
            <td id="check-out-date"></td>
          </tr>
          <tr>
            <th>Total Expenses (Rs)</th>
            <td id="total-expenses">0.00</td>
          </tr>
          <tr>
            <th>Bar Expenses (Rs)</th>
            <td id="bar-expenses">0.00</td>
          </tr>
          <tr>
            <th>Restaurant Expenses (Rs)</th>
            <td id="restaurant-expenses">0.00</td>
          </tr>
          <tr>
            <th>Room Service Expenses (Rs)</th>
            <td id="room-service-expenses">0.00</td>
          </tr>
        </tbody>
      </table>
      <button class="btn btn-success w-100 mb-2" onclick="completeCheckout()">Checkout Complete</button>
      <button class="btn btn-secondary w-100" onclick="resetPage()">Scan Another QR</button>
    </div>
  </div>

  <script>
    (function() {
      emailjs.init({ publicKey: "t4AC9wZSOh6N_EeQ6" });
    })();

    let html5QrCode;
    let isScanning = false;
    let bookingId = '';
    let bookingDetails = {};

    function setBookingId(displayId) {
      bookingId = displayId;
      document.getElementById('current-booking-display').textContent = displayId;
      document.getElementById('checkout-btn').disabled = false;
      document.getElementById('error-message').style.display = 'none';
    }

    function onScanSuccess(decodedText) {
      let displayId;
      if (decodedText.includes('BOOKING_ID:')) {
        const match = decodedText.match(/BOOKING_ID:(\d+)/);
        if (match) {
          displayId = match[1];
        } else {
          return;
        }
      } else if (decodedText.includes('booking_')) {
        displayId = decodedText.replace('booking_', '');
      } else if (decodedText.match(/^\d+$/)) {
        displayId = decodedText;
      } else {
        return;
      }

      setBookingId(displayId);

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
        setBookingId(manualId);
        document.getElementById('manual-input').style.display = 'none';
      }
    }

    function performCheckout() {
      if (!bookingId) return;

      document.getElementById('error-message').style.display = 'none';

      fetch(`get_expenses.php?booking_id=${encodeURIComponent(bookingId)}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.error) {
            document.getElementById('error-message').textContent = data.error;
            document.getElementById('error-message').style.display = 'block';
            return;
          }

          bookingDetails = data;
          document.getElementById('invoice-booking-id').textContent = bookingId;
          document.getElementById('customer-name').textContent = data.customer_name || 'N/A';
          document.getElementById('customer-email').textContent = data.customer_email || 'N/A';
          document.getElementById('room-number').textContent = data.room_number || 'N/A';
          document.getElementById('check-in-date').textContent = data.check_in_date || 'N/A';
          document.getElementById('check-out-date').textContent = data.check_out_date || 'N/A';
          document.getElementById('total-expenses').textContent = parseFloat(data.total_expenses || 0).toFixed(2);
          document.getElementById('bar-expenses').textContent = parseFloat(data.bar_expenses || 0).toFixed(2);
          document.getElementById('restaurant-expenses').textContent = parseFloat(data.restaurant_expenses || 0).toFixed(2);
          document.getElementById('room-service-expenses').textContent = parseFloat(data.room_service_expenses || 0).toFixed(2);
          document.getElementById('invoice').style.display = 'block';
          document.getElementById('checkout-btn').disabled = true;
        })
        .catch(error => {
          console.error('Error fetching expenses:', error);
          document.getElementById('error-message').textContent = 'Failed to fetch expenses: ' + error.message;
          document.getElementById('error-message').style.display = 'block';
        });
    }

    function generatePDFInvoice() {
      return new Promise((resolve, reject) => {
        try {
          const { jsPDF } = window.jspdf;
          const doc = new jsPDF();

          // Set font and title
          doc.setFontSize(20);
          doc.text('Resident Villa Invoice', 105, 20, { align: 'center' });
          doc.setFontSize(12);
          doc.text(`Booking ID: ${bookingId}`, 105, 30, { align: 'center' });
          doc.text(`Issued on: ${new Date().toLocaleDateString()}`, 105, 35, { align: 'center' });

          // Customer Details
          doc.setFontSize(14);
          doc.text('Customer Details', 20, 50);
          doc.setFontSize(12);
          doc.text(`Name: ${bookingDetails.customer_name || 'N/A'}`, 20, 60);
          doc.text(`Email: ${bookingDetails.customer_email || 'N/A'}`, 20, 65);

          // Booking Details
          doc.setFontSize(14);
          doc.text('Booking Details', 20, 80);
          doc.setFontSize(12);
          doc.text(`Room Number: ${bookingDetails.room_number || 'N/A'}`, 20, 90);
          doc.text(`Check-in Date: ${bookingDetails.check_in_date || 'N/A'}`, 20, 95);
          doc.text(`Check-out Date: ${bookingDetails.check_out_date || 'N/A'}`, 20, 100);

          // Expenses Table
          doc.setFontSize(14);
          doc.text('Expenses', 20, 115);
          doc.setFontSize(12);
          doc.autoTable({
            startY: 120,
            head: [['Description', 'Amount (Rs)']],
            body: [
              ['Total Expenses', parseFloat(bookingDetails.total_expenses || 0).toFixed(2)],
              ['Bar Expenses', parseFloat(bookingDetails.bar_expenses || 0).toFixed(2)],
              ['Restaurant Expenses', parseFloat(bookingDetails.restaurant_expenses || 0).toFixed(2)],
              ['Room Service Expenses', parseFloat(bookingDetails.room_service_expenses || 0).toFixed(2)]
            ],
            theme: 'grid',
            headStyles: { fillColor: [0, 123, 255], textColor: [255, 255, 255] },
            margin: { left: 20, right: 20 }
          });

          // Footer
          doc.setFontSize(12);
          doc.text('Thank you for staying at Resident Villa!', 105, doc.internal.pageSize.height - 20, { align: 'center' });

          // Trigger download
          doc.save(`invoice_${bookingId}.pdf`);

          // Return base64 for potential future use
          const pdfBase64 = doc.output('datauristring').split(',')[1];
          resolve(pdfBase64);
        } catch (error) {
          reject(error);
        }
      });
    }

    function completeCheckout() {
      if (!bookingId) {
        console.error('No booking ID available');
        return;
      }

      // Check booking status from bookingDetails
      if (!bookingDetails.status) {
        console.error('Booking status not available');
        document.getElementById('error-message').textContent = 
          '❌ Cannot proceed: Booking status not available for booking ID ' + bookingId;
        document.getElementById('error-message').style.display = 'block';
        return;
      }

      // If status is not completed, attempt to update it
      if (bookingDetails.status !== 'completed') {
        fetch(`complete_checkout.php?booking_id=${encodeURIComponent(bookingId)}`, { method: 'POST' })
          .then(response => {
            // Log raw response for debugging
            response.text().then(text => {
              console.log('Raw response from complete_checkout.php:', text);
            });
            if (!response.ok) {
              throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            if (!data.success) {
              throw new Error(data.error || 'Failed to update booking status');
            }
            // Status updated or already completed, proceed with PDF and email
            proceedWithCheckout();
          })
          .catch(error => {
            console.error('Error updating booking status:', error);
            document.getElementById('error-message').textContent = 
              '❌ Failed to update booking status: ' + error.message;
            document.getElementById('error-message').style.display = 'block';
          });
      } else {
        // Status is already completed, proceed directly
        proceedWithCheckout();
      }
    }

    function proceedWithCheckout() {
      generatePDFInvoice().then(pdfBase64 => {
        const customerEmail = (bookingDetails.customer_email || '').trim();
        console.log('Raw customer email:', bookingDetails.customer_email);
        console.log('Trimmed customer email:', customerEmail);

        const templateParams = {
          to_name: bookingDetails.customer_name || 'Guest',
          to_email: customerEmail,
          booking_id: bookingId,
          room_number: bookingDetails.room_number || 'N/A',
          check_in_date: bookingDetails.check_in_date || 'N/A',
          check_out_date: bookingDetails.check_out_date || 'N/A',
          total_expenses: parseFloat(bookingDetails.total_expenses || 0).toFixed(2),
          bar_expenses: parseFloat(bookingDetails.bar_expenses || 0).toFixed(2),
          restaurant_expenses: parseFloat(bookingDetails.restaurant_expenses || 0).toFixed(2),
          room_service_expenses: parseFloat(bookingDetails.room_service_expenses || 0).toFixed(2)
        };

        console.log('templateParams:', templateParams);
        console.log('Sending email to:', templateParams.to_email);

        if (!templateParams.to_email) {
          console.error('Missing customer email:', templateParams.to_email || 'undefined');
          document.getElementById('error-message').textContent = 
            `❌ Cannot send email: Customer email is missing for booking ID ${bookingId}`;
          document.getElementById('error-message').style.display = 'block';
          return;
        }

        emailjs.send("service_0w91rnv", "template_mtfikoq", templateParams)
          .then(response => {
            console.log('✅ Email sent successfully:', response);
            alert('✔️ Checkout completed successfully! Invoice sent to ' + templateParams.to_email);
            resetPage();
          })
          .catch(error => {
            console.error('❗ EmailJS error:', error);
            document.getElementById('error-message').textContent = 
              `❌ Failed to send checkout email: ${error.text || error.message || 'Unknown error'}`;
            document.getElementById('error-message').style.display = 'block';
          });
      })
      .catch(error => {
        console.error('❗ Error generating PDF:', error);
        document.getElementById('error-message').textContent = 
          '❌ Failed to generate PDF invoice: ' + error.message;
        document.getElementById('error-message').style.display = 'block';
      });
    }

    function resetPage() {
      bookingId = '';
      bookingDetails = {};
      document.getElementById('current-booking-display').textContent = 'Not scanned yet';
      document.getElementById('checkout-btn').disabled = true;
      document.getElementById('invoice').style.display = 'none';
      document.getElementById('manual-booking-id').value = '';
      document.getElementById('error-message').style.display = 'none';
      startScanner();
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