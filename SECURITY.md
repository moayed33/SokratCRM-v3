# Security Policy

## Supported Versions

The following versions of SokratCRM are currently supported with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 3.x     | :white_check_mark: |
| 2.x     | :x:                |
| 1.x     | :x:                |

---

## Reporting a Vulnerability

We take the security and privacy of SokratCRM and the charitable organizations using it very seriously.

If you discover a security vulnerability in SokratCRM, please follow responsible disclosure practices:

1. **Do NOT disclose the issue publicly** in GitHub Issues, Discussions, or social media.
2. **Email the security contact:**
   - Send details to: `security@sokratcrm.org` (or contact the repository maintainers via GitHub private security advisories).
3. **Include the following details:**
   - A clear description of the vulnerability (e.g., IDOR, privilege escalation, injection, unauthorized data access).
   - Step-by-step reproduction steps or a minimal Proof of Concept (PoC).
   - Affected versions, endpoints, or components.
   - Any potential mitigations you have identified.

---

## Security Architecture Highlights

SokratCRM incorporates defensive measures by design:

- **Granular Permissions & RBAC:** Fine-grained authorization through the `CrmPermission` enum and Laravel Policies across all controllers and UI components.
- **Strict Multi-Branch Isolation:** Branch-scoped query constraints to prevent cross-branch data exposure.
- **IDOR Protection:** Access controls on leads, collections, campaigns, and user management.
- **User-Scoped Notifications:** Strict recipient resolution preventing notification leakage across roles and branches.
- **Automated Security Verification:** Comprehensive automated test suite covering authorization barriers, tampering attempts, and multi-tenant boundary checks.
