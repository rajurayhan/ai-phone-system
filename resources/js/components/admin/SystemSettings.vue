<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <Navigation />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              System Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your website's appearance and SEO settings
            </p>
          </div>
        </div>

        <!-- Settings Form -->
        <div class="mt-8">
          <div class="bg-white shadow rounded-lg">
            <form @submit.prevent="saveSettings">
              <!-- General Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">General Settings</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Site Title</label>
                  <input
                    v-model="settings.site_title"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Enter site title"
                  />
                  <p class="mt-1 text-sm text-gray-500">The main title of your website</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Site Tagline</label>
                  <input
                    v-model="settings.site_tagline"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Enter site tagline"
                  />
                  <p class="mt-1 text-sm text-gray-500">A short description or tagline for your site</p>
                </div>
              </div>

              <!-- SEO Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">SEO Settings</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                  <textarea
                    v-model="settings.meta_description"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Enter meta description for SEO"
                  ></textarea>
                  <p class="mt-1 text-sm text-gray-500">Description for search engines (SEO)</p>
                </div>
              </div>

              <!-- Appearance Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Appearance</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Site Logo</label>
                  <div class="flex items-center space-x-4">
                    <div v-if="settings.logo_url" class="flex-shrink-0">
                      <img :src="settings.logo_url" alt="Logo" class="h-12 w-auto">
                    </div>
                    <div class="flex-1">
                      <input
                        ref="logoInput"
                        type="file"
                        accept="image/*"
                        @change="handleLogoUpload"
                        class="hidden"
                      />
                      <button
                        type="button"
                        @click="$refs.logoInput.click()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                      >
                        {{ settings.logo_url ? 'Change Logo' : 'Upload Logo' }}
                      </button>
                      <p class="mt-1 text-sm text-gray-500">Recommended: 200x60px</p>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Homepage Banner</label>
                  <div class="flex items-center space-x-4">
                    <div v-if="settings.homepage_banner" class="flex-shrink-0">
                      <img :src="settings.homepage_banner" alt="Banner" class="h-24 w-auto">
                    </div>
                    <div class="flex-1">
                      <input
                        ref="bannerInput"
                        type="file"
                        accept="image/*"
                        @change="handleBannerUpload"
                        class="hidden"
                      />
                      <button
                        type="button"
                        @click="$refs.bannerInput.click()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                      >
                        {{ settings.homepage_banner ? 'Change Banner' : 'Upload Banner' }}
                      </button>
                      <p class="mt-1 text-sm text-gray-500">Recommended: 1200x400px</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Save Button -->
              <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="saving"
                    class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium"
                  >
                    {{ saving ? 'Saving...' : 'Save Settings' }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Navigation from '../shared/Navigation.vue'
import { showSuccess, showError } from '../../utils/sweetalert.js'
import { clearSettingsCache, updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'SystemSettings',
  components: {
    Navigation
  },
  setup() {
    const saving = ref(false)
    const settings = ref({
      site_title: '',
      site_tagline: '',
      meta_description: '',
      logo_url: '',
      homepage_banner: ''
    })
    const selectedLogoFile = ref(null)
    const selectedBannerFile = ref(null)

    const loadSettings = async () => {
      try {
        const response = await axios.get('/api/system-settings')
        if (response.data.success) {
          // Flatten the grouped settings
          const allSettings = {}
          Object.values(response.data.data).forEach(group => {
            group.forEach(setting => {
              allSettings[setting.key] = setting.value
            })
          })
          settings.value = allSettings
        }
      } catch (error) {
        console.error('Error loading settings:', error)
        showError('Failed to load settings')
      }
    }

    const saveSettings = async () => {
      try {
        saving.value = true
        
        // Create FormData for file uploads
        const formData = new FormData()
        
        // Add all settings as JSON
        const settingsArray = Object.entries(settings.value).map(([key, value]) => ({
          key,
          value
        }))
        formData.append('settings', JSON.stringify(settingsArray))
        
        // Add logo file if selected
        if (selectedLogoFile.value) {
          formData.append('logo_file', selectedLogoFile.value)
        }
        
        // Add banner file if selected
        if (selectedBannerFile.value) {
          formData.append('banner_file', selectedBannerFile.value)
        }

        const response = await axios.post('/api/system-settings', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data.success) {
          // Clear cache so new settings are loaded
          clearSettingsCache()
          // Update document title
          await updateDocumentTitle()
          showSuccess('Settings Saved', 'System settings updated successfully')
          
          // Clear selected files
          selectedLogoFile.value = null
          selectedBannerFile.value = null
          
          // Reload settings to get updated URLs
          await loadSettings()
        }
      } catch (error) {
        console.error('Error saving settings:', error)
        showError('Failed to save settings')
      } finally {
        saving.value = false
      }
    }

    const handleLogoUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Store the file for upload
        selectedLogoFile.value = file
        // Show preview
        settings.value.logo_url = URL.createObjectURL(file)
      }
    }

    const handleBannerUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Store the file for upload
        selectedBannerFile.value = file
        // Show preview
        settings.value.homepage_banner = URL.createObjectURL(file)
      }
    }

    onMounted(() => {
      loadSettings()
    })

    return {
      settings,
      saving,
      saveSettings,
      handleLogoUpload,
      handleBannerUpload,
      selectedLogoFile,
      selectedBannerFile
    }
  }
}
</script> 