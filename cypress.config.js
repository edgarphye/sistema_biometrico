{
  "projectId": "sistema-biometrico",
  "baseUrl": "http://localhost:8000",
  "viewportWidth": 1280,
  "viewportHeight": 720,
  "video": true,
  "screenshotOnRunFailure": true,
  "videoCompression": 32,
  "defaultCommandTimeout": 10000,
  "requestTimeout": 10000,
  "responseTimeout": 10000,
  "retries": {
    "runMode": 2,
    "openMode": 0
  },
  "env": {
    "username": "admin@test.com",
    "password": "Admin123!",
    "apiUrl": "http://localhost:8000/api",
    "testUserEmail": "testuser@example.com",
    "testUserPassword": "Test123!"
  },
  "integrationFolder": "tests/E2E",
  "supportFile": "tests/E2E/support/e2e.js",
  "pluginsFile": "tests/E2E/plugins/index.js",
  "fixturesFolder": "tests/E2E/fixtures",
  "screenshotsFolder": "tests/E2E/screenshots",
  "videosFolder": "tests/E2E/videos",
  "downloadsFolder": "tests/E2E/downloads",
  "ignoreTestFiles": [
    "*.hot-update.js",
    "**/__snapshots__/*",
    "**/coverage/**"
  ],
  "testFiles": [
    "**/*.cy.js",
    "**/*.cy.ts"
  ],
  "chromeWebSecurity": false,
  "experimentalSessionSupport": true,
  "experimentalSourceRewriting": true,
  "component": {
    "componentFolder": "src/components"
  },
  "reporter": "cypress-multi-reporters",
  "reporterOptions": {
    "reporterEnabled": "mochawesome, mocha-junit-reporter",
    "mochawesomeReporterOptions": {
      "reportDir": "tests/E2E/results",
      "quiet": true,
      "overwrite": false,
      "html": false,
      "json": true
    },
    "mochaJunitReporterReporterOptions": {
      "mochaFile": "tests/E2E/results/junit-[hash].xml",
      "toConsole": false
    }
  },
  "taskTimeout": 60000,
  "fixtures": {
    "user": "tests/E2E/fixtures/user.json",
    "employees": "tests/E2E/fixtures/employees.json"
  },
  "port": 8080,
  "blockHosts": [
    "www.google-analytics.com",
    "stats.g.doubleclick.net",
    "www.googletagmanager.com"
  ],
  "headers": {
    "X-Custom-Header": "Test-Environment"
  }
}