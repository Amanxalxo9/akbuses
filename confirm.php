
<?php
// confirm.php
date_default_timezone_set('Asia/Kolkata');

function fail($msg) {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Error</title></head><body>";
    echo "<h2 style='color:crimson;'>Error</h2><p>$msg</p><p><a href='javascript:history.back()'>Go back</a></p></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inde2x.php'); exit;
}

// Collect booking-level fields
$from = trim($_POST['from'] ?? '');
$to = trim($_POST['to'] ?? '');
$date = trim($_POST['date'] ?? '');
$bus_name = trim($_POST['bus_name'] ?? '');
$bus_time = trim($_POST['bus_time'] ?? '');
$price_per_seat = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$customer_name = trim($_POST['customer_name'] ?? '');
$booking_gender = strtolower(trim($_POST['gender'] ?? 'unknown'));
$boarding_point = trim($_POST['boarding_point'] ?? '');
$dropping_point = trim($_POST['dropping_point'] ?? '');

// Seat ids & prices
$seat_ids_str = trim($_POST['seat_ids'] ?? '');
$seat_prices_str = trim($_POST['seat_prices'] ?? '');

// Passenger arrays
$p_names   = $_POST['passenger_name'] ?? [];
$p_ages    = $_POST['passenger_age'] ?? [];
$p_genders = $_POST['passenger_gender'] ?? [];
$p_seats   = $_POST['passenger_seat'] ?? [];
$p_phones  = $_POST['passenger_phone'] ?? [];
$p_emails  = $_POST['passenger_email'] ?? [];

// Normalize seat list
function normalize_seat_list($str) {
    if (is_array($str)) return array_map('strval', $str);
    $s = trim((string)$str);
    if ($s === '') return [];
    $parts = array_filter(array_map('trim', explode(',', $s)), function($x){ return $x !== ''; });
    return array_map(function($x){ return (string)intval($x); }, $parts);
}
$selectedSeats = normalize_seat_list($seat_ids_str);
if (empty($selectedSeats) && !empty($p_seats)) {
    $selectedSeats = array_map(function($x){ return (string)intval($x); }, (array)$p_seats);
}
if (empty($selectedSeats)) fail('No seats selected.');

// Normalize prices
$seatPrices = [];
if ($seat_prices_str !== '') {
    $seatPrices = array_map('floatval', array_map('trim', explode(',', $seat_prices_str)));
}
if (count($seatPrices) !== count($selectedSeats)) {
    // fallback: uniform price
    $seatPrices = array_fill(0, count($selectedSeats), $price_per_seat);
}

// Validate passenger arrays length
$passCount = count($selectedSeats);
if (!is_array($p_names)) $p_names = [];
if (!is_array($p_ages)) $p_ages = [];
if (!is_array($p_genders)) $p_genders = [];
if (!is_array($p_seats)) $p_seats = [];
if (!is_array($p_phones)) $p_phones = [];
if (!is_array($p_emails)) $p_emails = [];

if (count($p_names) !== $passCount || count($p_ages) !== $passCount || count($p_genders) !== $passCount || count($p_seats) !== $passCount) {
    fail('Passenger details do not match number of selected seats. Please fill passenger info for each seat.');
}

// Build passenger structured array
$passengers = [];
for ($i=0;$i<$passCount;$i++) {
    $passengers[] = [
        'name'   => trim($p_names[$i] ?? ("Passenger ".($i+1))),
        'age'    => intval($p_ages[$i] ?? 0),
        'gender' => strtolower(trim($p_genders[$i] ?? 'unknown')),
        'seat'   => (string)intval($p_seats[$i] ?? $selectedSeats[$i]),
        'phone'  => trim($p_phones[$i] ?? ''),
        'email'  => trim($p_emails[$i] ?? '')
    ];
}

// ------------------
// Prevent double booking for same trip/date/bus/time
// ------------------
$existing = []; // seat -> true
$fn = 'bookings.txt';
if (file_exists($fn)) {
    $lines = file($fn, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $obj = json_decode($line, true);
        if (!is_array($obj)) continue;
        if (($obj['from'] ?? '') === $from &&
            ($obj['to'] ?? '') === $to &&
            ($obj['date'] ?? '') === $date &&
            ($obj['bus'] ?? '') === $bus_name &&
            ($obj['time'] ?? '') === $bus_time) {
            $seats = $obj['seats'] ?? [];
            foreach ($seats as $s) $existing[(string)intval($s)] = true;
        }
    }
}
$conflicts = [];
foreach ($selectedSeats as $s) {
    if (isset($existing[(string)intval($s)])) $conflicts[] = $s;
}
if (!empty($conflicts)) {
    fail("The following seat(s) are already booked for this trip: " . implode(', ', $conflicts));
}

// ------------------
// Save booking (one JSON per line), use LOCK_EX
// ------------------
$booking = [
    'id' => uniqid('bk_', true),
    'from' => $from,
    'to' => $to,
    'date' => $date,
    'bus' => $bus_name,
    'time' => $bus_time,
    'customer_name' => $customer_name,
    'customer_gender' => $booking_gender,
    'boarding' => $boarding_point,
    'dropping' => $dropping_point,
    'seats' => array_values($selectedSeats),
    'prices' => array_map(function($p){ return (string)floatval($p); }, $seatPrices),
    'total' => array_sum($seatPrices),
    'passengers' => $passengers,
    'created_at' => date('c')
];

$line = json_encode($booking, JSON_UNESCAPED_UNICODE) . PHP_EOL;
if (file_put_contents($fn, $line, FILE_APPEND | LOCK_EX) === false) {
    fail('Could not save booking - file write error.');
}

// Show confirmation

// ---------------------

// Email Confirmation
// ---------------------
/*$aman = "user@example.com";   // <-- Replace with customer's email field when added
$subject = "Bus Ticket Confirmation - $bus_name";

$message = "
Booking Confirmed!\n
Customer: $customer_name_s
Route: $from → $to
Date: $date
Time: $bus_time
Seats: $seat_ids_save
Total Fare: ₹$totalFare
";

$headers = "From: noreply@yourbus.com";

@mail($to, $subject, $message, $headers);
//___________________________________________________
*/
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Booking Confirmed</title>
<style>body{font-family:Arial;background:#f4f6f8;padding:20px} .box{max-width:800px;margin:30px auto;background:#fff;padding:20px;border-radius:8px} .pass{border:1px solid #eee;padding:10px;border-radius:6px;margin-bottom:8px}</style>
</head><body>
<div class="box">
  <h1 style="color:green">Booking Confirmed ✅</h1>
  <p><strong>Booking ID:</strong> <?= htmlspecialchars($booking['id']) ?></p>
  <p><strong>Booked by:</strong> <?= htmlspecialchars($booking['customer_name']) ?> (<?= htmlspecialchars($booking['customer_gender']) ?>)</p>
  <p><strong>Route:</strong> <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></p>
  <p><strong>Date & Time:</strong> <?= htmlspecialchars($date) ?> — <?= htmlspecialchars($bus_time) ?></p>
  <p><strong>Boarding:</strong> <?= htmlspecialchars($boarding_point) ?> &nbsp; <strong>Dropping:</strong> <?= htmlspecialchars($dropping_point) ?></p>
  <p><strong>Seats:</strong> <?= htmlspecialchars(implode(',', $booking['seats'])) ?></p>
  <p><strong>Total Paid:</strong> ₹<?= number_format($booking['total'],2) ?></p>
  <hr>
  <h3>Passengers</h3>
  <?php foreach ($booking['passengers'] as $p): ?>
    <div class="pass">
      <div><strong>Name:</strong> <?= htmlspecialchars($p['name']) ?></div>
      <div><strong>Age:</strong> <?= htmlspecialchars($p['age']) ?></div>
      <div><strong>Gender:</strong> <?= htmlspecialchars($p['gender']) ?></div>
      <div><strong>Seat:</strong> <?= htmlspecialchars($p['seat']) ?></div>
      <div><strong>Phone:</strong> <?= htmlspecialchars($p['phone']) ?></div>
      <div><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></div>
    </div>
  <?php endforeach; ?>
  <p><a href="index2.php">Book another</a> | <a href="admin.php">Admin</a> | <a href="#" onclick="window.print()">Print</a></p>
</div>
</body></html>
