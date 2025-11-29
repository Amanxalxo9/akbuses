<?php
// book.php — Seat selection page with admin integration

// Incoming trip info
$bus_id   = $_POST['bus_id']   ?? '';
$bus_name = $_POST['bus_name'] ?? 'Bus Name';
$bus_time = $_POST['bus_time'] ?? '09:00 PM';
$price    = isset($_POST['price']) ? floatval($_POST['price']) : 500;
$from     = $_POST['from']     ?? 'From';
$to       = $_POST['to']       ?? 'To';
$date     = $_POST['date']     ?? date('Y-m-d');

// Layout configuration
$lowerRows = 5;  // 5 rows * 4 seats = 20 lower seats
$upperRows = 5;  // 5 rows * 2 sleepers = 10 upper seats
//$lowerSeatsCount = $lowerRows * 3;
//$upperSeatsCount = $upperRows * 3;

$seaterPrice  = $price;
$sleeperPrice = $price + 50;



// -----------------
// Load bookings from JSON file
// -----------------
$bookedSeats = []; // seat_number => gender

if (file_exists('bookings.txt')) {
    $lines = file('bookings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $b = json_decode($line, true);
        if (!$b) continue;

        // Match current trip
        if (
            ($b['from'] ?? '') === $from &&
            ($b['to'] ?? '') === $to &&
            ($b['date'] ?? '') === $date &&
            ($b['bus'] ?? '') === $bus_name &&
            ($b['time'] ?? '') === $bus_time
        ) {
            foreach ($b['seats'] ?? [] as $idx => $seat) {
                $gender = $b['passengers'][$idx]['gender'] ?? 'unknown';
                $bookedSeats[$seat] = strtolower($gender);
            }
        }
    }
}

$bookedSeatsForJs = $bookedSeats;

// -----------------
// Generate seat list
// -----------------
$seatList = [];
$totalLowerSeats = $lowerRows * 4;
$totalUpperSeats = $upperRows * 2;
$totalSeats = $totalLowerSeats + $totalUpperSeats;

for ($i = 1; $i <= $totalSeats; $i++) {
    $seatList[] = [
        'seat_number' => $i,
        'status' => isset($bookedSeats[$i]) ? 'booked' : 'available'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Select Seats — <?= htmlspecialchars($bus_name) ?></title>
<style>
body { font-family: Arial, Helvetica, sans-serif; background:#f4f6f8; color:#222; }
.container { max-width:1000px; margin:20px auto; padding:16px; background:#fff; border-radius:8px; box-shadow:0 4px 18px rgba(0,0,0,0.06); }
h1 { margin:0 0 8px 0; font-size:20px; }
.meta { color:#555; margin-bottom:16px; }
.layout { display:flex; gap:24px; justify-content:center; margin-bottom:12px; }
.column { display:flex; flex-direction:column; gap:8px; align-items:center; }
.row { display:flex; gap:8px; }
.seat {
    width:60px; height:100px; background:#e8e8e8; border-radius:8px;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    cursor:pointer; font-weight:700; position:relative;
}
.seat .num { font-size:18px; }
.seat .price { font-size:12px; position:absolute; bottom:8px; }
.seat.selected { background:#1adb47; color:white; }
.seat.booked { cursor:not-allowed; }
.seat.booked-male { background:#2e86de; color:white; }
.seat.booked-female { background:#ff69b4; color:white; }
.seat.booked-unknown { background:#9e9e9e; color:white; }
.legend { display:flex; gap:12px; justify-content:center; margin-top:8px; margin-bottom:16px; }
.legend div { display:flex; gap:6px; align-items:center; font-size:13px; color:#333; }
.legend span { width:18px; height:18px; display:inline-block; border-radius:4px; }
.form-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
.passenger-box { background:#fff; padding:12px; border-radius:8px; border:1px solid #eee; margin-bottom:10px; }
.btn { padding:10px 14px; background:#28a745; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:700; }
label { font-weight:600; margin-right:6px; }
select, input[type=text], input[type=number], input[type=email] { padding:6px 8px; border-radius:4px; border:1px solid #ccc; }
</style>
</head>
<body>
<div class="container">
<h1><?= htmlspecialchars($bus_name) ?></h1>
<div class="meta"><?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?> &nbsp; | &nbsp; <?= htmlspecialchars($date) ?> &nbsp; | &nbsp; <?= htmlspecialchars($bus_time) ?></div>

<form action="confirm.php" method="POST" id="bookingForm">
<input type="hidden" name="bus_id" value="<?= htmlspecialchars($bus_id) ?>">
<input type="hidden" name="bus_name" value="<?= htmlspecialchars($bus_name) ?>">
<input type="hidden" name="bus_time" value="<?= htmlspecialchars($bus_time) ?>">
<input type="hidden" name="price" value="<?= htmlspecialchars($price) ?>">
<input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
<input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
<input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
<input type="hidden" name="seat_ids" id="seat_ids">
<input type="hidden" name="seat_prices" id="seat_prices">

<div class="form-row" style="justify-content:center;">
<label>Your Name:</label>
<input type="text" name="customer_name" required>
<label style="margin-left:12px;">Booking Gender:</label>
<select name="gender" required>
<option value="male">Male</option>
<option value="female">Female</option>
</select>
</div>

<div class="form-row" style="justify-content:center;">
<label>Boarding:</label>
<select name="boarding_point" required>
<option value="">Select boarding</option>
<option>Raipur Bus Stand</option>
<option>Pandri Bus Stand</option>
<option>Tatibandh</option>
<option>Telibandha</option>
</select>

<label style="margin-left:12px;">Dropping:</label>
<select name="dropping_point" required>
<option value="">Select dropping</option>
<option>Mumbai Central</option>
<option>Dadar</option>
<option>Borivali</option>
<option>Thane</option>
</select>
</div>

<div style="text-align:center; margin-top:10px; font-weight:700;">Select Seats</div><br>
<div style="text-align:center; font-weight: 700;">Lower Deck</div><br>
<div class="layout">
<div class="column" id="leftColumn"></div>
<div style="width:24px"></div>
<div class="column" id="rightColumn"></div>
</div>

<div class="legend">
<div><span style="background:#e8e8e8"></span> Available</div>
<div><span style="background:#1adb47"></span> Selected</div>
<div><span style="background:#2e86de"></span> Booked (Male)</div>
<div><span style="background:#ff69b4"></span> Booked (Female)</div>
<div><span style="background:#9e9e9e"></span> Booked (Unknown)</div>
</div>

<hr>
<h3>Passenger Details</h3>
<div id="passengersContainer"></div>

<div style="text-align:center; margin-top:14px;">
<button type="submit" class="btn">Confirm Booking</button>
</div>
</form>
</div>

<script>
const bookedSeats = <?= json_encode($bookedSeatsForJs) ?>;
const seaterPrice = <?= $seaterPrice ?>;
const sleeperPrice = <?= $sleeperPrice ?>;

let selectedSeats = [];
let selectedPrices = [];

const seatIdsInput = document.getElementById('seat_ids');
const seatPricesInput = document.getElementById('seat_prices');
const passengersContainer = document.getElementById('passengersContainer');

function updatePassengers(){
    seatIdsInput.value = selectedSeats.join(',');
    seatPricesInput.value = selectedPrices.join(',');

    passengersContainer.innerHTML = '';
    selectedSeats.forEach((seat,i)=>{
        const box = document.createElement('div');
        box.className='passenger-box';
        box.innerHTML=`
        <h4>Passenger ${i+1} — Seat ${seat}</h4>
        <div class="form-row"><label>Name:</label><input type="text" name="passenger_name[]" required></div>
        <div class="form-row"><label>Age:</label><input type="number" name="passenger_age[]" min="0" required></div>
        <div class="form-row"><label>Gender:</label>
        <select name="passenger_gender[]" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select></div>
        <input type="hidden" name="passenger_seat[]" value="${seat}">
        `;
        passengersContainer.appendChild(box);
    });
}

function createSeatEl(seatNo){
    const div = document.createElement('div');
    div.className='seat';
    div.dataset.seat=seatNo;
    div.innerHTML=`<div class="num">${seatNo}</div><div class="price">${seatNo<=(<?= $lowerRows ?>*4)?'₹'+seaterPrice:'₹'+sleeperPrice}</div>`;

    if(bookedSeats[seatNo]){
        div.classList.add('booked');
        const g = bookedSeats[seatNo];
        if(g==='male') div.classList.add('booked-male');
        else if(g==='female') div.classList.add('booked-female');
        else div.classList.add('booked-unknown');
    }

    div.addEventListener('click',()=>{
        if(div.classList.contains('booked')) return;
        const s = seatNo.toString();
        if(div.classList.contains('selected')){
            div.classList.remove('selected');
            selectedSeats = selectedSeats.filter(x=>x!==s);
            selectedPrices.pop();
        } else {
            div.classList.add('selected');
            selectedSeats.push(s);
            selectedPrices.push(seatNo<=(<?= $lowerRows ?>*4)?seaterPrice:sleeperPrice);
        }
        updatePassengers();
    });

    return div;
}

// Build layout
const leftCol = document.getElementById('leftColumn');
const rightCol = document.getElementById('rightColumn');
let idx=1;

// Lower deck
for(let r=0;r<<?= $lowerRows ?>;r++){
    const leftRow = document.createElement('div'); leftRow.className='row';
    const rightRow = document.createElement('div'); rightRow.className='row';
    for(let i=0;i<2;i++) leftRow.appendChild(createSeatEl(idx++));
    for(let i=0;i<2;i++) rightRow.appendChild(createSeatEl(idx++));
    leftCol.appendChild(leftRow);
    rightCol.appendChild(rightRow);
}

// Upper deck
for(let r=0;r<<?= $upperRows ?>;r++){
    const leftRow = document.createElement('div'); leftRow.className='row';
    const rightRow = document.createElement('div'); rightRow.className='row';
    leftRow.appendChild(createSeatEl(idx++));
    rightRow.appendChild(createSeatEl(idx++));
    leftCol.insertBefore(leftRow,leftCol.firstChild);
    rightCol.insertBefore(rightRow,rightCol.firstChild);
}

updatePassengers();

// Prevent submit without seats
document.getElementById('bookingForm').addEventListener('submit',e=>{
    if(!seatIdsInput.value || seatIdsInput.value.trim()===''){
        e.preventDefault();
        alert('Please select at least one seat.');
    }
});

// Poll every 5 seconds
setInterval(() => {
    fetch(`check_seats.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&date=<?= urlencode($date) ?>&bus=<?= urlencode($bus_name) ?>&time=<?= urlencode($bus_time) ?>`)
    .then(res => res.json())
    .then(data => {
        // Update bookedSeats map
        for (const seatNo in data) {
            bookedSeats[seatNo] = data[seatNo];
        }

        // Update seat elements
        document.querySelectorAll('.seat').forEach(div => {
            const s = div.dataset.seat;
            if (bookedSeats[s]) {
                div.classList.add('booked');
                div.classList.remove('selected');

                const g = bookedSeats[s];
                div.classList.remove('booked-male','booked-female','booked-unknown');
                if (g==='male') div.classList.add('booked-male');
                else if(g==='female') div.classList.add('booked-female');
                else div.classList.add('booked-unknown');

                // Remove from selectedSeats if user had selected it
                selectedSeats = selectedSeats.filter(x=>x!==s);
                selectedPrices = selectedPrices.filter((_,i)=>i<selectedSeats.length);
                updatePassengers();
            }
        });
    });
}, 5000); // 5 seconds

</script>
</body>
</html>
