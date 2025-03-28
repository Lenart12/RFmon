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