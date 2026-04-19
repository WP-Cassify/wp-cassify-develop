FROM mcr.microsoft.com/playwright:v1.59.1-jammy

WORKDIR /work

COPY package.json playwright.config.js support.js wp-cassify.spec.js wp-cassify-multisite.spec.js ./

RUN npm install

CMD ["npm", "run", "test:e2e"]


