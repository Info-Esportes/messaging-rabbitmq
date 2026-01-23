# Publishing to Packagist - Complete Guide

## ✅ What's Already Configured

Your package is **ready to publish**! Here's what's already in place:

### 1. ✅ composer.json
- Package name: `infoesportes/messaging-rabbitmq`
- Complete metadata (description, keywords, license)
- Author information
- GitHub repository links
- PSR-4 autoloading
- All dependencies specified
- Quality tool scripts configured

### 2. ✅ LICENSE
- MIT License file created
- Matches the license specified in composer.json

### 3. ✅ README.md
- Comprehensive documentation
- Installation instructions
- Usage examples (Publisher & Consumer)
- Configuration tables
- Framework integration examples
- Badges ready for Packagist

### 4. ✅ Code Quality
- PHPStan configuration
- PHP-CS-Fixer configuration
- PHPUnit configuration
- Full test coverage

### 5. ✅ Git Repository
- Repository initialized
- Remote configured: https://github.com/Info-Esportes/messaging-rabbitmq
- Branch: main

## 📋 Step-by-Step Publishing Guide

### Step 1: Commit and Push Your Code

```bash
cd "/home/zluckx/Projects/Info Esportes/messaging-rabbitmq"

# Add all files
git add .

# Commit
git commit -m "feat: add consumer implementation and complete package"

# Push to GitHub
git push origin main
```

### Step 2: Create a Git Tag (Version)

Packagist uses Git tags for versions. Create your first release:

```bash
# Create an annotated tag for version 1.0.0
git tag -a v1.0.0 -m "Initial release with Publisher and Consumer support"

# Push the tag to GitHub
git push origin v1.0.0
```

**Version Numbering (Semantic Versioning):**
- `v1.0.0` - Major.Minor.Patch
- Major: Breaking changes
- Minor: New features (backward compatible)
- Patch: Bug fixes

### Step 3: Register on Packagist

1. **Go to Packagist**: https://packagist.org
2. **Sign up/Login** using your GitHub account
3. **Click "Submit"** in the top navigation
4. **Enter your repository URL**: `https://github.com/Info-Esportes/messaging-rabbitmq`
5. **Click "Check"** - Packagist will validate your package
6. **Click "Submit"** - Your package is now published!

### Step 4: Set Up Auto-Update Hook (Recommended)

To automatically update Packagist when you push to GitHub:

**Option A: Using GitHub Actions (Recommended)**

Create `.github/workflows/packagist-update.yml`:

```yaml
name: Update Packagist

on:
  push:
    tags:
      - 'v*'

jobs:
  update-packagist:
    runs-on: ubuntu-latest
    steps:
      - name: Update Packagist
        run: |
          curl -XPOST -H'content-type:application/json' \
            'https://packagist.org/api/update-package?username=${{ secrets.PACKAGIST_USERNAME }}&apiToken=${{ secrets.PACKAGIST_TOKEN }}' \
            -d'{"repository":{"url":"https://github.com/Info-Esportes/messaging-rabbitmq"}}'
```

Then add secrets in GitHub:
- Go to repository Settings > Secrets and variables > Actions
- Add `PACKAGIST_USERNAME` (your Packagist username)
- Add `PACKAGIST_TOKEN` (from Packagist profile > API Token)

**Option B: Manual Webhook**

1. On Packagist, go to your package page
2. Click "Settings" tab
3. Copy the webhook URL
4. On GitHub: Settings > Webhooks > Add webhook
5. Paste the URL and select "Just the push event"

## 🚀 Publishing New Versions

When you want to release a new version:

```bash
# Make your changes
git add .
git commit -m "feat: add new feature"
git push origin main

# Create a new tag
git tag -a v1.1.0 -m "Add new feature description"
git push origin v1.1.0
```

Packagist will automatically detect the new tag and create a new release!

## 📦 Verification Checklist

Before publishing, verify:

- [ ] Code committed and pushed to GitHub
- [ ] Version tag created (e.g., v1.0.0)
- [ ] Tag pushed to GitHub
- [ ] composer.json is valid (run: `composer validate`)
- [ ] README.md has installation instructions
- [ ] LICENSE file exists
- [ ] Tests pass (run: `composer test`)
- [ ] Code style is correct (run: `composer cs-check`)

## 🔍 After Publishing

1. **Verify on Packagist**: Visit https://packagist.org/packages/infoesportes/messaging-rabbitmq
2. **Test Installation**: In a new project, run:
   ```bash
   composer require infoesportes/messaging-rabbitmq
   ```
3. **Update Badges**: The badges in your README will automatically work once published

## 📊 Package Statistics

After publishing, your package page will show:
- Download statistics
- Version history
- Dependents
- GitHub stars

## 🎯 Marketing Your Package

1. **GitHub Topics**: Add relevant topics to your repository:
   - rabbitmq
   - messaging
   - php
   - microservices
   - amqp

2. **Announce**: Share on:
   - Reddit: r/PHP
   - Twitter/X with #PHP #RabbitMQ
   - Dev.to
   - Your blog

3. **Add to Lists**: Submit to awesome-php lists

## 🔄 Maintenance

### Updating Your Package

```bash
# Make changes
git add .
git commit -m "fix: bug fix description"
git push origin main

# Create patch version
git tag -a v1.0.1 -m "Bug fix description"
git push origin v1.0.1
```

### Version Strategy

- **Patch** (v1.0.1): Bug fixes, documentation
- **Minor** (v1.1.0): New features, backward compatible
- **Major** (v2.0.0): Breaking changes

## 🆘 Troubleshooting

**Issue**: Packagist says "Could not find package"
- **Solution**: Ensure composer.json is in the repository root
- Run `composer validate` locally

**Issue**: Version not showing up
- **Solution**: Tags must start with `v` (e.g., v1.0.0)
- Push tags: `git push --tags`

**Issue**: Auto-update not working
- **Solution**: Check webhook configuration
- Verify GitHub Actions secrets are set

## 📞 Support

- **Packagist Issues**: https://github.com/composer/packagist/issues
- **Composer Docs**: https://getcomposer.org/doc/

---

## Quick Commands Reference

```bash
# Validate composer.json
composer validate

# Create and push a version tag
git tag -a v1.0.0 -m "Initial release"
git push origin v1.0.0

# Update package on Packagist manually
curl -XPOST -H'content-type:application/json' \
  'https://packagist.org/api/update-package?username=USERNAME&apiToken=TOKEN' \
  -d'{"repository":{"url":"https://github.com/Info-Esportes/messaging-rabbitmq"}}'
```

Good luck with your package! 🎉
