# Phát triển SDK

```bash
composer install
composer check                 # PHPStan + PHPUnit
php bin/check-spec.php         # so resources/openapi.json với spec trên card2k.net
php bin/check-spec.php --update
php bin/generate-models.php    # sinh lại src/Model từ spec
```

- `resources/openapi.json` là bản chụp spec API v3 (bản gốc: https://card2k.net/tai-lieu-api/openapi). CI so bản chụp với card2k.net mỗi ngày.
- Commit theo [Conventional Commits](https://www.conventionalcommits.org/): `feat: …`, `fix: …`, phát hành `chore(release): vX.Y.Z`.
- Phiên bản theo [SemVer](https://semver.org/lang/vi/), ghi ở `Client::VERSION` và `CHANGELOG.md`.
