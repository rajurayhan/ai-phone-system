<template>
  <footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <p class="text-sm text-gray-500">
          &copy; {{ new Date().getFullYear() }} {{ settings.company_name || 'sulus.ai' }}. All rights reserved.
        </p>
        <div class="mt-4 md:mt-0 flex space-x-6">
          <router-link to="/terms" class="text-sm text-gray-500 hover:text-gray-700">Terms of Service</router-link>
          <router-link to="/privacy" class="text-sm text-gray-500 hover:text-gray-700">Privacy Policy</router-link>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { ref, onMounted } from 'vue'
import { getSystemSettings } from '../../utils/systemSettings.js'

export default {
  name: 'SimpleFooter',
  setup() {
    const settings = ref({
      company_name: 'sulus.ai'
    })

    const loadSettings = async () => {
      try {
        const systemSettings = await getSystemSettings()
        settings.value = systemSettings
      } catch (error) {
        // Use default values if loading fails
        console.error('Error loading settings for footer:', error)
      }
    }

    onMounted(() => {
      loadSettings()
    })

    return {
      settings
    }
  }
}
</script> 