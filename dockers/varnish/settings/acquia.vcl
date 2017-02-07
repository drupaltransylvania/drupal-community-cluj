# 2015042801
# Default Varnish cache policy for Acquia Hosting

# The default backend is specified in another file and should not be declared here.
# All other backends may be declared here.

# Incoming requests: Decide whether to try cache or not
sub vcl_recv {
  # Pipe all requests for files whose Content-Length is >=10,000,000. See
  # comment in vcl_fetch.
  if (req.http.x-pipe && req.restarts > 0) {
    return(pipe);
  }

  # Pipe all websocket requests.
  if (req.http.Upgrade ~ "(?i)websocket") {
    return(pipe);
  }

  if (req.http.X-AH-Redirect) {
    error 751 req.http.X-AH-Redirect;
  }

  # Grace: Avoid thundering herd when an object expires by serving
  # expired stale object during the next N seconds while one request
  # is made to the backend for that object.
  set req.grace = 120s;

  # EXAMPLE: How to do an external redirect in Varnish
  # in case we need to redirect a site to another balancer.
  # if (req.http.host ~ "^(www.)?example.com$") {
  #  error 302;
  # }

  # Varnish doesn't support Range requests: needs to be piped
  if (req.http.Range) {
    return(pipe);
  }

  # PURGE method support: PURGE request must include
  # a X-Acquia-Purge header which is weak but better
  # than nothing or ip address whack-a-mole. Example:
  # curl -X PURGE -H "X-Acquia-Purge: sitename" http://site/file
  if (req.request == "PURGE") {
    if (!req.http.X-Acquia-Purge) {
      error 405 "Not allowed.";
    }
    return(lookup);
  }

  # Don't Cache executables or archives
  # This was put in place to ensure these objects are piped rather then passed to the backend.
  # We had a customer who had a 500+MB file *.msi that Varnish was choking on,
  # so we decided to pipe all archives and executables to keep them from choking Varnish.
  if (req.url ~ "\.(msi|exe|dmg|zip|tgz|gz)") {
    return(pipe);
  }

  # Don't check cache for POSTs and various other HTTP request types
  if (req.request != "GET" && req.request != "HEAD") {
    return(pass);
  }

  # Find out if the request is pinned to a specific device and store it for later.
  if (req.http.Cookie ~ "desktop") {
    set req.http.X-pinned-device = "desktop";
  }
  else if (req.http.Cookie ~ "mobile") {
    set req.http.X-pinned-device = "mobile";
  }
  else if (req.http.Cookie ~ "tablet") {
    set req.http.X-pinned-device = "tablet";
  }

  # Always cache the following file types for all users if not coming from the private file system.
  if (req.url ~ "(?i)/(modules|themes|files|libraries)/.*\.(png|gif|jpeg|jpg|ico|swf|css|js|flv|f4v|mov|mp3|mp4|pdf|doc|ttf|eot|ppt|ogv|woff)(\?[a-z0-9]+)?$" && req.url !~ "/system/files") {
    unset req.http.Cookie;
    # Set header so we know to remove Set-Cookie later on.
    set req.http.X-static-asset = "True";
  }

  # Don't check cache for cron.php
  if (req.url ~ "^/cron.php") {
    return(pass);
  }

  # NOTE: xmlrpc.php requests are not cached because they're POSTs

  # Don't check cache for feedburner or feedvalidator for ise
  if ((req.http.host ~ "^(www\.|web\.)?ise") &&
      (req.http.User-Agent ~ "(?i)feed")) {
       return(pass);
  }

  # If the Drupal 8 persistent login module is in use, 
  # bypass the cache.
  if (req.http.cookie ~ "(^|;\s*)(P?PL[a-zA-Z0-9]*)=") {
    return(pass);
  }

  # This is part of Varnish's default behavior to pass through any request that
  # comes from an http auth'd user.
  if (req.http.Authorization) {
    return(pass);
  }

  # Don't check cache if the Drupal session cookie is set.
  # Pressflow pages don't send this cookie to anon users.
  if (req.http.cookie ~ "(^|;\s*)(S?SESS[a-zA-Z0-9]*)=") {
    return(pass);
  }

  # Enforce no-cookie-vary: Hide the Cookie header prior
  # to vcl_hash, then restore Cookie if we get to vcl_miss.
  # BUG: Varnish is truncates the X-Acquia-Cookie var
  if (req.http.Cookie) {
    set req.http.X-Acquia-Cookie = req.http.cookie;
    unset req.http.Cookie;
  }

  # Pass requests from simpletest to drupal.
  if (req.http.User-Agent ~ "simpletest") {
    return(pipe);
  }

  # Default cache check
  return(lookup);
}


# Cache hit: the object was found in cache
sub vcl_hit {
  if (req.request == "PURGE") {
    purge;
    error 200 "Purged.";
  }
}

# Cache miss: request is about to be sent to the backend
sub vcl_miss {
  # Restore the original incoming Cookie
  if (req.http.X-Acquia-Cookie) {
    set bereq.http.Cookie = req.http.X-Acquia-Cookie;
    unset bereq.http.X-Acquia-Cookie;
  }

  # PURGE method support
  if (req.request == "PURGE") {
    # This will allow for purging variants.
    purge;
    error 200 "Purged.";
  }
}

# Pass (including HitPass): request is about to be sent to the backend
# or about to be delivered
sub vcl_pass {
  # Restore the original incoming Cookie
  if (req.http.X-Acquia-Cookie) {
    set bereq.http.Cookie = req.http.X-Acquia-Cookie;
    unset bereq.http.X-Acquia-Cookie;
  }
}

# piped requests should not support keepalive because
# Varnish won't have chance to process or log the subrequests
sub vcl_pipe {
  if (req.http.upgrade) {
    set bereq.http.upgrade = req.http.upgrade;
  }
  else {
    set req.http.connection = "close";
  }
}

# Backend response: Determine whether to cache each backend response
sub vcl_fetch {
  # Pipe all requests for files whose Content-Length is >=10,000,000. See
  # comment in vcl_pipe.
  if ( beresp.http.Content-Length ~ "[0-9]{8,}" ) {
     set req.http.x-pipe = "1";
     return(restart);
  }

  # Avoid attempting to gzip an empty response body
  # https://www.varnish-cache.org/trac/ticket/1320
  if (beresp.http.Content-Encoding ~ "gzip" && beresp.http.Content-Length == "0") {
    unset beresp.http.Content-Encoding;
  }

  # Remove the Set-Cookie header from static assets
  # This is just for cleanliness and is also done in vcl_deliver
  if (req.http.X-static-asset) {
    unset beresp.http.Set-Cookie;
  }

  # Don't cache responses with status codes greater than 302 or
  # HEAD and POST requests.
  if (beresp.status >= 302 || !(beresp.ttl > 0s) || req.request != "GET") {
    call ah_pass;
  }

  # Make sure we are caching 301s for at least 15 mins.
  if (beresp.status == 301) {
    if (beresp.ttl < 15m) {
      set beresp.ttl = 15m;
    }
  }

  # Respect explicit no-cache headers
  if (beresp.http.Pragma ~ "no-cache" ||
      beresp.http.Cache-Control ~ "no-cache" ||
      beresp.http.Cache-Control ~ "private") {
    call ah_pass;
  }

  # Don't cache cron.php
  if (req.url ~ "^/cron.php") {
    return(hit_for_pass);
  }

  # NOTE: xmlrpc.php requests are not cached because they're POSTs

  # Don't cache if Drupal session cookie is set
  # Note: Pressflow doesn't send SESS cookies to anon users
  if (beresp.http.Set-Cookie ~ "SESS") {
    call ah_pass;
  }

  # Grace: Avoid thundering herd when an object expires by serving
  # expired stale object during the next N seconds while one request
  # is made to the backend for that object.
  set beresp.grace = 120s;

  # Cache anything else. Returning nothing here would fall-through
  # to Varnish's default cache store policies.
  return(deliver);
}

# Deliver the response to the client
sub vcl_deliver {
  # Redirect the request if the AH-Mobile-Redirect or AH-Tablet-Redirect header or X-AH-Desktop-Redirect
  # is set and the devices is a mobile, tablet or desktop.
  if (resp.http.X-AH-Mobile-Redirect || resp.http.X-AH-Tablet-Redirect || resp.http.X-AH-Desktop-Redirect && !resp.http.X-AH-Mobile-Redirect) {
    # We run devicedetect as it will add the X-UA-Device header which specifies if the device is pc, phone or tablet.
    call acquia_devicedetect;

    # Make sure remap header is added to req if needed
    if (resp.http.X-AH-Redirect-No-Remap) {
      set req.http.X-AH-Redirect-No-Remap = resp.http.X-AH-Redirect-No-Remap;
    }

    if ( resp.http.X-AH-Mobile-Redirect && req.http.X-UA-Device ~ "mobile" && req.http.X-pinned-device != "mobile" ) {
      if (resp.http.X-AH-Mobile-Redirect !~ "(?i)^https?://") {
        set resp.http.X-AH-Mobile-Redirect = "http://" + resp.http.X-AH-Mobile-Redirect;
      }
      set req.http.X-AH-Redirect = resp.http.X-AH-Mobile-Redirect;
      call ah_device_redirect_check;
    }
    else if ( resp.http.X-AH-Tablet-Redirect && req.http.X-UA-Device ~ "tablet" && req.http.X-pinned-device != "tablet" ) {
      if (resp.http.X-AH-Tablet-Redirect !~ "(?i)^https?://") {
        set resp.http.X-AH-Tablet-Redirect = "http://" + resp.http.X-AH-Tablet-Redirect;
      }
      set req.http.X-AH-Redirect = resp.http.X-AH-Tablet-Redirect;
      call ah_device_redirect_check;
    }
    else if ( resp.http.X-AH-Desktop-Redirect && req.http.X-UA-Device ~ "pc" && req.http.X-pinned-device != "desktop" ) {
      if (resp.http.X-AH-Desktop-Redirect !~ "(?i)^https?://") {
        set resp.http.X-AH-Desktop-Redirect = "http://" + resp.http.X-AH-Desktop-Redirect;
      }
      set req.http.X-AH-Redirect = resp.http.X-AH-Desktop-Redirect;
      call ah_device_redirect_check;
    }
  }

  # Unset the X-AH redirect headers if they exist here
  unset resp.http.X-AH-Mobile-Redirect;
  unset resp.http.X-AH-Tablet-Redirect;
  unset resp.http.X-AH-Desktop-Redirect;
  unset resp.http.X-AH-Redirect-No-Remap;

  # Add an X-Cache diagnostic header
  if (obj.hits > 0) {
    set resp.http.X-Cache = "HIT";
    set resp.http.X-Cache-Hits = obj.hits;
    # Don't echo cached Set-Cookie headers
    unset resp.http.Set-Cookie;
  } else {
    set resp.http.X-Cache = "MISS";
  }

  # Strip the age header for Akamai requests
  if (req.http.Via ~ "akamai") {
    set resp.http.X-Age = resp.http.Age;
    unset resp.http.Age;
  }

  # Remove the Set-Cookie header from static assets
  if (req.http.X-static-asset) {
    unset resp.http.Set-Cookie;
  }

  # Force Safari to always check the server as it doesn't respect Vary: cookie.
  # See https://bugs.webkit.org/show_bug.cgi?id=71509
  # Static assets may be cached however as we already forcefully remove the
  # cookies for them.
  if (req.http.user-agent ~ "Safari" && !req.http.user-agent ~ "Chrome" && !req.http.X-static-asset) {
    set resp.http.cache-control = "max-age: 0";
  }
  # ELB health checks respect HTTP keep-alives, but require the connection to
  # remain open for 60 seconds. Varnish's default keep-alive idle timeout is
  # 5 seconds, which also happens to be the minimum ELB health check interval.
  # The result is a race condition in which Varnish can close an ELB health
  # check connection just before a health check arrives, causing that check to
  # fail. Solve the problem by not allowing HTTP keep-alive for ELB checks.
  if (req.http.user-agent ~ "ELB-HealthChecker") {
    set resp.http.Connection = "close";
  }
  return(deliver);
}


# Backend down: Error page returned when all backend servers are down
sub vcl_error {

  # EXAMPLE: How to do an external redirect in Varnish
  # in case we need to redirect a site to another balancer.
  # if (req.http.host ~ "^(www.)?example.com$") {
  #  if (obj.status == 302) {
  #    set obj.http.Location = "http://examplecom.prod.acquia-sites.com" + req.url;
  #    deliver;
  #  }
  # }

  # mobile browsers redirect
  if (obj.status == 750) {
    set obj.http.Location = obj.response + req.url;
    set obj.status = 302;
    set obj.response = "Found";
    return(deliver);
  }

  # user defined device redirect
  if (obj.status == 751) {
    if (req.http.X-AH-Redirect-No-Remap) {
      set obj.http.Location = obj.response;
    }
    else {
      set obj.http.Location = obj.response + req.url;
    }
    set obj.status = 302;
    set obj.response = "Found";
    return(deliver);
  }

  # Default Varnish error (Nginx didn't reply)
  set obj.http.Content-Type = "text/html; charset=utf-8";

  synthetic {"<?xml version="1.0" encoding="utf-8"?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html>
    <head>
      <title>"} + obj.status + " " + obj.response + {"</title>
    </head>
    <body>
    <h1>This server is experiencing technical problems. Please
try again in a few moments. Thanks for your continued patience, and
we're sorry for any inconvenience this may cause.</h1>
    <p>Error "} + obj.status + " " + obj.response + {"</p>
    <p>"} + obj.response + {"</p>
      <p>XID: "} + req.xid + {"</p>
    </body>
   </html>
   "};
  return(deliver);
}

# Separate pass subroutine to shorten the lifetime of beresp.ttl
# This will reduce the amount of "Cache Hits for Pass" for objects
sub ah_pass {
  set beresp.ttl = 10s;
  return(hit_for_pass);
}

# Test if a device redirect is attempting to redirect to the same path as the
# request came from. This should stop the state machine restart and remove the
# redirect from the headers.
sub ah_device_redirect_check {
  if (req.http.X-AH-Redirect-No-Remap) {
    if (req.http.X-Forwarded-Proto) {
      if (req.http.X-AH-Redirect != req.http.X-Forwarded-Proto + "://" + req.http.host + req.url) {
        return(restart);
      }
    }
    else {
      if (req.http.X-AH-Redirect != "http://" + req.http.host + req.url) {
        return(restart);
      }
    }
  }
  else {
    if (req.http.X-Forwarded-Proto) {
      if (req.http.X-AH-Redirect != req.http.X-Forwarded-Proto + "://" + req.http.host) {
        return(restart);
      }
    }
    else {
      if (req.http.X-AH-Redirect != "http://" + req.http.host) {
        return(restart);
      }
    }
  }
  # Redirection fell through so we will remove the redirect header.
  unset req.http.X-AH-Redirect;
}
