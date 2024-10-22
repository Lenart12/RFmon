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
});
