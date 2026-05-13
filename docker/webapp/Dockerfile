FROM node:20-alpine

# bash makes the compose `command:` more portable; openssl + git occasionally needed by node-gyp deps
RUN apk add --no-cache bash git openssl

RUN npm install -g @angular/cli@20

WORKDIR /workspace/webapp

EXPOSE 4200
