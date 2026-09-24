/* Kamala Science Campus — the visitor's language.
   Loaded in <head>, before anything is drawn, so a visitor who prefers the
   other language is taken there before this page appears or downloads its
   images. Each page names its counterpart in <meta name="alt-page">. */
(function () {
  "use strict";

  function cookie(name) {
    var m = document.cookie.match(new RegExp("(?:^|; )" + name + "=([^;]*)"));
    return m ? decodeURIComponent(m[1]) : null;
  }
  function known(lang) {
    return lang === "en" || lang === "ne" ? lang : null;
  }

  // Choosing a language with the switch in the top bar is remembered for a
  // year (site_lang). A middle click, into a new tab, counts as a choice too.
  function remember(e) {
    var a = e.target && e.target.closest ? e.target.closest(".lang-switch") : null;
    if (!a) return;
    var lang = known(a.getAttribute("hreflang"));
    if (lang) document.cookie = "site_lang=" + lang + "; path=/; max-age=31536000; SameSite=Lax";
    // Stay at the same place: the two languages share their anchors.
    if (window.location.hash) a.href = a.href.split("#")[0] + window.location.hash;
  }
  document.addEventListener("click", remember);
  document.addEventListener("auxclick", remember);

  var alt = document.querySelector('meta[name="alt-page"]');
  if (!alt) return;

  // Only on arrival from outside the site (a search, a bookmark, a shared
  // link). A page reached from one of the site's own links, the language
  // switch included, is the page the visitor asked for — even when their
  // choice could not be saved because cookies are blocked.
  var ref = document.referrer;
  if (ref && ref.indexOf(window.location.origin + "/") === 0) return;

  var here = document.documentElement.getAttribute("lang") === "ne" ? "ne" : "en";
  // English is the default: only a language the visitor chose with the
  // switch moves them, never their browser's settings.
  var wanted = known(cookie("site_lang"));
  if (wanted && wanted !== here) {
    var a = document.createElement("a");
    a.href = alt.getAttribute("content");
    window.location.replace(a.href.split("#")[0] + window.location.hash);
  }
})();
