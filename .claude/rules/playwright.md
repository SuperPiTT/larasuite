---
paths: "tests/Playwright/**/*.ts, playwright.config.ts"
description: Playwright E2E testing patterns with reusable flows
---
# Playwright E2E Testing

> **Playwright** - Docs: https://playwright.dev/docs/intro
> **Test API** - Docs: https://playwright.dev/docs/api/class-test

## Directory Structure

```
tests/Playwright/
├── fixtures/              # Custom test fixtures
│   ├── auth.fixture.ts    # Authentication fixture
│   └── tenant.fixture.ts  # Multi-tenancy fixture
├── pages/                 # Page Object Models
│   ├── BasePage.ts
│   ├── LoginPage.ts
│   ├── DashboardPage.ts
│   └── clients/
│       ├── ClientListPage.ts
│       └── ClientFormPage.ts
├── flows/                 # Reusable test flows
│   ├── auth.flow.ts
│   └── client.flow.ts
├── specs/                 # Test specifications
│   ├── auth/
│   │   └── login.spec.ts
│   └── clients/
│       └── crud.spec.ts
├── utils/                 # Test utilities
│   └── helpers.ts
├── .auth/                 # Stored auth state (gitignored)
└── reports/               # Test reports (gitignored)
```

## Page Object Model (POM)

### Base Page

```typescript
// tests/Playwright/pages/BasePage.ts
import { Page, Locator } from '@playwright/test';

export abstract class BasePage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    abstract readonly url: string;

    async goto(): Promise<void> {
        await this.page.goto(this.url);
        await this.page.waitForLoadState('networkidle');
    }

    async waitForPageReady(): Promise<void> {
        await this.page.waitForLoadState('domcontentloaded');
        // Wait for Livewire to be ready
        await this.page.waitForFunction(() =>
            typeof (window as any).Livewire !== 'undefined'
        );
    }

    // Filament-specific helpers
    async waitForFilamentNotification(): Promise<string> {
        const notification = this.page.locator('.fi-no-notification');
        await notification.waitFor({ state: 'visible' });
        return await notification.textContent() ?? '';
    }

    async closeFilamentNotification(): Promise<void> {
        await this.page.locator('.fi-no-notification button[x-on\\:click="close"]').click();
    }
}
```

### Login Page

```typescript
// tests/Playwright/pages/LoginPage.ts
import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

export class LoginPage extends BasePage {
    readonly url = '/central/login';

    // Locators
    readonly emailInput: Locator;
    readonly passwordInput: Locator;
    readonly submitButton: Locator;
    readonly errorMessage: Locator;

    constructor(page: Page) {
        super(page);
        this.emailInput = page.locator('input[type="email"]');
        this.passwordInput = page.locator('input[type="password"]');
        this.submitButton = page.locator('button[type="submit"]');
        this.errorMessage = page.locator('.fi-fo-field-wrp-error-message');
    }

    async login(email: string, password: string): Promise<void> {
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);
        await this.submitButton.click();
    }

    async expectError(message?: string): Promise<void> {
        await expect(this.errorMessage).toBeVisible();
        if (message) {
            await expect(this.errorMessage).toContainText(message);
        }
    }

    async expectLoginSuccess(): Promise<void> {
        await this.page.waitForURL(/\/central(?!\/login)/, { timeout: 15000 });
    }
}
```

### Tenant Login Page

```typescript
// tests/Playwright/pages/TenantLoginPage.ts
import { Page } from '@playwright/test';
import { LoginPage } from './LoginPage';

export class TenantLoginPage extends LoginPage {
    readonly tenantSlug: string;

    constructor(page: Page, tenantSlug: string) {
        super(page);
        this.tenantSlug = tenantSlug;
    }

    get url(): string {
        return `http://${this.tenantSlug}.larasuite.test/login`;
    }

    async expectLoginSuccess(): Promise<void> {
        await this.page.waitForURL(
            new RegExp(`${this.tenantSlug}.*(?!\\/login)`),
            { timeout: 15000 }
        );
    }
}
```

## Authentication Fixture

```typescript
// tests/Playwright/fixtures/auth.fixture.ts
import { test as base, Page } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';
import { TenantLoginPage } from '../pages/TenantLoginPage';
import path from 'path';

// User credentials type
type UserCredentials = {
    email: string;
    password: string;
};

// Default test users
export const TEST_USERS = {
    superadmin: {
        email: 'admin@larasuite.test',
        password: 'password',
    },
    tenantAdmin: {
        email: 'admin@tenant.test',
        password: 'password',
    },
    tenantUser: {
        email: 'user@tenant.test',
        password: 'password',
    },
} as const;

// Auth state paths
const AUTH_DIR = path.join(__dirname, '../.auth');

// Extended test with auth fixtures
export const test = base.extend<{
    authenticatedPage: Page;
    centralAdminPage: Page;
    tenantPage: Page;
}>({
    // Authenticated page for central panel
    authenticatedPage: async ({ browser }, use) => {
        const context = await browser.newContext({
            storageState: path.join(AUTH_DIR, 'central-admin.json'),
        });
        const page = await context.newPage();
        await use(page);
        await context.close();
    },

    // Central admin page (superadmin)
    centralAdminPage: async ({ browser }, use) => {
        const context = await browser.newContext({
            storageState: path.join(AUTH_DIR, 'central-admin.json'),
        });
        const page = await context.newPage();
        await use(page);
        await context.close();
    },

    // Tenant page with tenant admin
    tenantPage: async ({ browser }, use) => {
        const context = await browser.newContext({
            storageState: path.join(AUTH_DIR, 'tenant-admin.json'),
        });
        const page = await context.newPage();
        await use(page);
        await context.close();
    },
});

// Setup: Generate auth state files
// Run this before tests: npx playwright test --project=setup
export async function globalSetup() {
    const { chromium } = await import('@playwright/test');
    const browser = await chromium.launch();

    // Setup central admin auth
    const centralPage = await browser.newPage();
    const loginPage = new LoginPage(centralPage);
    await loginPage.goto();
    await loginPage.login(TEST_USERS.superadmin.email, TEST_USERS.superadmin.password);
    await loginPage.expectLoginSuccess();
    await centralPage.context().storageState({
        path: path.join(AUTH_DIR, 'central-admin.json')
    });
    await centralPage.close();

    // Setup tenant admin auth
    const tenantPage = await browser.newPage();
    const tenantLoginPage = new TenantLoginPage(tenantPage, 'demo');
    await tenantLoginPage.goto();
    await tenantLoginPage.login(TEST_USERS.tenantAdmin.email, TEST_USERS.tenantAdmin.password);
    await tenantLoginPage.expectLoginSuccess();
    await tenantPage.context().storageState({
        path: path.join(AUTH_DIR, 'tenant-admin.json')
    });
    await tenantPage.close();

    await browser.close();
}

export { expect } from '@playwright/test';
```

## Reusable Flows

```typescript
// tests/Playwright/flows/auth.flow.ts
import { Page } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';
import { TenantLoginPage } from '../pages/TenantLoginPage';
import { TEST_USERS } from '../fixtures/auth.fixture';

export class AuthFlow {
    constructor(private page: Page) {}

    async loginAsCentralAdmin(): Promise<void> {
        const loginPage = new LoginPage(this.page);
        await loginPage.goto();
        await loginPage.login(
            TEST_USERS.superadmin.email,
            TEST_USERS.superadmin.password
        );
        await loginPage.expectLoginSuccess();
    }

    async loginAsTenantAdmin(tenantSlug: string): Promise<void> {
        const loginPage = new TenantLoginPage(this.page, tenantSlug);
        await loginPage.goto();
        await loginPage.login(
            TEST_USERS.tenantAdmin.email,
            TEST_USERS.tenantAdmin.password
        );
        await loginPage.expectLoginSuccess();
    }

    async logout(): Promise<void> {
        // Click user menu and logout
        await this.page.locator('[data-testid="user-menu"]').click();
        await this.page.locator('text=Cerrar sesión').click();
        await this.page.waitForURL(/\/login/);
    }
}
```

```typescript
// tests/Playwright/flows/client.flow.ts
import { Page, expect } from '@playwright/test';

export class ClientFlow {
    constructor(private page: Page) {}

    async createClient(data: {
        name: string;
        email: string;
        phone?: string;
        nif?: string;
    }): Promise<string> {
        await this.page.goto('/clients/create');

        await this.page.fill('[name="name"]', data.name);
        await this.page.fill('[name="email"]', data.email);

        if (data.phone) {
            await this.page.fill('[name="phone"]', data.phone);
        }
        if (data.nif) {
            await this.page.fill('[name="nif"]', data.nif);
        }

        await this.page.click('button[type="submit"]');

        // Wait for redirect to client view
        await this.page.waitForURL(/\/clients\/\d+/);

        // Return the client ID from URL
        const url = this.page.url();
        const match = url.match(/\/clients\/(\d+)/);
        return match ? match[1] : '';
    }

    async deleteClient(clientId: string): Promise<void> {
        await this.page.goto(`/clients/${clientId}`);
        await this.page.click('[data-testid="delete-button"]');
        await this.page.click('text=Confirmar');
        await this.page.waitForURL('/clients');
    }

    async searchClient(query: string): Promise<void> {
        await this.page.goto('/clients');
        await this.page.fill('[data-testid="search-input"]', query);
        await this.page.waitForLoadState('networkidle');
    }
}
```

## Composing Test Flows

```typescript
// tests/Playwright/specs/clients/crud.spec.ts
import { test, expect } from '../../fixtures/auth.fixture';
import { ClientFlow } from '../../flows/client.flow';
import { AuthFlow } from '../../flows/auth.flow';

test.describe('Client CRUD Operations', () => {
    test('should create, view, and delete a client', async ({ tenantPage }) => {
        const clientFlow = new ClientFlow(tenantPage);

        // Create client
        const clientId = await clientFlow.createClient({
            name: 'Test Client',
            email: 'test@example.com',
            nif: 'B12345678',
        });

        expect(clientId).toBeTruthy();

        // Verify client exists
        await clientFlow.searchClient('Test Client');
        await expect(tenantPage.locator('text=Test Client')).toBeVisible();

        // Cleanup
        await clientFlow.deleteClient(clientId);
    });

    test('should validate required fields', async ({ tenantPage }) => {
        await tenantPage.goto('/clients/create');
        await tenantPage.click('button[type="submit"]');

        await expect(tenantPage.locator('.fi-fo-field-wrp-error-message')).toHaveCount(2);
    });
});
```

## Complex Flow Example

```typescript
// tests/Playwright/specs/invoices/complete-flow.spec.ts
import { test, expect } from '../../fixtures/auth.fixture';
import { AuthFlow } from '../../flows/auth.flow';
import { ClientFlow } from '../../flows/client.flow';
import { InvoiceFlow } from '../../flows/invoice.flow';

test.describe('Complete Invoice Flow', () => {
    let clientId: string;

    test.beforeAll(async ({ browser }) => {
        // Setup: Create a client for invoice tests
        const page = await browser.newPage();
        const authFlow = new AuthFlow(page);
        const clientFlow = new ClientFlow(page);

        await authFlow.loginAsTenantAdmin('demo');
        clientId = await clientFlow.createClient({
            name: 'Invoice Test Client',
            email: 'invoice-test@example.com',
        });

        await page.close();
    });

    test.afterAll(async ({ browser }) => {
        // Cleanup: Delete the test client
        const page = await browser.newPage();
        const authFlow = new AuthFlow(page);
        const clientFlow = new ClientFlow(page);

        await authFlow.loginAsTenantAdmin('demo');
        await clientFlow.deleteClient(clientId);

        await page.close();
    });

    test('should create invoice for client', async ({ tenantPage }) => {
        const invoiceFlow = new InvoiceFlow(tenantPage);

        const invoiceId = await invoiceFlow.createInvoice({
            clientId,
            items: [
                { description: 'Service 1', quantity: 2, price: 100 },
            ],
        });

        expect(invoiceId).toBeTruthy();
    });

    test('should mark invoice as paid', async ({ tenantPage }) => {
        const invoiceFlow = new InvoiceFlow(tenantPage);

        // ... test implementation
    });
});
```

## Multi-tenancy Testing

```typescript
// tests/Playwright/fixtures/tenant.fixture.ts
import { test as base } from '@playwright/test';

type TenantFixtures = {
    tenantSlug: string;
    tenantBaseUrl: string;
};

export const test = base.extend<TenantFixtures>({
    tenantSlug: ['demo', { option: true }],

    tenantBaseUrl: async ({ tenantSlug }, use) => {
        await use(`http://${tenantSlug}.larasuite.test`);
    },
});

// Usage in tests
test('should show tenant dashboard', async ({ page, tenantBaseUrl }) => {
    await page.goto(`${tenantBaseUrl}/dashboard`);
    // ...
});
```

## DRY: Composable Test Architecture

### The Problem (Anti-pattern)

```typescript
// BAD: Login repeated in every test file
test('test A', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@test.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
    // ... actual test
});

test('test B', async ({ page }) => {
    // Same login code copy-pasted...
});
```

### The Solution (DRY)

```typescript
// GOOD: Reusable fixtures + flows
import { test } from '../fixtures/auth.fixture';
import { ClientFlow } from '../flows/client.flow';

// Auth is handled by fixture - no repeated login code
test('test A', async ({ authenticatedPage }) => {
    const clientFlow = new ClientFlow(authenticatedPage);
    await clientFlow.createClient({ name: 'Test' });
});

test('test B', async ({ authenticatedPage }) => {
    // Same fixture, different test - no duplication
});
```

### Flow Composition Pattern

```typescript
// Compose multiple flows for complex scenarios
test('complete business flow', async ({ tenantPage }) => {
    const auth = new AuthFlow(tenantPage);
    const client = new ClientFlow(tenantPage);
    const invoice = new InvoiceFlow(tenantPage);
    const payment = new PaymentFlow(tenantPage);

    // Build complex flows from simple, reusable pieces
    const clientId = await client.createClient(testData.client);
    const invoiceId = await invoice.createForClient(clientId, testData.items);
    await payment.markAsPaid(invoiceId);

    // Verify final state
    await invoice.expectStatus(invoiceId, 'paid');
});
```

### Reusability Checklist

- [ ] Login logic in `AuthFlow` + fixture, not in tests
- [ ] Page selectors in `Page Objects`, not hardcoded
- [ ] Common assertions in Page Object methods
- [ ] Test data in fixtures or factories
- [ ] Cleanup logic in `afterAll` hooks, shared

## Best Practices

### DO
- Use Page Object Model for all pages
- Create reusable flows for common operations
- Use fixtures for authentication state
- Store auth state to avoid repeated logins
- Use `data-testid` attributes for reliable selectors
- Clean up test data in `afterAll` hooks
- Group related tests with `test.describe`

### DON'T
- Don't hardcode URLs - use `baseURL` or page objects
- Don't use `waitForTimeout` - use proper waitFor methods
- Don't repeat login in every test - use fixtures
- Don't leave test data in database
- Don't use brittle CSS selectors

### Selectors Priority

```typescript
// 1. data-testid (best - stable)
page.locator('[data-testid="submit-button"]')

// 2. Role-based (accessible)
page.getByRole('button', { name: 'Submit' })

// 3. Text content (readable)
page.getByText('Submit')

// 4. CSS selectors (last resort)
page.locator('button.submit-btn')
```

### Debugging

```typescript
// Enable debug mode
await page.pause(); // Opens inspector

// Take screenshots
await page.screenshot({ path: 'debug.png' });

// Log page content
console.log(await page.content());

// Trace for CI failures
test.use({ trace: 'on' });
```

## Configuration Reference

```typescript
// playwright.config.ts
export default defineConfig({
    testDir: './tests/Playwright/specs',

    // Global setup for auth
    globalSetup: './tests/Playwright/fixtures/global-setup.ts',

    // Projects with dependencies
    projects: [
        {
            name: 'setup',
            testMatch: /global-setup\.ts/,
        },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
});
```
