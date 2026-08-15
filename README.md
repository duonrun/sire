# Celema Sire

<!-- prettier-ignore-start -->
[![ci](https://codefloe.com/celema/sire/badges/workflows/ci.yml/badge.svg?style=flat&logo=forgejo&logoColor=white&label=ci)](https://codefloe.com/celema/sire/actions)
[![code coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fcov.celema.dev%2Fcelema%2Fsire%2Fcode%2Fbadge.json)](https://cov.celema.dev/celema/sire/code)
[![type coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fcov.celema.dev%2Fcelema%2Fsire%2Ftypes%2Fbadge-cover.json)](https://cov.celema.dev/celema/sire/types)
[![psalm level](https://img.shields.io/endpoint?url=https%3A%2F%2Fcov.celema.dev%2Fcelema%2Fsire%2Ftypes%2Fbadge-level.json)](https://cov.celema.dev/celema/sire/types)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
<!-- prettier-ignore-end -->

A PHP validation library with a shape-first API and a compact rule DSL.

> **Note:** This is a preview feature currently under active development.

Sire defines required fields by default. Use field-level `optional()`, `default()`, `empty()`, and `nullable()` when a field can be absent, filled, empty, or explicitly `null`. Use shape-level `prepare()` for payload migrations, field-level `prepare()` for value normalization, and `finalize()` for final field output transforms.

## Installation

```bash
composer require celema/sire
```

## Documentation

Use the docs in `docs/` for complete setup and usage guidance.

- [Introduction](docs/index.md)
- [Installation](docs/installation.md)
- [Usage](docs/usage.md)
- [Development](docs/development.md)

## License

This project is licensed under the [MIT license](LICENSE.md).
