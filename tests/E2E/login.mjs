// One place for the Joomla administrator login, so that a change of the form touches one file.
// The page must already show the login form (page.goto(ADMIN)).
export async function submitLogin(page, username, password) {
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Log in' }).click()]);
}

export const loginAsAdmin = (page) => submitLogin(page, process.env.BFNG_E2E_USER, process.env.BFNG_E2E_PASSWORD);
