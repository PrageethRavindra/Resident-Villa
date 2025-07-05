<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resident-Villa - Checkout</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://unpkg.com/html5-qrcode@2.3.0"></script>
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
            <th>Room Numbers</th>
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
            <th>Room Charges (Rs)</th>
            <td id="room-charges">0.00</td>
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
          <tr>
            <th>Total Expenses (Rs)</th>
            <td id="total-expenses">0.00</td>
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
        }).catch(error => {
          console.error('Error stopping QR scanner:', error);
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

    function calculateRoomCharges(checkInDate, checkOutDate, roomPrice) {
      try {
        const checkIn = new Date(checkInDate);
        const checkOut = new Date(checkOutDate);
        if (isNaN(checkIn.getTime()) || isNaN(checkOut.getTime())) {
          throw new Error('Invalid date format');
        }
        const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        if (nights < 1) return 0;
        return nights * (roomPrice || 15000); // Default to 15000 if roomPrice is missing
      } catch (error) {
        console.error('Error calculating room charges:', error);
        return 0;
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
          const roomCharges = calculateRoomCharges(data.check_in_date, data.check_out_date, data.total_room_price);
          const barExpenses = parseFloat(data.bar_expenses || 0);
          const restaurantExpenses = parseFloat(data.restaurant_expenses || 0);
          const roomServiceExpenses = parseFloat(data.room_service_expenses || 0);
          const totalExpenses = roomCharges + barExpenses + restaurantExpenses + roomServiceExpenses;

          document.getElementById('invoice-booking-id').textContent = bookingId;
          document.getElementById('customer-name').textContent = data.customer_name || 'N/A';
          document.getElementById('customer-email').textContent = data.customer_email || 'N/A';
          document.getElementById('room-number').textContent = data.room_numbers || 'N/A';
          document.getElementById('check-in-date').textContent = data.check_in_date || 'N/A';
          document.getElementById('check-out-date').textContent = data.check_out_date || 'N/A';
          document.getElementById('room-charges').textContent = roomCharges.toFixed(2);
          document.getElementById('bar-expenses').textContent = barExpenses.toFixed(2);
          document.getElementById('restaurant-expenses').textContent = restaurantExpenses.toFixed(2);
          document.getElementById('room-service-expenses').textContent = roomServiceExpenses.toFixed(2);
          document.getElementById('total-expenses').textContent = totalExpenses.toFixed(2);
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
          doc.text(`Room Numbers: ${bookingDetails.room_numbers || 'N/A'}`, 20, 90);
          doc.text(`Check-in Date: ${bookingDetails.check_in_date || 'N/A'}`, 20, 95);
          doc.text(`Check-out Date: ${bookingDetails.check_out_date || 'N/A'}`, 20, 100);

          // Expenses Table
          doc.setFontSize(14);
          doc.text('Expenses', 20, 115);
          doc.setFontSize(12);
          const roomCharges = calculateRoomCharges(bookingDetails.check_in_date, bookingDetails.check_out_date, bookingDetails.total_room_price);
          const barExpenses = parseFloat(bookingDetails.bar_expenses || 0);
          const restaurantExpenses = parseFloat(bookingDetails.restaurant_expenses || 0);
          const roomServiceExpenses = parseFloat(bookingDetails.room_service_expenses || 0);
          const totalExpenses = roomCharges + barExpenses + restaurantExpenses + roomServiceExpenses;

          doc.autoTable({
            startY: 120,
            head: [['Description', 'Amount (Rs)']],
            body: [
              ['Room Charges', roomCharges.toFixed(2)],
              ['Bar Expenses', barExpenses.toFixed(2)],
              ['Restaurant Expenses', restaurantExpenses.toFixed(2)],
              ['Room Service Expenses', roomServiceExpenses.toFixed(2)],
              ['Total Expenses', totalExpenses.toFixed(2)]
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

      // Generate PDF and trigger download
      generatePDFInvoice().then(pdfBase64 => {
        // Ensure bookingDetails.customer_email is valid
        const customerEmail = (bookingDetails.customer_email || '').trim();
        console.log('Customer email from bookingDetails:', customerEmail);

        // Calculate room charges for email
        const roomCharges = calculateRoomCharges(bookingDetails.check_in_date, bookingDetails.check_out_date, bookingDetails.total_room_price);
        const barExpenses = parseFloat(bookingDetails.bar_expenses || 0);
        const restaurantExpenses = parseFloat(bookingDetails.restaurant_expenses || 0);
        const roomServiceExpenses = parseFloat(bookingDetails.room_service_expenses || 0);
        const totalExpenses = roomCharges + barExpenses + restaurantExpenses + roomServiceExpenses;

        // Email parameters for EmailJS
        const templateParams = {
          to_name: bookingDetails.customer_name || 'Guest',
          to_email: customerEmail,
          to_email: customerEmail,
          booking_id: bookingId,
          room_number: bookingDetails.room_numbers || 'N/A',
          check_in_date: bookingDetails.check_in_date || 'N/A',
          check_out_date: bookingDetails.check_out_date || 'N/A',
          room_charges: roomCharges.toFixed(2),
          bar_expenses: barExpenses.toFixed(2),
          restaurant_expenses: restaurantExpenses.toFixed(2),
          room_service_expenses: roomServiceExpenses.toFixed(2),
          total_expenses: totalExpenses.toFixed(2)
        };

        // Debug logs
        console.log('templateParams:', templateParams);
        console.log('Sending email to:', templateParams.to_email);

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!templateParams.to_email || !emailRegex.test(templateParams.to_email)) {
          console.error('Invalid or missing customer email:', templateParams.to_email || 'undefined');
          document.getElementById('error-message').textContent = 
            `❌ Cannot send email: Invalid or missing customer email (${templateParams.to_email || 'none'}) for booking ID ${bookingId}`;
          document.getElementById('error-message').style.display = 'block';
          return;
        }

        // Send email using EmailJS
        emailjs.send("service_0w91rnv", "template_mtfikoq", templateParams)
          .then(response => {
            console.log('✅ Email sent successfully:', response);

            // Update booking status
            fetch(`complete_checkout.php?booking_id=${encodeURIComponent(bookingId)}`, { method: 'POST' })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  alert('✔️ Checkout completed successfully! Invoice sent to ' + templateParams.to_email);
                  resetPage();
                } else {
                  throw new Error(data.error || '❌ Failed to update booking status');
                }
              })
              .catch(error => {
                console.error('❗ Error updating booking status:', error);
                document.getElementById('error-message').textContent = 
                  '⚠️ Checkout email sent, but failed to update booking status: ' + error.message;
                document.getElementById('error-message').style.display = 'block';
              });
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
        if (devices.length === 0) {
          document.getElementById('error-message').textContent = 'No cameras found on this device';
          document.getElementById('error-message').style.display = 'block';
          return;
        }
        html5QrCode = new Html5Qrcode("reader");
        const cameraId = devices.find(device => 
          device.label.toLowerCase().includes('back') ||
          device.label.toLowerCase().includes('rear')
        )?.id || devices[0].id;
        console.log('Selected camera:', cameraId);
        html5QrCode.start(
          cameraId,
          { fps: 10, qrbox: { width: 200, height: 200 } },
          onScanSuccess
        ).then(() => {
          isScanning = true;
          console.log('QR code scanner started successfully');
        }).catch(error => {
          console.error('Error starting QR scanner:', error);
          document.getElementById('error-message').textContent = 'Failed to start camera: ' + error.message;
          document.getElementById('error-message').style.display = 'block';
        });
      }).catch(error => {
        console.error('Error accessing cameras:', error);
        document.getElementById('error-message').textContent = 'Camera access denied: ' + error.message;
        document.getElementById('error-message').style.display = 'block';
      });
    }

    document.addEventListener('DOMContentLoaded', startScanner);
  </script>
</body>
</html>