document.addEventListener('DOMContentLoaded', () => {
  const fromSelect = document.getElementById('from');
  const toSelect = document.getElementById('to');
  let routeData = {};

  fetch('routes.json')
    .then(response => response.json())
    .then(data => {
      routeData = data;
      fromSelect.innerHTML = '<option value="">Select Origin</option>';
      Object.keys(routeData).forEach(city => {
        const option = document.createElement('option');
        option.value = city;
        option.textContent = city;
        fromSelect.appendChild(option);
      });
    })
    .catch(err => {
      alert("Could not load routes.");
      console.error(err);
    });

  fromSelect.addEventListener('change', () => {
    const selectedFrom = fromSelect.value;
    toSelect.innerHTML = '<option value="">Select Destination</option>';

    if (routeData[selectedFrom]) {
      routeData[selectedFrom].forEach(dest => {
        const option = document.createElement('option');
        option.value = dest;
        option.textContent = dest;
        toSelect.appendChild(option);
      });
    }
  });
});
document.getElementById('swapButton').addEventListener('click', () => {
  const fromSelect = document.getElementById('from');
  const toSelect = document.getElementById('to');

  const fromValue = fromSelect.value;
  const toValue = toSelect.value;

  // Swap values
  fromSelect.value = toValue;

  // Trigger change event to update "To" options
  fromSelect.dispatchEvent(new Event('change'));

  // Wait for options to update, then set destination
  setTimeout(() => {
    toSelect.value = fromValue;
  }, 100); // Slight delay to let DOM update first
});