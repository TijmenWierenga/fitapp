---
name: fix-tests
description: >-
  Diagnoses and fixes failing tests with a root-cause-first approach. Activates when the user asks
  to fix failing tests, debug test failures, or make tests pass. Focuses on fixing application code
  rather than patching test fixtures.
---

# Fix Failing Tests

## When to Apply

Activate this skill when:

- The user asks to fix one or more failing tests
- The user shares test output with failures or errors
- The user asks to make the test suite green

## Workflow

Follow these steps **in order**. Do not skip the diagnosis step.

### Step 1: Run the Failing Tests

Run the failing test(s) to reproduce the failure and capture output:

```bash
php artisan test --compact --filter=TestName
```

Or run a specific file:

```bash
php artisan test --compact tests/Feature/ExampleTest.php
```

### Step 2: Diagnose the Root Cause

**Before making ANY code changes**, analyze the failure:

1. Read the failing test to understand what it asserts
2. Read the application code the test exercises (controller, action, model, etc.)
3. Determine where the bug lives:
   - **Application code** — the code under test doesn't handle a case correctly
   - **Test setup** — the test itself has incorrect expectations or outdated setup

Report your diagnosis to the user before proceeding.

### Step 3: Apply the Fix

Follow these rules when fixing:

- **Prefer fixing application code over test fixtures.** If the app code should handle optional/nullable data, make it do so — don't add data to the test to paper over the gap.
- **Only modify test code when the test itself is wrong** (e.g., outdated assertions after an intentional behavior change).
- **Make the minimal fix.** Don't refactor surrounding code, add docblocks, or extract classes as part of a test fix.

### Step 4: Verify the Fix

Run the specific failing test(s) to confirm they pass:

```bash
php artisan test --compact --filter=TestName
```

### Step 5: Run the Full Suite

Run the full test suite to ensure no regressions:

```bash
php artisan test --compact
```

If new failures appear, repeat from Step 2 for each one.

### Step 6: Format

Run Pint to fix any formatting issues:

```bash
vendor/bin/pint --dirty
```

## Rules

- **Never skip the diagnosis.** Always explain the root cause before writing a fix.
- **Application code is the primary fix target.** Test fixtures are the last resort.
- **Minimal changes only.** A test fix is not an invitation to refactor.
- **All tests must pass** before the task is considered complete.
