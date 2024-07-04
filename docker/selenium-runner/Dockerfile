FROM node:21-alpine

RUN npm install -g selenium-side-runner

WORKDIR /selenium-sides

CMD selenium-side-runner -s http://selenium-chrome:4444 01-* \
    && selenium-side-runner -s http://selenium-chrome:4444 02-* \
    && selenium-side-runner -s http://selenium-chrome:4444 03-* \
    && selenium-side-runner -s http://selenium-chrome:4444 04-*    
    