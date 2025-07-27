<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <Navigation />

    <div class="py-6">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Payment Form
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Complete your payment to subscribe to our services
            </p>
          </div>
        </div>

        <!-- Payment Form -->
        <div class="mt-6 bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <form @submit.prevent="processPayment">
              <!-- Package Selection -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Package</label>
                <select v-model="form.package_id" required class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                  <option value="">Choose a package</option>
                  <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                    {{ pkg.name }} - ${{ pkg.price }}/month
                  </option>
                </select>
              </div>

              <!-- Payment Method -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <select v-model="form.payment_method" required class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                  <option value="">Choose payment method</option>
                  <option value="stripe">💳 Credit Card (Stripe)</option>
                  <option value="paypal">🔗 PayPal</option>
                  <option value="manual">👤 Manual Payment</option>
                </select>
              </div>

              <!-- Amount Display -->
              <div v-if="selectedPackage" class="mb-6 p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">Amount:</span>
                  <span class="text-lg font-bold text-gray-900">${{ selectedPackage.price }}</span>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                  Package: {{ selectedPackage.name }}
                </div>
              </div>

              <!-- Billing Information -->
              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Billing Email *</label>
                  <input 
                    v-model="form.billing_email" 
                    type="email" 
                    required 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="your@email.com"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Billing Name</label>
                  <input 
                    v-model="form.billing_name" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="Your Name"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Billing Address</label>
                  <input 
                    v-model="form.billing_address" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="123 Main St"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                  <input 
                    v-model="form.billing_city" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="City"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                  <input 
                    v-model="form.billing_state" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="State"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                  <input 
                    v-model="form.billing_country" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="Country"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                  <input 
                    v-model="form.billing_postal_code" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="12345"
                  />
                </div>
              </div>

              <!-- Description -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                <textarea 
                  v-model="form.description" 
                  rows="3" 
                  class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                  placeholder="Any additional notes about this payment..."
                ></textarea>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end">
                <button 
                  type="submit" 
                  :disabled="loading || !selectedPackage"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ loading ? 'Processing...' : `Pay $${selectedPackage ? selectedPackage.price : '$0.00'}` }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import axios from 'axios'
import { showSuccess, showError } from '../../utils/sweetalert.js'

export default {
  name: 'PaymentForm',
  components: {
    Navigation
  },
  data() {
    return {
      loading: false,
      packages: [],
      form: {
        package_id: '',
        payment_method: '',
        amount: 0,
        currency: 'USD',
        type: 'subscription',
        billing_email: '',
        billing_name: '',
        billing_address: '',
        billing_city: '',
        billing_state: '',
        billing_country: '',
        billing_postal_code: '',
        description: ''
      }
    }
  },
  computed: {
    selectedPackage() {
      return this.packages.find(pkg => pkg.id == this.form.package_id)
    }
  },
  async mounted() {
    await this.loadPackages()
    
    // Pre-fill billing email with user's email
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user.email) {
      this.form.billing_email = user.email
      this.form.billing_name = user.name || ''
    }
    
    // Pre-select package from URL parameter
    const urlParams = new URLSearchParams(window.location.search)
    const packageId = urlParams.get('package_id')
    if (packageId) {
      this.form.package_id = packageId
      // Set amount after packages are loaded
      await this.$nextTick()
      if (this.selectedPackage) {
        this.form.amount = this.selectedPackage.price
      }
    }
  },
  watch: {
    'form.package_id'(newVal) {
      if (newVal && this.selectedPackage) {
        this.form.amount = this.selectedPackage.price
      }
    },
    'selectedPackage'(newPackage) {
      if (newPackage && this.form.package_id == newPackage.id) {
        this.form.amount = newPackage.price
      }
    }
  },
  methods: {
    async loadPackages() {
      try {
        const response = await axios.get('/api/subscriptions/packages')
        this.packages = response.data.data || []
      } catch (error) {
        showError('Error', 'Failed to load subscription packages')
      }
    },

    async processPayment() {
      if (!this.selectedPackage) {
        showError('Error', 'Please select a package')
        return
      }

      try {
        this.loading = true

        // Create transaction
        const transactionData = {
          ...this.form,
          amount: this.selectedPackage.price,
          currency: 'USD',
          type: 'subscription'
        }

        const response = await axios.post('/api/transactions', transactionData)
        const transaction = response.data.data

        // Process payment
        const processResponse = await axios.post(`/api/transactions/${transaction.transaction_id}/process`)
        
        if (processResponse.data.success) {
          await showSuccess('Payment Successful', 'Your payment has been processed successfully and your subscription has been activated! You can now create voice assistants.')
          this.$router.push('/subscription')
        } else {
          showError('Payment Failed', processResponse.data.message || 'Payment processing failed')
        }
      } catch (error) {
        showError('Payment Error', error.response?.data?.message || 'Failed to process payment')
      } finally {
        this.loading = false
      }
    }
  }
}
</script> 