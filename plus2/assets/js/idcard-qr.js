// Draws the QR code on the back of each identity card: the school's location,
// which a phone camera opens in Google Maps. The link comes from the page
// (data-qr), set by the school office under System → School details.
//
// Drawn in the browser by qrcode.js (Kazuhiko Arase, MIT licence), which is
// kept beside this file rather than loaded from a CDN, so a card still prints
// its code on a connection that cannot reach one.
(function () {
  if (typeof qrcode !== 'function') { return; }
  document.querySelectorAll('[data-qr]').forEach(function (el) {
    var text = el.getAttribute('data-qr');
    if (!text) { return; }
    try {
      var qr = qrcode(0, 'M');        // smallest version that fits, 15% error correction
      qr.addData(text);
      qr.make();
      // Scalable: the SVG fills the box CSS gives it, so it prints sharp at
      // the card's real size whatever the screen's.
      el.innerHTML = qr.createSvgTag({ cellSize: 1, margin: 0, scalable: true });
      el.classList.add('drawn');
    } catch (e) {
      // A link too long to encode leaves the box empty rather than breaking
      // the rest of the page.
    }
  });
})();
