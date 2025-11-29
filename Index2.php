<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bus Booking</title>
  <link rel="stylesheet" href="style2.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

</head>
<body>
  <div class="container">
    <h2>AK Buses</h2><br>
      
    <!--
      <form action="search.php" method="POST">
        From: <input type="text" name="from" required><br><br>
        To: <input type="text" name="to" required><br><br>
        Date: <input type="date" name="date" required><br><br>
        <button type="submit">Search</button>
      --> 
    <form id="bookingform" action="search.php" method="POST">
      <label for="from">From:</label>

        <select id="from" name="from" required>
          <option value="">Loading...</option>
        </select>

        <label for="to">To:</label>
        <select id="to" name="to" required>
          <option value="">Select Destination</option>
        </select>

        <label for="date">Date:</label>
        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>

        <button type="submit">Search</button>
    </form>
      
      <div id="confirmation"></div>
      <!--</form>-->
  </div>

  <script src="script2.js"></script>
   <script>

const dropdown = document.getElementById('myDropdown');

jsonData.forEach(item => {
  const option = document.createElement('option');
  option.value = item.id;     // Value submitted in form
  option.text = item.name;    // Text shown in dropdown
  dropdown.add(option);
});
document.getElementById('date').value = new Date().toISOString().split('T')[0];
   </script>
</body>
</html>
