=== Chromatic Aberration Hover ===
Contributors: exzent.de
Tags: chromatic, hover, mask, logo
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Chromatic Aberration Hover is a WordPress plugin that applies a configurable chromatic aberration visual effect to specified elements on your website. The effect creates a dynamic mask that follows the cursor, producing a stylized color separation effect commonly used in modern web design.

The plugin provides extensive customization options through an intuitive admin interface, allowing you to control which elements receive the effect, the color scheme, mask behavior, and tracking mode. This makes it ideal for enhancing logos, headings, and other prominent elements with an eye-catching hover interaction.

== Features ==

* Configurable target selectors for flexible element targeting
* Two color modes: dual-color split or RGB three-color separation
* Adjustable mask radius to control the size of the effect area
* Customizable shadow size for color separation intensity
* Per-element or global mouse tracking modes
* Custom event system for pausing and resuming effects during animations
* Automatic detection of dynamically added elements
* Performance optimized with requestAnimationFrame
* Responsive design support with automatic resize handling
* Deep style synchronization for complex nested elements

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/chromatic-aberration-hover` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to Settings > Chromatic Aberration Hover to configure the plugin options.
4. Specify your target CSS selectors and adjust the visual parameters to match your design requirements.

== Configuration ==

After activation, access the plugin settings page at Settings > Chromatic Aberration Hover in your WordPress admin panel.

**Enable Effect**
Toggle the effect on or off without deactivating the plugin.

**Target Selectors**
Enter comma-separated CSS selectors that should receive the chromatic aberration effect. Examples include:
* `.site-logo img, .site-logo svg` for logo images
* `h1.site-title` for specific headings
* `.custom-element` for any custom class

**Mask Radius**
Controls the size of the circular mask area in pixels. Larger values create a broader effect area, while smaller values produce a more focused spotlight effect. Default is 300 pixels.

**Shadow Size**
Determines the offset distance of the color channels in pixels. Higher values create more pronounced color separation. Default is 4 pixels.

**Color Mode**
Choose between two color modes:
* Two Colors: Uses left and right color channels for a simple split effect
* Three Colors (RGB Split): Uses separate red, green, and blue channels for a full RGB separation effect

**Color Selection**
Configure the colors used in the effect:
* Left Color: Used in two-color mode for the left channel
* Right Color: Used in two-color mode for the right channel
* Red, Green, Blue Colors: Used in three-color mode for respective RGB channels

**Tracking Mode**
Select how the effect follows mouse movement:
* Per-element hover only: Effect activates only when hovering directly over target elements
* Global mouse tracking: Effect follows mouse movement across the entire page and activates on target elements

**Pause and Resume Events**
Configure custom event names that allow other scripts to temporarily pause and resume the effect. This is useful when coordinating with other animations or interactions. Default events are 'cah-pause' and 'cah-resume'.

== Usage ==

Once configured, the plugin automatically applies the chromatic aberration effect to all elements matching your specified selectors. The effect activates on hover or mouse movement depending on your selected tracking mode.

For programmatic control, you can dispatch custom events to pause and resume the effect:

```javascript
// Pause the effect on a specific element
element.dispatchEvent(new CustomEvent('cah-pause'));

// Resume the effect
element.dispatchEvent(new CustomEvent('cah-resume'));
```

== Technical Details ==

The plugin uses CSS filters with drop-shadow effects to create the chromatic aberration appearance. A cloned overlay element is positioned above the original element, and a radial gradient mask is applied that follows the cursor position. The mask reveals the filtered clone, creating the color separation effect.

The implementation includes:
* MutationObserver for detecting dynamically added elements
* ResizeObserver for handling element size changes
* RequestAnimationFrame for smooth performance
* Deep style synchronization for nested elements with transforms
* Accessibility considerations with proper ARIA attributes

== Browser Compatibility ==

The plugin requires modern browser support for:
* CSS mask properties
* CSS filter properties
* MutationObserver API
* ResizeObserver API (optional, gracefully degrades)

== Frequently Asked Questions ==

= Does this work with SVG elements? =

Yes, the plugin supports both image and SVG elements, as well as text elements and other HTML content.

= Can I use this on multiple elements? =

Yes, you can specify multiple selectors separated by commas. The plugin will apply the effect to all matching elements.

= Will this affect page performance? =

The plugin is optimized for performance using requestAnimationFrame and efficient event handling. However, using the effect on many elements simultaneously may impact performance on lower-end devices.

= Can I customize the effect per element? =

Currently, all elements use the same global settings. For per-element customization, you would need to use separate selector groups or modify the plugin code.

= Does this work with page builders? =

Yes, as long as the page builder outputs standard HTML and your selectors match the generated elements.

== Changelog ==

= 0.1.0 =
* Initial release
* Configurable chromatic aberration hover effect
* Two and three color modes
* Per-element and global tracking modes
* Custom pause and resume event system
* Automatic element detection
* Admin settings interface

== Upgrade Notice ==

= 0.1.0 =
Initial release of Chromatic Aberration Hover plugin.

== Support ==

For support, feature requests, or bug reports, please visit the plugin page or contact the contributors.

== Credits ==

Developed by exzent.de

