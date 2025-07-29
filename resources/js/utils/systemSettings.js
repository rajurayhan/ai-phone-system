import axios from 'axios'

let cachedSettings = null
let settingsPromise = null

/**
 * Get system settings with caching
 */
export const getSystemSettings = async () => {
  if (cachedSettings) {
    return cachedSettings
  }

  if (settingsPromise) {
    return settingsPromise
  }

  settingsPromise = axios.get('/api/public-settings')
    .then(response => {
      if (response.data.success) {
        cachedSettings = response.data.data
        return cachedSettings
      }
      return getDefaultSettings()
    })
    .catch(error => {
      console.error('Error loading system settings:', error)
      return getDefaultSettings()
    })

  return settingsPromise
}

/**
 * Get default settings if API fails
 */
const getDefaultSettings = () => {
  return {
    site_title: 'XpartFone',
    site_tagline: 'Never Miss a call Again XpartFone answers 24x7!',
    meta_description: 'Transform your business with cutting-edge voice AI technology',
    logo_url: '/logo.png',
    homepage_banner: null
  }
}

/**
 * Clear cached settings (useful after updates)
 */
export const clearSettingsCache = () => {
  cachedSettings = null
  settingsPromise = null
}

/**
 * Get a specific setting value
 */
export const getSetting = async (key, defaultValue = '') => {
  const settings = await getSystemSettings()
  return settings[key] || defaultValue
}

/**
 * Update document title with system settings
 */
export const updateDocumentTitle = async (pageTitle = '') => {
  try {
    const settings = await getSystemSettings()
    const siteTitle = settings.site_title || 'XpartFone'
    
    if (pageTitle) {
      // If pageTitle already contains the site title (like "XpartFone - Tagline"), use it as is
      if (pageTitle.includes(siteTitle)) {
        document.title = pageTitle
      } else {
        // Otherwise, append site title to page title
        document.title = `${pageTitle} - ${siteTitle}`
      }
    } else {
      document.title = siteTitle
    }
  } catch (error) {
    console.error('Error updating document title:', error)
    // Fallback to default title
    if (pageTitle) {
      document.title = pageTitle
    } else {
      document.title = 'XpartFone'
    }
  }
} 