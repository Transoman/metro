FROM alpine:latest

RUN apk update && apk add --no-cache ca-certificates nss

WORKDIR /certs

RUN wget -q -O mkcert https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-arm64
RUN chmod +x mkcert
RUN mv mkcert /usr/local/bin/

# Generating a root CA (if not already created)
RUN mkcert -install

# Generate certificates for local domains
CMD ["sh", "-c", "mkcert -cert-file /certs/localhost.pem -key-file /certs/localhost-key.pem localhost 127.0.0.1 ::1"]