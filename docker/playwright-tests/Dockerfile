FROM mcr.microsoft.com/playwright:v1.60.0-jammy

WORKDIR /work

COPY package.json playwright.config.js support.js cleanup-tests.js cleanup-fixtures.php wp-cassify.spec.js wp-cassify-multisite.spec.js membership.spec.js membership-fixtures.php ./

RUN npm install

CMD ["npm", "run", "test"]


