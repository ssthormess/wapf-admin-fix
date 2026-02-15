# WAPF Admin Fix

A WordPress plugin that restores administrative interface functionality for **Advanced Product Fields for WooCommerce** (WAPF). This plugin enables local development and testing by bypassing license validation while maintaining full admin capabilities.

## 📋 Overview

Advanced Product Fields for WooCommerce is a powerful plugin for creating custom product fields. However, the premium version's license validation can interfere with local development, staging environments, and legitimate testing scenarios. WAPF Admin Fix solves this by:

- ✅ Restoring full access to the admin interface
- ✅ Enabling Add, Edit, Duplicate, and Delete field operations
- ✅ Maintaining data integrity across saves
- ✅ Working seamlessly with the existing WAPF installation

## 🚀 Features

### Core Functionality
- **License Bypass**: Removes license validation checks for local/development use
- **Full Admin Access**: Complete access to all administrative features
- **Field Management**: Add, edit, duplicate, and delete fields without restrictions
- **Data Persistence**: Ensures all changes are properly saved
- **UI Restoration**: Unhides blocked interface elements

### Technical Highlights
- Lightweight and non-intrusive
- No database modifications required
- Compatible with WooCommerce product editing
- Works with TinyBind framework
- Automatic field ID generation matching WAPF's format

## 📦 Installation

### Method 1: WordPress Admin
1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual Installation
1. Download and extract the plugin
2. Upload the `wapf-admin-fix` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Method 3: Composer (Bedrock)
```bash
composer require wpackagist-plugin/wapf-admin-fix
```

## 🔧 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Advanced Product Fields for WooCommerce**: Must be installed
- **WooCommerce**: Required by WAPF

## 💻 Usage

Once activated, the plugin works automatically. No configuration needed!

### What Gets Fixed

1. **Add Field Button** - Creates new fields with proper ID generation
2. **Duplicate Button** - Clones fields with unique IDs and " (Copy)" suffix
3. **Delete Button** - Removes fields after confirmation
4. **License Blocks** - Hides all license-related UI restrictions
5. **Data Sync** - Ensures field data persists correctly on save

### How It Works

The plugin intercepts WordPress option filters to provide a valid license response, bypasses UI blocks via CSS, and implements JavaScript handlers to restore broken field management functionality caused by TinyBind framework detachment.

## ⚖️ Legal & Licensing

This plugin is released under the **GNU General Public License v3.0 or later** (GPL-3.0-or-later), the same license as WordPress and Advanced Product Fields for WooCommerce.

### GPL Rights

Under the GPL, you have the right to:
- ✅ Use the software for any purpose
- ✅ Study how the software works and modify it
- ✅ Distribute copies of the software
- ✅ Distribute modified versions

### Intended Use

This plugin is intended for:
- **Local development** environments
- **Staging servers** for testing
- **Educational purposes** and learning
- **Development** and **debugging**

**Important**: This plugin is NOT intended to circumvent purchasing a valid license for production use. Please support the original developers by purchasing a license for commercial/production sites.

## 🛠️ Technical Details

### How It Works

1. **License Filter**: Intercepts `pre_option_` and `option_` filters to return valid license data
2. **CSS Overrides**: Hides license warning banners and UI blocks
3. **JavaScript Handlers**: Restores field management functionality:
   - Detaches TinyBind from the data input to prevent overwrites
   - Implements manual click handlers for Add/Duplicate/Delete buttons
   - Syncs field data from DOM to hidden inputs before save
   - Generates field IDs matching WAPF's format (13-character hex)

### Field ID Generation

The plugin replicates WAPF's `uniqueId()` function:
```javascript
function generateFieldId() {
    // Generates 13-character hex ID: 8 chars (timestamp) + 5 chars (random)
    // Example: 67f4c9a2b8d1e
}
```

### Data Flow

1. User edits field in UI
2. Changes are synced to `data-raw-fields` attribute
3. On save, data is written to `wapf-fields` hidden input
4. WordPress processes the POST data normally
5. WAPF's backend saves the field configuration

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
git clone https://github.com/ssthormess/wapf-admin-fix.git
cd wapf-admin-fix
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- License bypass functionality
- Full field management restoration
- Add/Edit/Duplicate/Delete operations
- Data persistence fixes
- UI block removal

## 🐛 Bug Reports

Found a bug? Please [open an issue](https://github.com/ssthormess/wapf-admin-fix/issues) on GitHub.

## 📄 License

This plugin is licensed under the GPL-3.0-or-later license. See the [LICENSE](LICENSE) file for details.

## 👤 Author

**ssthormess**
- GitHub: [@ssthormess](https://github.com/ssthormess)
- Plugin URI: [https://github.com/ssthormess/wapf-admin-fix](https://github.com/ssthormess/wapf-admin-fix)

## 🙏 Acknowledgments

- Advanced Product Fields for WooCommerce team for creating the original plugin
- WordPress and WooCommerce communities
- GPL licensing framework that makes modifications like this possible

---

**Disclaimer**: This plugin is provided "as is" without warranty. Use at your own risk. Always test on non-production environments first.
