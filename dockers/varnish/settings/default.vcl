backend default {
  .host = "php5";
  .port = "80";
}

# Respond to incoming requests.
sub vcl_recv {
  include "vcl_recv.vcl";
}

# Set a header to track a cache HIT/MISS.
sub vcl_deliver {
  include "vcl_deliver.vcl";
}

# Code determining what to do when serving items from the Apache servers.
# beresp == Back-end response from the web server.
sub vcl_fetch {
  include "vcl_fetch.vcl";
}

### vcl_hash creates the key for varnish under which the object is stored. It is
### possible to store the same url under 2 different keys, by making vcl_hash
### create a different hash.
sub vcl_hash {
  include "vcl_hash.vcl";
}

# In the event of an error, show friendlier messages.
sub vcl_error {
  include "vcl_error.vcl";
}