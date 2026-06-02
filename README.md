# ownCloud Antivirus App

<!-- OSPO-managed README | Generated: 2026-04-16 | v2 -->

[![License](https://img.shields.io/badge/License-AGPL--3.0-blue.svg)](COPYING) [![ownCloud OSPO](https://img.shields.io/badge/OSPO-ownCloud-blue)](https://kiteworks.com/opensource) [![Docker Hub](https://img.shields.io/docker/pulls/owncloud)](https://hub.docker.com/r/owncloud/server)

An ownCloud Classic (OC10) app that integrates [ClamAV](http://www.clamav.net) antivirus scanning into the file upload pipeline. Files are scanned at upload time; infected files are automatically deleted and users are notified on screen and via email. The app supports ClamAV in executable mode, network daemon mode, and local socket mode, and includes a background job for scanning existing files.

## Getting Started

Requirements: ClamAV (binaries or a ClamAV daemon).

Enable the app and configure ClamAV mode in the ownCloud admin settings:

```bash
sudo -u www-data php occ app:enable files_antivirus
```

Configure the connection mode (executable, socket, or network) in the admin panel under the Antivirus section.

## Documentation

- [ownCloud Antivirus Documentation](https://doc.owncloud.com/server/latest/admin_manual/configuration/server/virus-scanner-support.html)
- [ClamAV Documentation](https://docs.clamav.net/)

## Features

Capabilities of the antivirus integration:

### Scanning Modes

- **Executable mode** -- uses the ClamAV binary directly
- **Daemon mode (network socket)** -- connects to a remote/local ClamAV daemon via network
- **Daemon mode (local socket)** -- connects via local file socket
- **Background job** -- scans all existing files on a schedule

### Enterprise: ICAP Integration

The Enterprise Edition supports the [ICAP protocol](https://tools.ietf.org/html/rfc3507) for integration with commercial antivirus solutions:

| Vendor | Request Service | Response Header |
|---|---|---|
| ClamAV (c-icap) | `avscan` | `X-Infection-Found` |
| Kaspersky ScanEngine | `req` | `X-Virus-ID` |
| FortiSandbox | `respmod` | `X-Virus-Name` |
| McAfee/Skyhigh Web Gateway 10.x+ | `respmod` | `X-Virus-Name` |

ICAP mode requires a valid enterprise license. Without a license key, it triggers a grace period; after expiration the app is disabled.

### Current Capabilities

- Files are checked at upload time
- Infected files are automatically deleted with on-screen and email notification
- Configurable file size limit
- Background job for full-filesystem scanning
- Tested on Linux

## Part of ownCloud Classic (OC10)

This app extends [ownCloud Server](https://github.com/owncloud/core) with antivirus scanning capabilities. It is shipped as part of the [ownCloud Server Docker image](https://hub.docker.com/r/owncloud/server).

## Community & Support

**[Star](https://github.com/owncloud/files_antivirus)** this repo and **Watch** for release notifications!

- [ownCloud Website](https://owncloud.com)
- [Community Discussions](https://github.com/orgs/owncloud/discussions)
- [Matrix Chat](https://app.element.io/#/room/#owncloud:matrix.org)
- [Documentation](https://doc.owncloud.com)
- [Enterprise Support](https://owncloud.com/contact-us/)
- [OSPO Home](https://kiteworks.com/opensource)

## Contributing

We welcome contributions! Please read the [Contributing Guidelines](CONTRIBUTING.md)
and our [Code of Conduct](CODE_OF_CONDUCT.md) before getting started.

### Workflow

- **Rebase Early, Rebase Often!** We use a rebase workflow. Always rebase on the target branch before submitting a PR.
- **Dependabot**: Automated dependency updates are managed via Dependabot. Review and merge dependency PRs promptly.
- **Signed Commits**: All commits **must** be PGP/GPG signed. See [GitHub's signing guide](https://docs.github.com/en/authentication/managing-commit-signature-verification).
- **DCO Sign-off**: Every commit must carry a `Signed-off-by` line:
  ```
  git commit -s -S -m "your commit message"
  ```
- **GitHub Actions Policy**: Workflows may only use actions that are (a) owned by `owncloud`, (b) created by GitHub (`actions/*`), or (c) verified in the GitHub Marketplace.

## Translations

Help translate this project on Transifex:
**<https://explore.transifex.com/owncloud-org/owncloud/>**

Please submit translations via Transifex -- do not open pull requests for translation changes.

## Security

**Do not open a public GitHub issue for security vulnerabilities.**

Report vulnerabilities at **<https://security.owncloud.com>** -- see [SECURITY.md](SECURITY.md).

Bug bounty: [YesWeHack ownCloud Program](https://yeswehack.com/programs/owncloud-bug-bounty-program)

## License

This project is licensed under the [AGPL-3.0](COPYING).

## About the ownCloud OSPO

The [Kiteworks Open Source Program Office](https://kiteworks.com/opensource), operating under
the [ownCloud](https://owncloud.com) brand, launched on May 5, 2026, to steward the open source
ecosystem around ownCloud's products. The OSPO ensures transparent governance, license compliance,
community health, and sustainable collaboration between the open source community and
[Kiteworks](https://www.kiteworks.com), which acquired ownCloud in 2023.

- **OSPO Home**: <https://kiteworks.com/opensource>
- **GitHub**: <https://github.com/owncloud>
- **ownCloud**: <https://owncloud.com>

For questions about the OSPO or licensing, contact ospo@kiteworks.com.

### License Migration to Apache 2.0

The OSPO is driving a strategic relicensing of ownCloud repositories toward the
[Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0), following
the [Apache Software Foundation's third-party license policy](https://www.apache.org/legal/resolved.html).

Individual repositories will migrate as their audit is completed. The LICENSE file
in each repo reflects its **current** license status (not the target).

**Current license: AGPL-3.0** (Category X per Apache policy -- cannot be included in Apache-2.0 works).

Migration prerequisites for this repository:

- **CLA/DCO coverage**: All past contributors must have signed agreements permitting relicensing
- **Copyleft dependency audit**: All AGPL/GPL dependencies must be replaced or isolated
- **KDE heritage review**: Any code with KDE-era copyrights requires legal analysis
- **Complete relicensing**: AGPL-3.0 is a strong copyleft license; migration requires full relicensing of all files, not just a header change
