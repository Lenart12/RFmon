// On load, add event listener to all audio elements
window.addEventListener('load', () => {
  document.querySelectorAll('audio').forEach(function(audio) {
    audio.addEventListener('ended', function() {
      if (!document.getElementById('auto-play').checked) {
        return;
      }
      var next = audio.closest('.record').nextElementSibling;
      if (next && next.querySelector('audio')) {
        next.querySelector('audio').play();
      }
    });
  });

  // Date picker functionality
  function setupDatePicker(pickerId, buttonId) {
    const picker = document.getElementById(pickerId);
    const button = document.getElementById(buttonId);
    
    if (picker && button) {
      button.addEventListener('click', function() {
        if (picker.value) {
          // Get the selected date
          const selectedDate = new Date(picker.value);
          
          // Calculate date 3 days before
          const fromDate = new Date(selectedDate);
          fromDate.setDate(selectedDate.getDate() - 2);
          
          // Calculate end of selected day
          const toDate = new Date(selectedDate);
          toDate.setHours(23, 59, 59, 999);
          
          // Convert to timestamps
          const fromTimestamp = Math.floor(fromDate.getTime() / 1000);
          const toTimestamp = Math.floor(toDate.getTime() / 1000);
          
          // Navigate to the new page
          window.location.href = `?from=${fromTimestamp}&to=${toTimestamp}`;
        }
      });
    }
  }
  
  // Set up both date pickers
  setupDatePicker('date-picker', 'date-picker-button');
  setupDatePicker('date-picker-bottom', 'date-picker-button-bottom');

  (async () => {
    const latest_audio = document.getElementById('latest-audio').value;
    while (true) {
      const req = await fetch('poll.php')
      const latest = await req.text();
      console.log(latest, latest_audio);
      if (latest !== latest_audio) {
        document.getElementById('new-audio').hidden = false;
        break;
      }
      await new Promise(resolve => setTimeout(resolve, 5000));
    }
  })()
});