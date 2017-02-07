FROM ubuntu:12.04
MAINTAINER Daniel Popdan <popdan.daniel@gmail.com>

# Specifies it is not used interactively.
ENV DEBIAN_FRONTEND noninteractive

# Installs base packages.
RUN apt-get update && \
    apt-get -yq install \
    nginx \
    curl

# Creates the GPG key and registers it.
RUN curl http://repo.varnish-cache.org/debian/GPG-key.txt |apt-key add - \
	  && echo "deb http://repo.varnish-cache.org/ubuntu/ lucid varnish-3.0" | tee -a /etc/apt/sources.list

# Instals varnish.
RUN apt-get update && \
    apt-get -yq install \
    varnish

# Sets listening on port 6081.
ENV LISTEN_PORT 6081

# Exposes the 6081 port.
EXPOSE 6081

# Starts varnish.
CMD ["varnishd","-a","0.0.0.0:6081","-T","0.0.0.0:6082","-f","/etc/varnish/default.vcl","-s","file,/var/cache/varnish.cache,256m","-F"]