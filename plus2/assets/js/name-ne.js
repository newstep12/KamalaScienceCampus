/* Shree Kamala Secondary School portal — the Nepali name, written for you.
   On the forms that ask for a name in both scripts, the Nepali box shows, in
   grey, the Devanagari that portal/name-ne.php writes from the English name,
   updated as the English name is typed — the spelling the card prints when
   the box is left empty. It is only ever the box's placeholder: typing in the
   box is how a student puts their own spelling on the card, and nothing here
   writes a value that would then be saved as if they had typed it. */
(function () {
  "use strict";
  var en = document.getElementById("full_name");
  var ne = document.getElementById("full_name_ne");
  if (!en || !ne || !window.fetch) return;
  var url = ne.getAttribute("data-suggest");
  if (!url) return;

  var timer = null;
  var asked = "";

  function suggest() {
    var name = en.value.trim();
    if (name === asked) return;
    asked = name;
    if (name === "") {
      ne.placeholder = "";
      return;
    }
    fetch(url + "?name=" + encodeURIComponent(name), { credentials: "same-origin" })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        // Only the answer to what is in the English box now.
        if (data && en.value.trim() === name) ne.placeholder = data.ne || "";
      })
      .catch(function () { /* the box simply keeps its placeholder */ });
  }

  en.addEventListener("input", function () {
    clearTimeout(timer);
    timer = setTimeout(suggest, 250);
  });
  suggest();
})();
