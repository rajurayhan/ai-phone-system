<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Simple Navigation for Pricing Page -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="flex items-center hover:opacity-80 transition-opacity">
                <div class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">Hive AI Voice Agent</h1>
                </div>
              </router-link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/login" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
              Login
            </router-link>
            <router-link to="/register" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
              Sign Up
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
          <h1 class="text-4xl font-bold text-gray-900 mb-4">Voice Agent Pricing</h1>
          <p class="text-xl text-gray-600">Choose the perfect plan for your voice agent needs</p>
        </div>

        <!-- Pricing Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Dynamic Package Cards -->
          <div
            v-for="(pkg, index) in packages"
            :key="pkg.id"
            :class="[
              'bg-white rounded-lg shadow-lg border border-gray-200 p-6',
              pkg.is_popular ? 'relative' : ''
            ]"
          >
            <div v-if="pkg.is_popular" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
              <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                Most Popular
              </span>
            </div>
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ pkg.name }}</h3>
              <div class="text-4xl font-bold text-green-600 mb-4">${{ pkg.price }}</div>
              <p class="text-gray-600 mb-6">{{ pkg.description }}</p>
              <ul class="text-left space-y-3 mb-8">
                <li
                  v-for="feature in pkg.features"
                  :key="feature"
                  class="flex items-center"
                >
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  {{ feature }}
                </li>
              </ul>
              <router-link 
                v-if="!isPackageDisabled(pkg)"
                :to="isAuthenticated ? `/payment?package_id=${pkg.id}` : '/register'" 
                class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700 transition-colors"
              >
                {{ isAuthenticated ? `Get Started - $${pkg.price}` : 'Sign Up Now' }}
              </router-link>
              <button 
                v-else
                disabled
                class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg font-medium bg-gray-300 text-gray-500 cursor-not-allowed"
              >
                {{ pkg.price <= (currentSubscription?.package?.price || 0) ? 'Current Plan' : 'Lower Tier' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pricing Comparison -->
        <div class="mt-16">
          <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Pricing Comparison</h2>
          <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                  <th 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    {{ pkg.name }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Monthly Minutes</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.monthly_minutes_limit === -1 ? 'Unlimited' : pkg.monthly_minutes_limit.toLocaleString() }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Price per Month</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    ${{ pkg.price }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Voice Agents</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.voice_agents_limit === -1 ? 'Unlimited' : pkg.voice_agents_limit }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Analytics</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.analytics_level }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Support</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.support_level }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-16">
          <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>
          <div class="max-w-3xl mx-auto space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">How does billing work?</h3>
              <p class="text-gray-600">We offer simple monthly subscription plans. You pay a fixed monthly fee based on your chosen plan, with no hidden charges or per-minute fees.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I change plans anytime?</h3>
              <p class="text-gray-600">Yes, you can upgrade or downgrade your plan at any time. Changes take effect at the start of your next billing cycle.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">What happens if I exceed my monthly minutes?</h3>
              <p class="text-gray-600">For Starter and Professional plans, you'll be notified when you're approaching your limit. Enterprise plans include unlimited minutes.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Do you offer custom pricing for high-volume usage?</h3>
              <p class="text-gray-600">Yes! Contact our sales team for custom pricing on high-volume usage and enterprise deployments with specific requirements.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Pricing',
  setup() {
    const currentSubscription = ref(null)
    const packages = ref([])
    const isAuthenticated = ref(false)

    const checkAuthStatus = () => {
      const token = localStorage.getItem('token')
      isAuthenticated.value = !!token
    }

    const loadCurrentSubscription = async () => {
      if (!isAuthenticated.value) {
        currentSubscription.value = null
        return
      }

      try {
        const response = await axios.get('/api/subscriptions/current')
        currentSubscription.value = response.data.data
      } catch (error) {
        // No active subscription
        currentSubscription.value = null
      }
    }

    const loadPackages = async () => {
      try {
        const response = await axios.get('/api/subscriptions/packages')
        packages.value = response.data.data
      } catch (error) {
        console.error('Error loading packages:', error)
      }
    }

    const isPackageDisabled = (pkg) => {
      // If user is not authenticated, all packages are available
      if (!isAuthenticated.value) return false
      
      const currentPackage = currentSubscription.value?.package;
      if (!currentPackage) return false; // No current subscription, so all packages are available
      return pkg.price <= currentPackage.price;
    }

    onMounted(() => {
      checkAuthStatus()
      loadCurrentSubscription()
      loadPackages()
    })

    return {
      currentSubscription,
      packages,
      isAuthenticated,
      isPackageDisabled
    }
  }
}
</script> 