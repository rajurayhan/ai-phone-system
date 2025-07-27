<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-blue-100">
    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="flex items-center">
                <div v-if="settings.logo_url" class="h-8 w-auto">
                  <img :src="settings.logo_url" :alt="settings.site_title" class="h-full w-auto">
                </div>
                <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">{{ settings.site_title || 'XpartFone' }}</h1>
                </div>
              </div>
            </div>
          </div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
              <a href="#features" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium">Features</a>
              <a href="#pricing" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium">Pricing</a>
              <a href="#contact" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium">Contact</a>
              <!-- Show different buttons based on authentication status -->
              <template v-if="!isAuthenticated">
                <router-link to="/login" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium">Login</router-link>
                <router-link to="/register" class="btn-primary">Get Started</router-link>
              </template>
              <template v-else>
                <router-link to="/dashboard" class="btn-primary">Dashboard</router-link>
              </template>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative overflow-hidden">
      <div class="max-w-7xl mx-auto">
        <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
          <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
            <div class="sm:text-center lg:text-left">
              <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                <span class="block xl:inline">{{ settings.site_title || 'Revolutionary' }}</span>
                <span class="block text-primary-600 xl:inline">XpartFone</span>
                <span class="block xl:inline">Platform</span>
              </h1>
              <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                {{ settings.site_tagline || 'Transform your business with cutting-edge voice AI technology. Create intelligent voice agents that understand, respond, and engage with your customers 24/7.' }}
              </p>
              <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                <div class="rounded-md shadow">
                  <router-link v-if="!isAuthenticated" to="/register" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 md:py-4 md:text-lg md:px-10">
                    Get Started
                  </router-link>
                  <router-link v-else to="/dashboard" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 md:py-4 md:text-lg md:px-10">
                    Dashboard
                  </router-link>
                </div>
                <div class="mt-3 sm:mt-0 sm:ml-3">
                  <router-link to="/demo-request" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 md:py-4 md:text-lg md:px-10">
                    Request Demo
                  </router-link>
                </div>
              </div>
            </div>
          </main>
        </div>
      </div>
      <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
        <div class="h-56 w-full sm:h-72 md:h-96 lg:w-full lg:h-full bg-gradient-to-r from-primary-400 to-blue-500 rounded-lg shadow-xl"></div>
      </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-12 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center">
          <h2 class="text-base text-primary-600 font-semibold tracking-wide uppercase">Features</h2>
          <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
            Everything you need for XpartFone
          </p>
          <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
            Our platform provides all the tools you need to create, deploy, and manage intelligent voice agents.
          </p>
        </div>

        <div class="mt-10">
          <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
            <div
              v-for="feature in features"
              :key="feature.id"
              class="relative"
            >
              <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon" />
                </svg>
              </div>
              <p class="ml-16 text-lg leading-6 font-medium text-gray-900">{{ feature.title }}</p>
              <p class="mt-2 ml-16 text-base text-gray-500">
                {{ feature.description }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing Section -->
    <div id="pricing" class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
            Simple, transparent pricing
          </h2>
          <p class="mt-4 text-xl text-gray-600">
            Choose the plan that's right for your business
          </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <!-- Dynamic Package Cards -->
          <div
            v-for="(pkg, index) in packages"
            :key="pkg.id"
            :class="[
              'bg-white rounded-lg shadow-lg border p-8',
              pkg.is_popular ? 'border-2 border-primary-500 relative' : 'border-gray-200'
            ]"
          >
            <div v-if="pkg.is_popular" class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
              <span class="bg-primary-600 text-white px-3 py-1 rounded-full text-sm font-medium">Most Popular</span>
            </div>
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900">{{ pkg.name }}</h3>
              <div class="mt-4">
                <span class="text-4xl font-extrabold text-gray-900">${{ pkg.price }}</span>
                <span class="text-gray-600">/month</span>
              </div>
              <p class="mt-2 text-sm text-gray-600">{{ pkg.description }}</p>
            </div>
            <ul class="mt-8 space-y-4">
              <li
                v-for="feature in pkg.features"
                :key="feature"
                class="flex items-center"
              >
                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="ml-3 text-gray-700">{{ feature }}</span>
              </li>
            </ul>
            <div class="mt-8">
              <router-link to="/register" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 block text-center">
                Get Started
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-primary-700">
      <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
          <span class="block">Ready to get started?</span>
          <span class="block">Transform your business today.</span>
        </h2>
        <p class="mt-4 text-lg leading-6 text-primary-200">
          Join thousands of businesses already using our XpartFone platform.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <router-link v-if="!isAuthenticated" to="/register" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50">
            Sign up now
          </router-link>
          <router-link v-else to="/dashboard" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50">
            Dashboard
          </router-link>
          <router-link to="/demo-request" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 border border-white text-base font-medium rounded-md text-white hover:bg-primary-600">
            Request Demo
          </router-link>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8">
        <div class="xl:grid xl:grid-cols-3 xl:gap-8">
          <div class="space-y-8 xl:col-span-1">
            <div class="flex items-center">
              <div v-if="settings.logo_url" class="h-8 w-auto">
                <img :src="settings.logo_url" :alt="settings.site_title" class="h-full w-auto">
              </div>
              <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </div>
              <div class="ml-2">
                <h3 class="text-xl font-bold text-white">{{ settings.site_title || 'XpartFone' }}</h3>
              </div>
            </div>
            <p class="text-gray-300 text-base">
              The future of voice interaction is here. Transform your business with intelligent voice agents.
            </p>
          </div>
          <div class="mt-12 grid grid-cols-2 gap-8 xl:mt-0 xl:col-span-2">
            <div class="md:grid md:grid-cols-2 md:gap-8">
              <div>
                <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Solutions</h3>
                <ul class="mt-4 space-y-4">
                  <li><a href="#" class="text-base text-gray-300 hover:text-white">Customer Service</a></li>
                  <li><a href="#" class="text-base text-gray-300 hover:text-white">Sales Automation</a></li>
                  <li><a href="#" class="text-base text-gray-300 hover:text-white">Voice Analytics</a></li>
                </ul>
              </div>
              <div class="mt-12 md:mt-0">
                <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Support</h3>
                <ul class="mt-4 space-y-4">
                  <li><a href="#" class="text-base text-gray-300 hover:text-white">Documentation</a></li>
                  <li><a href="#" class="text-base text-gray-300 hover:text-white">API Reference</a></li>
                  <li><a href="#" class="text-base text-gray-300 hover:text-white">Contact Us</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-12 border-t border-gray-700 pt-8">
          <p class="text-base text-gray-400 xl:text-center">
            &copy; 2024 XpartFone. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'LandingPage',
  setup() {
    const packages = ref([])
    const features = ref([])
    const settings = ref({})

    // Check if user is authenticated
    const isAuthenticated = computed(() => {
      return localStorage.getItem('token') !== null
    })

    const loadPackages = async () => {
      try {
        const response = await axios.get('/api/subscriptions/packages')
        packages.value = response.data.data
      } catch (error) {
        console.error('Error loading packages:', error)
      }
    }

    const loadFeatures = async () => {
      try {
        const response = await axios.get('/api/features')
        features.value = response.data.data
      } catch (error) {
        console.error('Error loading features:', error)
      }
    }

    const loadSettings = async () => {
      try {
        const response = await axios.get('/api/public-settings')
        settings.value = response.data.data
      } catch (error) {
        console.error('Error loading settings:', error)
        // Set default values if API fails
        settings.value = {
          site_title: 'XpartFone',
          site_tagline: 'Revolutionary Voice AI Platform',
          logo_url: '/logo.png',
          homepage_banner: null
        }
      }
    }

    onMounted(() => {
      loadPackages()
      loadFeatures()
      loadSettings()
                  updateDocumentTitle(settings.value.site_title || 'XpartFone')
    })

    return {
      packages,
      features,
      isAuthenticated,
      settings
    }
  }
}
</script> 