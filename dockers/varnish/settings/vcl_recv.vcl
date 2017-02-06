if (req.request != "GET" &&
    req.request != "HEAD") {
      return (pass);
}

# Use anonymous, cached pages if all backends are down.
if (!req.backend.healthy) {
  unset req.http.Cookie;
}

# Allow the backend to serve up stale content if it is responding slowly.
set req.grace = 6h;

# Pipe these paths directly to Apache for streaming.
if (req.url ~ "^/admin/content/backup_migrate/export") {
  return (pipe);
}

if (req.restarts == 0) {
  if (req.http.x-forwarded-for) {
    set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
  }
  else {
    set req.http.X-Forwarded-For = client.ip;
  }
}

# Do not cache these paths.
if (req.url ~ "^/status\.php$" ||
    req.url ~ "^/update\.php$" ||
    req.url ~ "^/admin$" ||
    req.url ~ "^/admin/.*$" ||
    req.url ~ "^/flag/.*$" ||
    req.url ~ "^.*/ajax/.*$" ||
    req.url ~ "^/esi/.*$" ||
    req.url ~ "^/user/.*$" ||
    req.url ~ "/user" ||
    req.url ~ "^.*/ahah/.*$") {
     return (pass);
}

# Do not allow outside access to cron.php or install.php.
#if (req.url ~ "^/(cron|install)\.php$" && !client.ip ~ internal) {
  # Have Varnish throw the error directly.
  #  error 404 "Page not found.";
  # Use a custom error page that you've defined in Drupal at the path "404".
  # set req.url = "/404";
#}

# Always cache the following file types for all users. This list of extensions
# appears twice, once here and again in vcl_fetch so make sure you edit both
# and keep them equal.
if (req.url ~ "(?i)\.(pdf|asc|dat|txt|doc|xls|ppt|tgz|csv|png|gif|jpeg|jpg|ico|swf|css|js)(\?.*)?$") {
  unset req.http.Cookie;
}

# Remove all cookies that Drupal doesn't need to know about. We explicitly
# list the ones that Drupal does need, the SESS and NO_CACHE. If, after
# running this code we find that either of these two cookies remains, we
# will pass as the page cannot be cached.
set req.http.X-Cookie-Debug = req.http.Cookie;

# Filter the Cookies if exist.
if (req.http.Cookie) {

  # Append a semi-colon to the front of the cookie string.
  set req.http.Cookie = ";" + req.http.Cookie;

  # Remove all spaces that appear after semi-colons.
  set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
  set req.http.Cookie = regsuball(req.http.Cookie, ";(SESS[a-z0-9]+|SSESS[a-z0-9]+|NO_CACHE)=", "; \1=");
  set req.http.Cookie = regsuball(req.http.Cookie, ";(RSESS[a-z0-9]+|SSESS[a-z0-9]+|NO_CACHE)=", "; \1=");

  # Lullabot: remove has_js or Drupal.toolbar.collapsed cookies.
  # Match the cookies we want to keep, adding the space we removed previously
  # back. (\1) is first matching group in the regsuball.
  set req.http.Cookie = regsuball(req.http.Cookie, "(^|:\s*)(__[a-z]+|has_js|Drupal.toolbar.collapsed)=[^;]*", "");
  # Remove table drag cookie.
  set req.http.cookie = regsub(req.http.cookie, "Drupal.tableDrag.showWeight=[^;]+(; )?", "");
  # Remove _ga cookie.
	set req.http.cookie = regsub(req.http.cookie, "_ga=[^;]+(; )?", "");

  # 4. Remove all other cookies, identifying them by the fact that they have.
  # set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
  # set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");

  # 5. Remove all spaces and semi-colons from the beginning and end of the
  #    no space after the preceding semi-colon.
  set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|([; ]+$)", "");
	
  if (req.http.Cookie == "") {
    # If there are no remaining cookies, remove the cookie header. If there
    # aren't any cookie headers, Varnish's default behavior will be to cache
    # the page.
    unset req.http.Cookie;
    return (lookup);
  }
  else {
      return (pass);

  }
}

# Default condition: if (req.http.Authorization || req.http.Cookie) {...}
# Use pass for authorization only.
if (req.http.Authorization) {
  /* Not cacheable by default */
  return (pass);
}
