/**
 * @file
 * Used to call loadCSS so css doesn't block the browser.
 */

var urlMatcher = new RegExp('href="(.*?)"');
[].forEach.call(document.querySelectorAll('noscript'), function(el) {
  loadCSS(urlMatcher.exec(el.innerHTML)[1]);
})
