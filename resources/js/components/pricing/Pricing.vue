<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/dashboard" class="flex items-center hover:opacity-80 transition-opacity">
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
          <div class="flex items-center">
            <router-link to="/dashboard" class="text-gray-500 hover:text-gray-700 px-3 py-2">
              Back to Dashboard
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Free Tier -->
          <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
              <div class="text-4xl font-bold text-green-600 mb-4">$0</div>
              <p class="text-gray-600 mb-6">50 minutes/month</p>
              <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Basic voice agents
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Standard support
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Community forum
                </li>
              </ul>
              <button class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium">
                Current Plan
              </button>
            </div>
          </div>

          <!-- Basic Plan -->
          <div class="bg-white rounded-lg shadow-lg border-2 border-green-500 p-6 relative">
            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
              <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">Most Popular</span>
            </div>
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Basic</h3>
              <div class="text-4xl font-bold text-green-600 mb-4">$0.15</div>
              <p class="text-gray-600 mb-6">per minute</p>
              <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Unlimited voice agents
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Advanced AI models
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Priority support
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Analytics dashboard
                </li>
              </ul>
              <button class="w-full bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors">
                Get Started
              </button>
            </div>
          </div>

          <!-- Professional Plan -->
          <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
              <div class="text-4xl font-bold text-green-600 mb-4">$0.12</div>
              <p class="text-gray-600 mb-6">per minute</p>
              <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Everything in Basic
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Custom voice training
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Advanced analytics
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Dedicated account manager
                </li>
              </ul>
              <button class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                Contact Sales
              </button>
            </div>
          </div>

          <!-- Business Plan -->
          <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Business</h3>
              <div class="text-4xl font-bold text-green-600 mb-4">$0.09</div>
              <p class="text-gray-600 mb-6">per minute</p>
              <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Everything in Professional
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Custom integrations
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  SLA guarantees
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  24/7 phone support
                </li>
              </ul>
              <button class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                Contact Sales
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
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Free</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Basic</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Professional</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Monthly Minutes</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">50</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Unlimited</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Unlimited</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Unlimited</td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Price per Minute</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">$0.00</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">$0.15</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">$0.12</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">$0.09</td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Voice Agents</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">3</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Unlimited</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Unlimited</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Unlimited</td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Support</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Community</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Priority</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Dedicated</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">24/7 Phone</td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Analytics</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Basic</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Advanced</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Advanced</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Custom</td>
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
              <p class="text-gray-600">You are charged only for the minutes you use. No monthly fees, no hidden charges. Pay only for what you consume.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I change plans anytime?</h3>
              <p class="text-gray-600">Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">What happens if I exceed my free minutes?</h3>
              <p class="text-gray-600">You'll be automatically charged at the Basic plan rate ($0.15/minute) for any usage beyond your free tier.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Do you offer volume discounts?</h3>
              <p class="text-gray-600">Yes! Contact our sales team for custom pricing on high-volume usage and enterprise deployments.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Pricing'
}
</script> 