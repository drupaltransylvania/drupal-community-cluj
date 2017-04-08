hash_data(req.url);
if (req.http.host) {
   hash_data(req.http.host);
} else {
   hash_data(server.ip);
}

# Let the default hash to return the hash data.
return (hash);
