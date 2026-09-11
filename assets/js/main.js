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
    toggle.addEventListener("click", function () {
      var open = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.textContent = open ? "✕ Close" : "☰ Menu";
    });

    nav.addEventListener("click", function (e) {
      if (e.target.tagName === "A" && window.innerWidth <= 820) {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.textContent = "☰ Menu";
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
