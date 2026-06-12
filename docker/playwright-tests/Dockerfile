FROM mcr.microsoft.com/playwright:v1.60.0-jammy

WORKDIR /work

COPY package.json playwright.config.js support.js wp-cassify.spec.js wp-cassify-multisite.spec.js ./

RUN npm install

CMD ["npm", "run", "test"]


