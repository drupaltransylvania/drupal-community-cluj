# Activate Edge Side Includes.
set beresp.do_esi = true;

# We need this to cache 404s, 301s, 500s. Otherwise, depending on backend but
# definitely in Drupal's case these responses are not cacheable by default.
if (beresp.status == 404 || beresp.status == 301 || beresp.status == 500) {
  set beresp.ttl = 10m;
}
else if (beresp.http.Cache-Control != "(private|no-cache)") {
   # In case if the page can be cached set the ttl for 1h.		
   set beresp.ttl = 1h;
}

# Don't allow static files to set cookies.
# (?i) denotes case insensitive in PCRE (perl compatible regular expressions).
# This list of extensions appears twice, once here and again in vcl_recv so
# make sure you edit both and keep them equal.
if (req.url ~ "(?i)\.(pdf|asc|dat|txt|doc|xls|ppt|tgz|csv|png|gif|jpeg|jpg|ico|swf|css|js)(\?.*)?$") {
  unset beresp.http.set-cookie;
}

# Allow items to be stale if needed.
set beresp.grace = 6h;

if (beresp.ttl <= 0s ||
  beresp.http.Vary == "*") {
  /*
   * Mark as "Hit-For-Pass" for the next 2 minutes
   */
set beresp.http.X-Cookie-Vary = beresp.ttl;
  set beresp.ttl = 120 s;
  return (hit_for_pass);
}

return (deliver);
