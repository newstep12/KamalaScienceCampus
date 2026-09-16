/* Kamala Science Campus — shared behaviour */
(function () {
  "use strict";

  // Mark the current page in the navigation
  var page = document.body.getAttribute("data-page");
  if (page) {
    var links = document.querySelectorAll('.nav a[data-nav="' + page + '"]');
    for (var i = 0; i < links.length; i++) {
      links[i].setAttribute("aria-current", "page");
    }
  }

  // Mobile navigation
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.querySelector(".nav");

  if (toggle && nav) {
    // Each header carries its own labels, so they stay in the page's language.
    var labelOpen = toggle.getAttribute("data-label-open") || "✕ Close";
    var labelClosed = toggle.getAttribute("data-label-closed") || "☰ Menu";

    toggle.addEventListener("click", function () {
      var open = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.textContent = open ? labelOpen : labelClosed;
    });

    nav.addEventListener("click", function (e) {
      // Matches the menu-button breakpoint in styles.css
      if (e.target.tagName === "A" && window.innerWidth <= 1120) {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.textContent = labelClosed;
      }
    });
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
  if (year) year.textContent = new Date().getFullYear();

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
