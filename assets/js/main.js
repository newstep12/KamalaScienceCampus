/* Kamala Science Campus — shared behaviour */
(function () {
  "use strict";

  // Mark the current page in the side menu and the top bar
  var page = document.body.getAttribute("data-page");
  if (page) {
    var links = document.querySelectorAll('.side-nav a[data-nav="' + page + '"], .top-actions a[data-nav="' + page + '"]');
    for (var i = 0; i < links.length; i++) {
      links[i].setAttribute("aria-current", "page");
    }
  }

  // The side menu as a drawer, on screens too narrow to show it beside the
  // page (the 960px breakpoint in styles.css)
  var toggle = document.querySelector(".side-toggle");
  var side = document.getElementById("side-nav");
  var scrim = document.querySelector(".side-scrim");

  if (toggle && side) {
    var drawer = window.matchMedia("(max-width: 960px)");
    // While the drawer is open the page behind it is out of reach, for the
    // keyboard and screen readers as well as the eye.
    var behind = document.querySelectorAll(".skip, .sh, .site-main, .site-footer");

    var setOpen = function (open) {
      for (var b = 0; b < behind.length; b++) {
        if (open) behind[b].setAttribute("inert", "");
        else behind[b].removeAttribute("inert");
      }
      side.classList.toggle("open", open);
      document.body.classList.toggle("side-open", open);
      if (scrim) scrim.hidden = !open;
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.setAttribute("aria-label", toggle.getAttribute(open ? "data-label-close" : "data-label-open"));
      if (open) {
        var first = side.querySelector("a");
        if (first) first.focus();
      }
    };

    toggle.addEventListener("click", function () {
      setOpen(!side.classList.contains("open"));
    });
    if (scrim) scrim.addEventListener("click", function () { setOpen(false); });
    var close = side.querySelector(".side-close");
    if (close) {
      close.addEventListener("click", function () {
        setOpen(false);
        toggle.focus();
      });
    }
    side.addEventListener("click", function (e) {
      if (drawer.matches && e.target.closest("a")) setOpen(false);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && side.classList.contains("open")) {
        setOpen(false);
        toggle.focus();
      }
    });
    // Widening the window past the breakpoint puts the menu back in place.
    var reset = function () { if (!drawer.matches) setOpen(false); };
    if (drawer.addEventListener) drawer.addEventListener("change", reset);
    else if (drawer.addListener) drawer.addListener(reset);
  }

  // Notice attachments: fill the preview frame the first time it is opened.
  // A notice board can list sixty PDFs, and loading them all on arrival would
  // cost a visitor on mobile data far more than the page is worth. The buttons
  // beside the preview open the same file, so nothing here is load-bearing.
  var previews = document.querySelectorAll("details.p-preview[data-preview-src]");
  for (var p = 0; p < previews.length; p++) {
    previews[p].addEventListener("toggle", function () {
      if (!this.open) return;
      var frame = this.querySelector("iframe");
      if (frame && !frame.getAttribute("src")) {
        frame.setAttribute("src", this.getAttribute("data-preview-src"));
      }
    });
  }

  // Current year in the footer
  var year = document.querySelector("[data-year]");
  if (year) {
    var y = String(new Date().getFullYear());
    // Nepali pages write the year in Devanagari digits.
    if (document.documentElement.getAttribute("lang") === "ne") {
      y = y.replace(/[0-9]/g, function (d) { return "०१२३४५६७८९".charAt(+d); });
    }
    year.textContent = y;
  }

  // Enquiry form: no server is attached, so hand the message to the
  // visitor's mail client instead of silently losing it.
  var form = document.querySelector("[data-enquiry-form]");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var data = new FormData(form);
      var to = form.getAttribute("data-mailto") || "";
      var subject =
        "Website enquiry — " + (data.get("interest") || "General");
      var body = [
        "Name: " + (data.get("name") || ""),
        "Email: " + (data.get("email") || ""),
        "Phone: " + (data.get("phone") || ""),
        "Interested in: " + (data.get("interest") || ""),
        "",
        "Message:",
        data.get("message") || ""
      ].join("\n");

      var status = form.querySelector(".form-status");
      if (status) {
        status.textContent =
          "Opening your email app with this enquiry ready to send…";
      }

      window.location.href =
        "mailto:" + to +
        "?subject=" + encodeURIComponent(subject) +
        "&body=" + encodeURIComponent(body);
    });
  }
})();
