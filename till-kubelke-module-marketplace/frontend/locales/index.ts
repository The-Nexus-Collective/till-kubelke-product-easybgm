/**
 * Marketplace Module - Translation Resources
 * 
 * This file exports translation resources that can be dynamically loaded
 * by consuming applications (Product Frontends, Admin Frontend).
 * 
 * Usage in consuming app:
 * ```typescript
 * import { marketplaceLocales } from '@till-kubelke/module-marketplace/locales';
 * 
 * // Add to i18next resources
 * i18next.addResourceBundle('de-DE', 'marketplace', marketplaceLocales['de-DE']);
 * i18next.addResourceBundle('en-US', 'marketplace', marketplaceLocales['en-US']);
 * ```
 */

// Import all locale files
import marketplaceDe from './de-DE/marketplace.json';
import marketplaceEn from './en-US/marketplace.json';

export const marketplaceLocales = {
  'de-DE': marketplaceDe,
  'en-US': marketplaceEn,
} as const;

export type MarketplaceLocaleKey = keyof typeof marketplaceLocales;

// Re-export individual translations for tree-shaking
export { marketplaceDe, marketplaceEn };


