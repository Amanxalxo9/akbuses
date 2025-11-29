<?php
// admin.php — Enhanced Admin Panel

// Simple authentication
$admin_user = 'admin';
$admin_pass = 'admin123';

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
} else {
    if ($_SERVER['PHP_AUTH_USER'] !== $admin_user || $_SERVER['PHP_AUTH_PW'] !== $admin_pass) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied';
        exit;
    }
}

$bookings_file = 'bookings.txt';

// -----------------
// Load bookings
// -----------------
$bookings = [];
if (file_exists($bookings_file)) {
    $lines = file($bookings_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data) $bookings[] = $data;
    }
}

// -----------------
// Handle POST actions: delete, add/edit
// -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Delete booking
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $new_lines = [];
        foreach ($bookings as $b) {
            if (($b['id'] ?? '') !== $delete_id) $new_lines[] = json_encode($b);
        }
        file_put_contents($bookings_file, implode("\n",$new_lines)."\n");
        header("Location: admin.php");
        exit;
    }

    // Add/Edit booking
    if (isset($_POST['action']) && ($_POST['action']==='save_booking')) {
        $id = $_POST['id'] ?? 'bk_'.uniqid();
        $booking = [
            'id' => $id,
            'from' => $_POST['from'] ?? '',
            'to' => $_POST['to'] ?? '',
            'date' => $_POST['date'] ?? '',
            'bus' => $_POST['bus'] ?? '',
            'time' => $_POST['time'] ?? '',
            'customer_name' => $_POST['customer_name'] ?? '',
            'customer_gender' => $_POST['customer_gender'] ?? '',
            'boarding' => $_POST['boarding'] ?? '',
            'dropping' => $_POST['dropping'] ?? '',
            'seats' => $_POST['seats'] ?? [],
            'prices' => $_POST['prices'] ?? [],
            'total' => array_sum(array_map('floatval', $_POST['prices'] ?? [])),
            'passengers' => [],
            'created_at' => date('c')
        ];
        foreach ($_POST['passenger_name'] ?? [] as $i=>$name) {
            $booking['passengers'][] = [
                'name'=>$name,
                'age'=>intval($_POST['passenger_age'][$i] ?? 0),
                'gender'=>$_POST['passenger_gender'][$i] ?? 'unknown',
                'seat'=>$_POST['passenger_seat'][$i] ?? '',
                'phone'=>$_POST['passenger_phone'][$i] ?? '',
                'email'=>$_POST['passenger_email'][$i] ?? ''
            ];
        }

        // Remove old booking if editing
        $new_lines = [];
        foreach ($bookings as $b) {
            if (($b['id'] ?? '') !== $id) $new_lines[] = json_encode($b);
        }
        $new_lines[] = json_encode($booking);
        file_put_contents($bookings_file, implode("\n",$new_lines)."\n");
        header("Location: admin.php");
        exit;
    }
}

// -----------------
// Filter bookings
// -----------------
$filter_bus  = $_GET['bus']  ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to   = $_GET['to']   ?? '';

$filtered = array_filter($bookings, function($b) use ($filter_bus,$filter_date,$filter_from,$filter_to){
    if ($filter_bus && stripos($b['bus'],$filter_bus)===false) return false;
    if ($filter_date && $b['date']!==$filter_date) return false;
    if ($filter_from && stripos($b['from'],$filter_from)===false) return false;
    if ($filter_to && stripos($b['to'],$filter_to)===false) return false;
    return true;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel — Bus Bookings</title>
<style>
body { font-family: Arial, Helvetica, sans-serif; background:#f4f6f8; color:#222; }
.container { max-width:1200px; margin:20px auto; padding:16px; background:#fff; border-radius:8px; box-shadow:0 4px 18px rgba(0,0,0,0.06); }
h1,h2 { margin-bottom:12px; }
form { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
input, select { padding:6px; border-radius:4px; border:1px solid #ccc; }
.btn { padding:6px 10px; border:none; border-radius:4px; background:#28a745; color:white; cursor:pointer; }
.btn-delete { background:#dc3545; }
table { width:100%; border-collapse:collapse; margin-top:12px; }
th,td { border:1px solid #ccc; padding:8px; font-size:14px; text-align:left; }
th { background:#eee; }
tr:nth-child(even){background:#f9f9f9;}
.passenger-row { display:flex; gap:4px; margin-bottom:4px; }
.passenger-row input { width:120px; }
</style>
</head>
<body>
<div class="container">
<h1>Admin Panel — Bus Bookings</h1>

<a href="add_bus.php" 
   style="
      display:inline-block;
      padding:6px 10px;
      background:#28a745;
      color:#fff;
      border-radius:4px;
      text-decoration:none;
      margin-bottom:15px;
   ">
   + Add Bus
</a>
<a href="bus_manager.php" 
   style="
      display:inline-block;
      padding:6px 10px;
      background:#28a745;
      color:#fff;
      border-radius:5px;
      text-decoration:none;
      margin-bottom:15px;
   ">
   🚌 Manage Buses
</a>


<h2>Add / Edit Booking</h2>
<form method="POST" id="bookingForm">
<input type="hidden" name="action" value="save_booking">
<input type="hidden" name="id" id="booking_id">

<input type="text" name="bus" placeholder="Bus Name" required>
<input type="text" name="from" placeholder="From" required>
<input type="text" name="to" placeholder="To" required>
<input type="date" name="date" required>
<input type="time" name="time" required>
<input type="text" name="customer_name" placeholder="Customer Name" required>
<select name="customer_gender" required>
<option value="male">Male</option>
<option value="female">Female</option>
</select>
<input type="text" name="boarding" placeholder="Boarding" required>
<input type="text" name="dropping" placeholder="Dropping" required>
<input type="text" name="seats" placeholder="Seats (comma separated)" required>
<input type="text" name="prices" placeholder="Prices (comma separated)" required>

<div id="passengersContainer">
<!-- passenger rows will be added by JS -->
</div>
<button type="button" onclick="addPassengerRow()" class="btn">Add Passenger</button>
<button type="submit" class="btn">Save Booking</button>
</form>

<h2>Filter Bookings</h2>
<form method="GET">
<input type="text" name="bus" placeholder="Bus Name" value="<?= htmlspecialchars($filter_bus) ?>">
<input type="text" name="from" placeholder="From" value="<?= htmlspecialchars($filter_from) ?>">
<input type="text" name="to" placeholder="To" value="<?= htmlspecialchars($filter_to) ?>">
<input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
<button type="submit" class="btn">Filter</button>
<button type="button" class="btn" onclick="exportCSV()">Export CSV</button>
</form>

<table id="bookingsTable">
<tr>
<th>ID</th><th>Bus</th><th>Route</th><th>Date/Time</th><th>Customer</th><th>Gender</th><th>Seats</th><th>Total</th><th>Passengers</th><th>Actions</th>
</tr>

<?php foreach ($filtered as $b): ?>
<tr>
<td><?= htmlspecialchars($b['id'] ?? '') ?></td>
<td><?= htmlspecialchars($b['bus'] ?? '') ?></td>
<td><?= htmlspecialchars($b['from'] ?? '') ?> → <?= htmlspecialchars($b['to'] ?? '') ?></td>
<td><?= htmlspecialchars($b['date'] ?? '') ?> <?= htmlspecialchars($b['time'] ?? '') ?></td>
<td><?= htmlspecialchars($b['customer_name'] ?? '') ?></td>
<td><?= htmlspecialchars($b['customer_gender'] ?? '') ?></td>
<td><?= htmlspecialchars(implode(',',$b['seats']??[])) ?></td>
<td>₹<?= htmlspecialchars($b['total'] ?? 0) ?></td>
<td>
<?php foreach($b['passengers'] ?? [] as $p){
    echo htmlspecialchars($p['name'].'('.$p['seat'].')')."<br>";
} ?>
<td>
<button class="btn" type="button" onclick='editBooking(<?= json_encode($b) ?>)'>Edit</button>

<form method="POST" style="display:inline;" onsubmit="return confirm("Delete this booking?");">
<input type="hidden" name="delete_id" value="<?= htmlspecialchars($b['id']) ?>">
<button type="submit" class="btn btn-delete">Delete</button>
</form>
</td>

</tr>
<?php endforeach; ?>
</table>

</div>

<script>
function addPassengerRow(name='',age='',gender='male',seat='',phone='',email=''){
    const container = document.getElementById('passengersContainer');
    const div = document.createElement('div');
    div.className='passenger-row';
    div.innerHTML=`
    <input type="text" name="passenger_name[]" placeholder="Name" value="${name}" required>
    <input type="number" name="passenger_age[]" placeholder="Age" value="${age}" required>
    <select name="passenger_gender[]" required>
      <option value="male"${gender==='male'?' selected':''}>Male</option>
      <option value="female"${gender==='female'?' selected':''}>Female</option>
    </select>
    <input type="text" name="passenger_seat[]" placeholder="Seat" value="${seat}" required>
    <input type="text" name="passenger_phone[]" placeholder="Phone" value="${phone}">
    <input type="email" name="passenger_email[]" placeholder="Email" value="${email}">
    <button type="button" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(div);
}

function exportCSV(){
    const rows = Array.from(document.querySelectorAll('#bookingsTable tr'));
    let csv = rows.map(r => Array.from(r.querySelectorAll('th,td')).map(c=>'"'+c.innerText.replace(/"/g,'""')+'"').join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download='bookings.csv'; a.click();
    URL.revokeObjectURL(url);
}

function editBooking(b){
    document.getElementById('booking_id').value = b.id;

    document.querySelector('input[name="bus"]').value = b.bus;
    document.querySelector('input[name="from"]').value = b.from;
    document.querySelector('input[name="to"]').value = b.to;
    document.querySelector('input[name="date"]').value = b.date;
    document.querySelector('input[name="time"]').value = b.time;
    document.querySelector('input[name="customer_name"]').value = b.customer_name;
    document.querySelector('select[name="customer_gender"]').value = b.customer_gender;
    document.querySelector('input[name="boarding"]').value = b.boarding;
    document.querySelector('input[name="dropping"]').value = b.dropping;

    document.querySelector('input[name="seats"]').value = b.seats.join(",");
    document.querySelector('input[name="prices"]').value = b.prices.join(",");

    // Clear old passengers
    document.getElementById('passengersContainer').innerHTML = "";

    // Load passengers
    if(b.passengers){
        b.passengers.forEach(p=>{
            addPassengerRow(p.name, p.age, p.gender, p.seat, p.phone, p.email);
        });
    }

    // Scroll to the form
    window.scrollTo({top: 0, behavior: 'smooth'});
}

</script>
</body>
</html>
