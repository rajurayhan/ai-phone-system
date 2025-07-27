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
              Subscription Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your subscription and billing
            </p>
          </div>
        </div>

        <div class="mt-8">
          <!-- Current Subscription -->
          <div v-if="currentSubscription" class="bg-white shadow rounded-lg p-6 mb-8">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  Current Subscription
                  <span v-if="currentSubscription.status === 'pending'" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    Pending Payment
                  </span>
                  <span v-else-if="currentSubscription.status === 'active'" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Active
                  </span>
                </h3>
                <p class="text-gray-600 mb-4">{{ currentSubscription.package?.name }} - ${{ currentSubscription.package?.price }}/month</p>
                <p v-if="currentSubscription.status === 'pending'" class="text-sm text-purple-600 mb-2">
                  ⚠️ Your subscription is pending payment. Please complete the payment to activate your subscription.
                </p>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-500">Status</h4>
                <span :class="[
                  'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                  currentSubscription.status_badge_class
                ]">
                  {{ currentSubscription.status }}
                </span>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-500">Next Billing</h4>
                <p class="text-lg font-semibold text-gray-900">
                  {{ formatDate(currentSubscription.current_period_end) }}
                </p>
              </div>
            </div>
            
            <!-- Usage Statistics -->
            <div v-if="usage" class="mt-6">
              <h4 class="text-sm font-medium text-gray-500 mb-3">Usage</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Voice Agents</span>
                    <span class="text-sm font-semibold text-gray-900">
                      {{ usage.assistants.used }} / {{ usage.assistants.limit }}
                    </span>
                  </div>
                  <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-green-600 h-2 rounded-full" 
                        :style="{ width: getUsagePercentage(usage.assistants.used, usage.assistants.limit) + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Days Remaining</span>
                    <span class="text-sm font-semibold text-gray-900">
                      {{ usage.subscription.days_remaining }} days
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex space-x-3">
              <router-link 
                v-if="currentSubscription.status === 'pending'"
                :to="`/payment?package_id=${currentSubscription.package?.id}`"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Complete Payment
              </router-link>
              <button
                v-if="currentSubscription.status === 'active'"
                @click="showUpgradeModal = true"
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
              >
                Upgrade Plan
              </button>
              <button
                v-if="currentSubscription.status === 'active'"
                @click="cancelSubscription"
                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
              >
                Cancel Subscription
              </button>
            </div>
          </div>

          <!-- No Subscription -->
          <div v-else class="bg-white shadow rounded-lg p-6 mb-8">
            <div class="text-center">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">No Active Subscription</h3>
              <p class="text-gray-600 mb-4">Subscribe to a plan to start creating voice agents</p>
              <button
                @click="showPackagesModal = true"
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
              >
                View Plans
              </button>
            </div>
          </div>

          <!-- Available Packages -->
          <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Plans</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div
                v-for="pkg in packages"
                :key="pkg.id"
                :class="[
                  'border rounded-lg p-6',
                  pkg.is_popular ? 'border-green-500 relative' : 'border-gray-200'
                ]"
              >
                <div v-if="pkg.is_popular" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                  <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                    Most Popular
                  </span>
                </div>
                
                <div class="text-center">
                  <h4 class="text-xl font-bold text-gray-900 mb-2">{{ pkg.name }}</h4>
                  <div class="text-3xl font-bold text-green-600 mb-2">{{ pkg.formatted_price }}</div>
                  <p class="text-gray-600 mb-4">{{ pkg.description }}</p>
                  
                  <ul class="text-left space-y-2 mb-6">
                    <li
                      v-for="feature in pkg.features"
                      :key="feature"
                      class="flex items-center text-sm"
                    >
                      <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                      {{ feature }}
                    </li>
                  </ul>
                  
                  <router-link 
                    v-if="!isPackageDisabled(pkg)"
                    :to="`/payment?package_id=${pkg.id}`" 
                    class="w-full inline-flex justify-center items-center py-2 px-4 rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                  >
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Pay ${{ pkg.price }}
                  </router-link>
                  <button 
                    v-else
                    disabled
                    class="w-full inline-flex justify-center items-center py-2 px-4 rounded-lg font-medium bg-gray-300 text-gray-500 cursor-not-allowed"
                  >
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    {{ pkg.price === (currentSubscription?.package?.price || 0) ? 'Current Plan' : 'Lower or Same Tier' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upgrade Modal -->
    <div v-if="showUpgradeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Subscription Plan</h3>
          <div class="space-y-4">
            <div
              v-for="pkg in availableUpgrades"
              :key="pkg.id"
              :class="[
                'border rounded-lg p-4 transition-colors',
                isPackageDisabled(pkg) 
                  ? 'border-gray-200 bg-gray-50 cursor-not-allowed' 
                  : 'cursor-pointer hover:border-green-500'
              ]"
              @click="!isPackageDisabled(pkg) && upgradeToPackage(pkg)"
            >
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-semibold text-gray-900">{{ pkg.name }}</h4>
                  <p class="text-sm text-gray-600">{{ pkg.description }}</p>
                </div>
                <div class="text-right">
                  <div class="font-semibold text-green-600">${{ pkg.price }}</div>
                  <div class="text-xs text-gray-500">per month</div>
                </div>
              </div>
              <div v-if="isPackageDisabled(pkg)" class="mt-2 text-xs text-gray-500">
                {{ pkg.price === (currentSubscription?.package?.price || 0) ? 'Current plan' : 'Lower or same tier' }}
              </div>
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button
              @click="showUpgradeModal = false"
              class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Navigation from '../shared/Navigation.vue'
import { showSuccess, showError } from '../../utils/sweetalert.js'

export default {
  name: 'SubscriptionManager',
  components: {
    Navigation
  },
  setup() {
    const currentSubscription = ref(null)
    const packages = ref([])
    const usage = ref(null)
    const loading = ref(false)
    const showUpgradeModal = ref(false)
    const showPackagesModal = ref(false)

    const loadCurrentSubscription = async () => {
      try {
        const response = await axios.get('/api/subscriptions/current')
        currentSubscription.value = response.data.data
        await loadUsage()
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
        showError('Failed to load subscription packages')
      }
    }

    const loadUsage = async () => {
      try {
        const response = await axios.get('/api/subscriptions/usage')
        usage.value = response.data.data
      } catch (error) {
        // Handle error silently
      }
    }

    const subscribeToPackage = async (pkg) => {
      try {
        loading.value = true
        const response = await axios.post('/api/subscriptions/subscribe', {
          package_id: pkg.id
        })
        
        showSuccess('Subscription created successfully!')
        await loadCurrentSubscription()
      } catch (error) {
        showError(error.response?.data?.message || 'Failed to subscribe to package')
      } finally {
        loading.value = false
      }
    }

    const upgradeToPackage = async (pkg) => {
      try {
        loading.value = true
        const response = await axios.post('/api/subscriptions/upgrade', {
          package_id: pkg.id
        })
        
        showSuccess('Subscription upgraded successfully!')
        showUpgradeModal.value = false
        await loadCurrentSubscription()
      } catch (error) {
        showError(error.response?.data?.message || 'Failed to upgrade subscription')
      } finally {
        loading.value = false
      }
    }

    const cancelSubscription = async () => {
      if (!confirm('Are you sure you want to cancel your subscription?')) {
        return
      }

      try {
        loading.value = true
        await axios.post('/api/subscriptions/cancel')
        
        showSuccess('Subscription cancelled successfully')
        await loadCurrentSubscription()
      } catch (error) {
        showError(error.response?.data?.message || 'Failed to cancel subscription')
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString()
    }

    const getUsagePercentage = (used, limit) => {
      if (limit === 'Unlimited') return 0
      return Math.min((used / parseInt(limit)) * 100, 100)
    }

    const availableUpgrades = computed(() => {
      if (!currentSubscription.value) return packages.value
      
      // Return all packages but they will be disabled if they're current or lower tier
      return packages.value
    })

    const isPackageDisabled = (pkg) => {
      const currentPackage = currentSubscription.value?.package;
      
      if (!currentPackage) {
        return false; // No current subscription, so all packages are available
      }
      
      // Convert prices to numbers for proper comparison
      const pkgPrice = parseFloat(pkg.price);
      const currentPrice = parseFloat(currentPackage.price);
      
      const isDisabled = pkgPrice <= currentPrice;
      return isDisabled; // Disable packages with lower or equal prices (same or lower tier)
    };

    onMounted(() => {
      loadCurrentSubscription()
      loadPackages()
    })

    return {
      currentSubscription,
      packages,
      usage,
      loading,
      showUpgradeModal,
      showPackagesModal,
      subscribeToPackage,
      upgradeToPackage,
      cancelSubscription,
      formatDate,
      getUsagePercentage,
      availableUpgrades,
      isPackageDisabled
    }
  }
}
</script> 