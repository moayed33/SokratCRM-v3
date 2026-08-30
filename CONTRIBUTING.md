# Contributing to SokratCRM

Thank you for your interest in contributing to SokratCRM! We welcome contributions to help improve this CRM for charitable organizations and donor management.

---

## Code of Conduct

Please treat all contributors, maintainers, and community members with respect and professionalism.

---

## Getting Started

1. **Fork and Clone:**
   ```bash
   git clone https://github.com/your-username/SokratCRM-v3.git
   cd SokratCRM-v3
   ```

2. **Set Up Local Environment:**
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure Database & Migrate:**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   npm run build
   ```

4. **Run the Test Suite:**
   ```bash
   ./vendor/bin/phpunit
   ```
   Ensure all tests pass before making modifications.

---

## Development Guidelines

- **Branch Naming:** Use descriptive branch names (e.g., `feature/donor-tagging`, `fix/collection-reschedule-validation`).
- **Code Style:** Adhere to PSR-12 and standard Laravel conventions. Run Laravel Pint if applicable (`./vendor/bin/pint`).
- **Authorization & Security:** Ensure every new controller action, route, or UI element enforces appropriate `CrmPermission` checks and respects branch/user scoping.
- **Tests Required:** Any new feature, bug fix, or authorization rule must include corresponding PHPUnit feature tests.
- **Privacy First:** Never commit real donor details, phone numbers, credentials, or private organization tokens.

---

## Submitting Pull Requests

1. Commit your changes with clear, descriptive commit messages.
2. Push your branch to your fork.
3. Open a Pull Request against the `main` branch with a comprehensive description of the change, test coverage, and screenshots if UI changes were made.
