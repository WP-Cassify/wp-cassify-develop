#!/usr/bin/env bash
set -euo pipefail

repo=$(git -C "$(dirname "$0")" rev-parse --show-toplevel)
fixture=$(mktemp -d)
project="cassify-upgrade-${fixture##*.}"
project=${project,,}
compose=(docker compose -p "$project" -f "$fixture/docker-compose.yml" -f "$fixture/upgrade.yml")

cleanup() {
  "${compose[@]}" down --volumes >/dev/null 2>&1 || true
}
trap cleanup EXIT

tar -C "$repo" --exclude=docker/playwright-tests/playwright-report --exclude=docker/playwright-tests/test-results -cf - docker docker-compose.yml | tar -x -C "$fixture"
git -C "$repo" archive 2.5.0 wp-cassify | tar -x -C "$fixture"
cp -a "$repo/wp-cassify" "$fixture/upgrade-candidate"
cat > "$fixture/upgrade.yml" <<'YAML'
services:
  wordpress:
    ports: !reset []
  cas:
    ports: !reset []
  playwright-suite:
    environment:
      PLAYWRIGHT_UPGRADE_PLUGIN: /work/upgrade-plugin
      PLAYWRIGHT_UPGRADE_CANDIDATE: /work/upgrade-candidate
    volumes:
      - ./wp-cassify:/work/upgrade-plugin
      - ./upgrade-candidate:/work/upgrade-candidate:ro
      - ./docker/playwright-tests/upgrade.spec.js:/work/upgrade.spec.js:ro
    command: ["sh", "-lc", "xvfb-run -a npx playwright test --headed upgrade.spec.js"]
YAML

echo "Upgrade test report: $fixture/docker/playwright-tests/playwright-report/index.html"
"${compose[@]}" up --build --attach playwright-suite --exit-code-from playwright-suite
