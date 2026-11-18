const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { CAS_BASE_URL } = require('./support');

module.exports = function registerGatewayTests() {
  test.describe('Gateway whitelist with a longer WordPress site URL', () => {
    const mu = path.join(process.env.PLAYWRIGHT_WORDPRESS_VOLUME_PATH || '/work/wordpress', 'wp-content/mu-plugins');
    const token = crypto.randomBytes(24).toString('hex');
    test.beforeAll(() => {
      fs.mkdirSync(mu, { recursive: true });
      fs.writeFileSync(path.join(mu, 'cassify-gateway.token'), token);
      fs.copyFileSync(path.join(__dirname, 'gateway-fixtures.php'), path.join(mu, 'cassify-gateway.php'));
    });
    test.afterAll(() => {
      for (const file of ['cassify-gateway.php', 'cassify-gateway.token']) {
        fs.rmSync(path.join(mu, file), { force: true });
      }
    });

    for (const scenario of [
      { name: 'home shorter than offset redirects on every fresh session', url: '/', whitelist: '/public/', redirect: true },
      { name: 'URL equal to offset remains a non-match', url: '/wp', whitelist: '/wp', redirect: true },
      { name: 'path fragment after site URL bypasses CAS', url: '/wp/public/', whitelist: '/missing/;/public/', redirect: false },
      { name: 'full URL entry does not match', url: '/wp/public/', whitelist: 'http://wordpress.example.org/wp/public/', redirect: true },
    ]) {
      test(scenario.name, async ({ playwright, baseURL }) => {
        // Independent cookie jars reproduce first visits, including crawlers.
        for (let visit = 0; visit < 2; visit++) {
          const request = await playwright.request.newContext({
            baseURL,
            extraHTTPHeaders: { 'X-Cassify-Gateway-Token': token, 'X-Cassify-White-List': scenario.whitelist },
          });
          try {
            const response = await request.get(scenario.url, { maxRedirects: 0 });
            if (scenario.redirect) {
              expect(response.status()).toBe(302);
              const location = new URL(response.headers().location);
              expect(location.origin + location.pathname).toBe(`${CAS_BASE_URL}/login`);
              expect(location.searchParams.get('gateway')).toBe('true');
              expect(location.searchParams.get('service')).toBe(new URL(scenario.url, baseURL).href);
            } else {
              // The synthetic path may be a WordPress 404; it must not redirect to CAS.
              expect([200, 404]).toContain(response.status());
              expect(await response.text()).not.toMatch(/Fatal error|critical error/i);
            }
          } finally {
            await request.dispose();
          }
        }
      });
    }
  });
};
