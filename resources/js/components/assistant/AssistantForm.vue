<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center">
            <button @click="goBack" class="mr-4 text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <div class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center mr-3">
              <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ isCreating ? 'Create Assistant' : 'Edit Assistant' }}</h1>
              <p class="text-gray-600">{{ isCreating ? 'Create a new voice assistant' : 'Update your voice assistant configuration' }}</p>
              <!-- Subscription Status (only show when creating and not admin) -->
              <div v-if="isCreating && subscriptionInfo && !isAdmin" class="mt-2 text-sm">
                <span class="text-gray-500">Current Plan: {{ subscriptionInfo.plan }}</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">Assistants: {{ subscriptionInfo.used }}/{{ subscriptionInfo.limit }}</span>
                <span v-if="subscriptionInfo.remaining > 0" class="ml-2 text-green-600">
                  ({{ subscriptionInfo.remaining }} remaining)
                </span>
                <span v-else class="ml-2 text-red-600">
                  (Limit reached)
                </span>
                <router-link v-if="subscriptionInfo.remaining <= 0" to="/subscription" class="ml-2 text-blue-600 hover:text-blue-700 underline">
                  Upgrade Plan
                </router-link>
              </div>
              <!-- No Subscription Warning (only show when creating and not admin) -->
              <div v-else-if="isCreating && !subscriptionInfo?.hasSubscription && !isAdmin" class="mt-2 text-sm">
                <span class="text-red-600 font-medium">No Active Subscription</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">Subscribe to create assistants</span>
                <router-link to="/subscription" class="ml-2 text-blue-600 hover:text-blue-700 underline">
                  Subscribe Now
                </router-link>
              </div>
              <!-- Admin Status (only show when creating and admin) -->
              <div v-else-if="isCreating && isAdmin" class="mt-2 text-sm">
                <span class="text-green-600 font-medium">Admin User</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">No subscription limits</span>
              </div>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <button
              @click="saveAssistant"
              :disabled="submitting"
              class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg flex items-center"
            >
              <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>
                {{ submitting ? (isCreating ? 'Creating...' : 'Updating...') : (isCreating ? 'Create Assistant' : 'Update Assistant') }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Loading assistant...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <p class="text-gray-600">{{ error }}</p>
        <button @click="loadAssistant" class="mt-4 text-green-600 hover:text-green-700">
          Try again
        </button>
      </div>

      <!-- Form -->
      <div v-else class="space-y-8">
        <!-- Company Information -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
              <input
                v-model="form.metadata.company_name"
                type="text"
                placeholder="Your Company Name"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.company_name 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              />
              <p v-if="fieldErrors.company_name" class="text-xs text-red-600 mt-1">{{ fieldErrors.company_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Industry *</label>
              <input
                v-model="form.metadata.industry"
                type="text"
                placeholder="e.g., Technology, Healthcare, Finance"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.industry 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              />
              <p v-if="fieldErrors.industry" class="text-xs text-red-600 mt-1">{{ fieldErrors.industry }}</p>
            </div>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Services/Products *</label>
            <textarea
              v-model="form.metadata.services_products"
              rows="3"
              placeholder="Describe your main services or products"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.services_products 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            ></textarea>
            <p v-if="fieldErrors.services_products" class="text-xs text-red-600 mt-1">{{ fieldErrors.services_products }}</p>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">SMS Phone Number</label>
            <input
              v-model="form.metadata.sms_phone_number"
              type="tel"
              placeholder="+1234567890"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.sms_phone_number 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            />
            <p v-if="fieldErrors.sms_phone_number" class="text-xs text-red-600 mt-1">{{ fieldErrors.sms_phone_number }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">Phone number to receive text messages</p>
          </div>
        </div>

        <!-- Agent Information -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Agent Information</h2>
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Agent Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                maxlength="40"
                placeholder="My Voice Assistant"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.name 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              />
              <p v-if="fieldErrors.name" class="text-xs text-red-600 mt-1">{{ fieldErrors.name }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">Maximum 40 characters</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assistant Type *</label>
              <select
                v-model="form.type"
                required
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
                  'border-gray-300 focus:border-green-500 bg-white'
                ]"
              >
                <option value="demo">Demo (Uses templates from settings)</option>
                <option value="production">Production (Custom configuration)</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                <strong>Demo:</strong> Uses pre-configured templates from system settings. Template variables will be automatically replaced with your company information.<br>
                <strong>Production:</strong> Custom configuration that you can fully customize for your specific needs.
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assistant Phone Number</label>
              
              <!-- Phone Number Purchase Section -->
              <div v-if="isCreating" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-sm font-medium text-blue-900">Purchase Twilio Phone Number</h4>
                  <div class="flex items-center space-x-2">
                    <input
                      v-model="areaCode"
                      type="text"
                      placeholder="Area Code (e.g., 212)"
                      maxlength="3"
                      class="text-xs px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <button
                      @click="loadAvailableNumbers"
                      :disabled="loadingNumbers"
                      class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                      <span v-if="loadingNumbers">Loading...</span>
                      <span v-else>Get Available Numbers</span>
                    </button>
                  </div>
                </div>
                
                <!-- Available Numbers List -->
                <div v-if="availableNumbers.length > 0" class="space-y-2">
                  <p class="text-xs text-blue-700 mb-2">Select a phone number to purchase when creating assistant:</p>
                  <div class="space-y-2 max-h-32 overflow-y-auto">
                    <div
                      v-for="number in availableNumbers"
                      :key="number.phone_number"
                      class="flex items-center justify-between p-2 bg-white border border-blue-200 rounded"
                    >
                      <div class="flex items-center space-x-2">
                        <input
                          type="radio"
                          :id="number.phone_number"
                          :value="number.phone_number"
                          v-model="selectedPhoneNumber"
                          name="phoneNumber"
                          class="text-blue-600 focus:ring-blue-500"
                        />
                        <label :for="number.phone_number" class="text-sm font-medium cursor-pointer">
                          {{ number.phone_number }}
                          <span v-if="number.locality" class="text-xs text-gray-500 ml-1">
                            ({{ number.locality }}, {{ number.region }})
                          </span>
                        </label>
                      </div>
                      <span v-if="selectedPhoneNumber === number.phone_number" class="text-xs text-green-600 font-medium">
                        Selected
                      </span>
                    </div>
                  </div>
                </div>
                
                <!-- Manual Phone Number Input -->
                <div class="mt-3">
                  <p class="text-xs text-blue-700 mb-2">Or enter a phone number manually:</p>
                  <input
                    v-model="form.metadata.assistant_phone_number"
                    type="tel"
                    placeholder="+1234567890"
                    :class="[
                      'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                      fieldErrors.assistant_phone_number 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                        : 'border-gray-300 focus:border-green-500 bg-white'
                    ]"
                  />
                </div>
              </div>
              
              <!-- Edit Mode - Simple Input -->
              <div v-else>
                <input
                  v-model="form.metadata.assistant_phone_number"
                  type="tel"
                  placeholder="+1234567890"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                    fieldErrors.assistant_phone_number 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                />
              </div>
              
              <p v-if="fieldErrors.assistant_phone_number" class="text-xs text-red-600 mt-1">{{ fieldErrors.assistant_phone_number }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">Optional phone number for this specific assistant</p>
            </div>
          </div>
        </div>

        <!-- Model Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Model Configuration</h2>
          <div class="space-y-6">
            <!-- System Prompt -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                System Prompt
                <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <textarea
                  v-model="form.model.messages[0].content"
                  rows="8"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 resize-none',
                    fieldErrors.systemPrompt 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                  placeholder="Enter the system prompt that defines the assistant's behavior..."
                ></textarea>
                
                <!-- Template/Actual Data Toggle for Demo Assistants -->
                <div v-if="form.type === 'demo'" class="absolute top-2 right-2 flex space-x-1">
                  <button
                    type="button"
                    @click="replaceWithTemplate('systemPrompt')"
                    class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 rounded"
                    title="Replace with templated data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="replaceWithActual('systemPrompt')"
                    class="p-1 text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 rounded"
                    title="Replace with actual Vapi data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <p v-if="fieldErrors.systemPrompt" class="text-xs text-red-600 mt-1">
                {{ fieldErrors.systemPrompt }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-1">
                Define the assistant's behavior and personality. You can use template variables: <code class="bg-gray-100 px-1 rounded">{{company_name}}</code>, <code class="bg-gray-100 px-1 rounded">{{company_industry}}</code>, <code class="bg-gray-100 px-1 rounded">{{company_services}}</code>
              </p>
            </div>
            
            <!-- Prompt Preview -->
            <div v-if="form.metadata.company_name || form.metadata.industry || form.metadata.services_products">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt Preview</label>
              <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700 max-h-40 overflow-y-auto">
                <pre class="whitespace-pre-wrap">{{ processedSystemPrompt }}</pre>
              </div>
              <p class="text-xs text-gray-500 mt-1">This is how your system prompt will appear with the company information filled in.</p>
            </div>
            
            <!-- First Message -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                First Message
                <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <textarea
                  v-model="form.firstMessage"
                  rows="4"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 resize-none',
                    fieldErrors.firstMessage 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                  placeholder="Enter the first message the assistant will say when a call starts..."
                ></textarea>
                
                <!-- Template/Actual Data Toggle for Demo Assistants -->
                <div v-if="form.type === 'demo'" class="absolute top-2 right-2 flex space-x-1">
                  <button
                    type="button"
                    @click="replaceWithTemplate('firstMessage')"
                    class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 rounded"
                    title="Replace with templated data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="replaceWithActual('firstMessage')"
                    class="p-1 text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 rounded"
                    title="Replace with actual Vapi data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <p v-if="fieldErrors.firstMessage" class="text-xs text-red-600 mt-1">
                {{ fieldErrors.firstMessage }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-1">
                The first message the assistant will say when a call starts. You can use template variables: <code class="bg-gray-100 px-1 rounded">{{company_name}}</code>, <code class="bg-gray-100 px-1 rounded">{{company_industry}}</code>, <code class="bg-gray-100 px-1 rounded">{{company_services}}</code>
              </p>
            </div>

            <!-- End Call Message -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                End Call Message
                <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <textarea
                  v-model="form.endCallMessage"
                  rows="4"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 resize-none',
                    fieldErrors.endCallMessage 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                  placeholder="Enter the message the assistant will say when ending a call..."
                ></textarea>
                
                <!-- Template/Actual Data Toggle for Demo Assistants -->
                <div v-if="form.type === 'demo'" class="absolute top-2 right-2 flex space-x-1">
                  <button
                    type="button"
                    @click="replaceWithTemplate('endCallMessage')"
                    class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 rounded"
                    title="Replace with templated data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="replaceWithActual('endCallMessage')"
                    class="p-1 text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 rounded"
                    title="Replace with actual Vapi data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <p v-if="fieldErrors.endCallMessage" class="text-xs text-red-600 mt-1">
                {{ fieldErrors.endCallMessage }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-1">
                The message the assistant will say when ending a call. You can use template variables: <code class="bg-gray-100 px-1 rounded">{{company_name}}</code>, <code class="bg-gray-100 px-1 rounded">{{company_industry}}</code>, <code class="bg-gray-100 px-1 rounded">{{company_services}}</code>
              </p>
            </div>
            
            <!-- First Message Preview -->
            <div v-if="form.metadata.company_name || form.metadata.industry || form.metadata.services_products" class="mt-3">
              <label class="block text-sm font-medium text-gray-700 mb-2">First Message Preview</label>
              <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700">
                  <pre class="whitespace-pre-wrap">{{ processedFirstMessage }}</pre>
                </div>
                <p class="text-xs text-gray-500 mt-1">This is how your first message will appear with the company information filled in.</p>
              </div>
            </div>
          </div>

        <!-- Messaging Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Messaging Configuration</h2>
          
          <!-- Webhook Configuration -->
          <div class="mt-6">
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">Server URL (Webhook)</label>
              <div class="flex items-center space-x-2">
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                  End-of-Call Report Enabled
                </span>
              </div>
            </div>
            <input
              v-model="form.metadata.webhook_url"
              type="url"
              placeholder="https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.webhook_url 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            />
            <p v-if="fieldErrors.webhook_url" class="text-xs text-red-600 mt-1">{{ fieldErrors.webhook_url }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">
              Server URL to receive webhook events. When provided, end-of-call reports will be automatically enabled and sent to this URL.
            </p>
          </div>
        </div>



        <!-- User Assignment (Admin Only) -->
        <div v-if="isAdmin" class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">User Assignment</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Assign to User *</label>
            <div v-if="loadingUsers" class="flex items-center space-x-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
              <span class="text-sm text-gray-500">Loading users...</span>
            </div>
            <select
              v-else
              v-model="form.user_id"
              required
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
                'border-gray-300 focus:border-green-500 bg-white'
              ]"
            >
              <option value="">Select a user to assign this assistant to</option>
              <option
                v-for="user in users"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }} ({{ user.email }}) - {{ user.role }}
              </option>
            </select>
            <p v-if="fieldErrors.user_assignment" class="text-xs text-red-600 mt-1">{{ fieldErrors.user_assignment }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">Choose which user will own this assistant</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { showError, showSuccess } from '../../utils/sweetalert.js'

export default {
  name: 'AssistantForm',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const loading = ref(false)
    const submitting = ref(false)
    const error = ref(null)
    
    // User assignment for admin
    const users = ref([])
    const loadingUsers = ref(false)
    const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'))
    const isAdmin = computed(() => currentUser.value.role === 'admin')
    
    // Subscription info for display
    const subscriptionInfo = ref(null)
    
    // Template data for demo assistants
    const templates = ref({
      system_prompt: '',
      first_message: '',
      end_call_message: ''
    })
    
    // Phone number purchase data
    const availableNumbers = ref([])
    const loadingNumbers = ref(false)
    const purchasingNumber = ref(false)
    const areaCode = ref('')
    const selectedPhoneNumber = ref('')
    
    // Check if we're creating a new assistant or editing an existing one
    const isCreating = computed(() => {
      return route.params.id === 'create' || !route.params.id
    })
    
    // Store actual Vapi data separately from form data
    const actualVapiData = ref({
      systemPrompt: '',
      firstMessage: '',
      endCallMessage: ''
    })
    
    // Store templated data separately
    const templatedData = ref({
      systemPrompt: '',
      firstMessage: '',
      endCallMessage: ''
    })
    
    const form = ref({
      name: '',
      model: {
        provider: 'openai',
        model: 'gpt-4o',
        messages: [
          {
            role: 'system',
            content: `## COMPANY PROFILE - 
\`\`\`
COMPANY_NAME: {{company_name}}
COMPANY_INDUSTRY: {{company_industry}}
COMPANY_SERVICES: {{company_name}} provides {{company_services}}
\`\`\`

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}, a leading {{company_industry}} company specializing in {{company_name}} provides {{company_services}}. 

You embody the highest standards of customer service that {{company_name}} would provide to their valued clients.`
          }
        ]
      },
      voice: {
        provider: 'vapi',
        voiceId: 'elliot'
      },
      firstMessage: 'Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?',
      endCallMessage: 'Thank you for calling {{company_name}}. Have a wonderful day!',
      metadata: {
        company_name: '',
        industry: '',
        services_products: '',
        sms_phone_number: '',
        assistant_phone_number: '',
        webhook_url: 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents'
      },
      user_id: null, // Will be set based on isAdmin computed value
      type: 'demo' // Default to demo
    })

    // Field-specific error states
    const fieldErrors = ref({
      name: '',
      systemPrompt: '',
      firstMessage: '',
      endCallMessage: '',
      company_name: '',
      industry: '',
      services_products: '',
      sms_phone_number: '',
      assistant_phone_number: '',
      webhook_url: '',
      user_assignment: '' // Added for admin user assignment
    })

    // Computed property to process system prompt with company information
    const processedSystemPrompt = computed(() => {
      let prompt = form.value.model.messages[0].content || ''
      
      // Replace template variables with actual company data
      prompt = prompt.replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
      prompt = prompt.replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
      prompt = prompt.replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      
      return prompt
    })

    // Computed property to process first message with company information
    const processedFirstMessage = computed(() => {
      let message = form.value.firstMessage || ''
      
      // Replace template variables with actual company data
      message = message.replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
      message = message.replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
      message = message.replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      
      return message
    })

    // Computed property to process end call message with company information
    const processedEndCallMessage = computed(() => {
      let message = form.value.endCallMessage || ''
      
      // Replace template variables with actual company data
      message = message.replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
      message = message.replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
      message = message.replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      
      return message
    })

    // Function to replace with templated data
    const replaceWithTemplate = (field) => {
      if (field === 'systemPrompt') {
        form.value.model.messages[0].content = templatedData.value.systemPrompt
      } else if (field === 'firstMessage') {
        form.value.firstMessage = templatedData.value.firstMessage
      } else if (field === 'endCallMessage') {
        form.value.endCallMessage = templatedData.value.endCallMessage
      }
    }

    // Function to replace with actual Vapi data
    const replaceWithActual = (field) => {
      if (field === 'systemPrompt') {
        form.value.model.messages[0].content = actualVapiData.value.systemPrompt
      } else if (field === 'firstMessage') {
        form.value.firstMessage = actualVapiData.value.firstMessage
      } else if (field === 'endCallMessage') {
        form.value.endCallMessage = actualVapiData.value.endCallMessage
      }
    }

    // Function to update templated data when company info changes
    const updateTemplatedData = () => {
      if (templates.value.system_prompt) {
        templatedData.value.systemPrompt = templates.value.system_prompt
          .replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
          .replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
          .replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      }
      
      if (templates.value.first_message) {
        templatedData.value.firstMessage = templates.value.first_message
          .replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
          .replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
          .replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      }
      
      if (templates.value.end_call_message) {
        templatedData.value.endCallMessage = templates.value.end_call_message
          .replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
          .replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
          .replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      }
    }

    // Watch for type changes to handle template loading
    watch(() => form.value.type, (newType) => {
      if (newType === 'demo' && templates.value.system_prompt) {
        // Auto-load templates for demo assistants
        loadDefaultTemplate()
        loadDefaultFirstMessage()
        loadDefaultEndCallMessage()
      }
    })

    // Watch for company information changes to update templated data
    watch([() => form.value.metadata.company_name, () => form.value.metadata.industry, () => form.value.metadata.services_products], () => {
      updateTemplatedData()
    }, { deep: true })

    // Watch for company name changes to auto-populate agent name
    watch(() => form.value.metadata.company_name, (newCompanyName) => {
      if (newCompanyName && newCompanyName.trim()) {
        // Auto-populate agent name based on company name for both create and edit
        form.value.name = `${newCompanyName.trim()} Assistant`
      } else if (!newCompanyName || !newCompanyName.trim()) {
        // Clear agent name if company name is empty
        form.value.name = ''
      }
    })

    const loadAssistant = async () => {
      // Check if user is logged in
      const token = localStorage.getItem('token')
      if (!token) {
        error.value = 'Please log in to access this assistant.'
        return
      }
      
      // If creating, don't load existing assistant data
      if (isCreating.value) {
        loading.value = false
        return
      }
      
      try {
        loading.value = true
        error.value = null
        const response = await axios.get(`/api/assistants/${route.params.id}`)
        const assistant = response.data.data
        
        // Store actual Vapi data separately
        actualVapiData.value.systemPrompt = assistant.vapi_data?.model?.messages?.[0]?.content || ''
        actualVapiData.value.firstMessage = assistant.vapi_data?.firstMessage || ''
        actualVapiData.value.endCallMessage = assistant.vapi_data?.endCallMessage || ''
        
        // Map the assistant data to our form structure - always use actual Vapi data
        form.value.name = assistant.name || ''
        form.value.firstMessage = actualVapiData.value.firstMessage
        form.value.endCallMessage = actualVapiData.value.endCallMessage
        
        // Map model configuration
        if (assistant.vapi_data?.model) {
          form.value.model.provider = assistant.vapi_data.model.provider || 'openai'
          form.value.model.model = assistant.vapi_data.model.model || 'gpt-4o'
          if (assistant.vapi_data.model.messages && assistant.vapi_data.model.messages.length > 0) {
            form.value.model.messages[0].content = actualVapiData.value.systemPrompt
          }
        }
        
        // Map voice configuration
        if (assistant.vapi_data?.voice) {
          form.value.voice.provider = assistant.vapi_data.voice.provider || 'vapi'
          form.value.voice.voiceId = assistant.vapi_data.voice.voiceId || 'elliot'
        }
        
        // Map metadata
        if (assistant.vapi_data?.metadata) {
          form.value.metadata.company_name = assistant.vapi_data.metadata.company_name || ''
          form.value.metadata.industry = assistant.vapi_data.metadata.industry || ''
          form.value.metadata.services_products = assistant.vapi_data.metadata.services_products || ''
          form.value.metadata.sms_phone_number = assistant.vapi_data.metadata.sms_phone_number || ''
          form.value.metadata.assistant_phone_number = assistant.vapi_data.metadata.assistant_phone_number || ''
          form.value.metadata.webhook_url = assistant.vapi_data.metadata.webhook_url || ''
        }
        
        // Map phone_number from database
        if (assistant.phone_number) {
          form.value.metadata.assistant_phone_number = assistant.phone_number
        }
        
        // Map webhook_url from database
        if (assistant.webhook_url) {
          form.value.metadata.webhook_url = assistant.webhook_url
        }
        
        // Map user_id for admin assignment
        if (isAdmin.value) {
          form.value.user_id = assistant.user_id || null
        }
        
        // Map type - check both database and Vapi metadata
        form.value.type = assistant.type || assistant.vapi_data?.metadata?.type || 'demo'
        
        // Load templates and update templated data
        await loadTemplates()
        updateTemplatedData()
        
      } catch (err) {
        console.error('Error loading assistant:', err)
        
        if (err.response) {
          const { status, data } = err.response
          
          switch (status) {
            case 401:
              error.value = 'Please log in to access this assistant.'
              break
              
            case 403:
              error.value = 'You do not have permission to access this assistant.'
              break
              
            case 404:
              error.value = 'Assistant not found. Please check the URL and try again.'
              break
              
            case 500:
              error.value = 'Server error occurred. Please try again later.'
              break
              
            default:
              if (data.message) {
                error.value = data.message
              } else {
                error.value = `Failed to load assistant (Status: ${status}). Please try again.`
              }
          }
        } else if (err.request) {
          // Network error
          error.value = 'Network error. Please check your internet connection and try again.'
        } else {
          // Other errors
          error.value = 'Failed to load assistant. Please check your connection and try again.'
        }
      } finally {
        loading.value = false
      }
    }

    const loadUsers = async () => {
      if (!isAdmin.value) return
      
      try {
        loadingUsers.value = true
        const response = await axios.get('/api/admin/users/for-assignment')
        users.value = response.data.data || []
      } catch (error) {
        console.error('Error loading users:', error)
      } finally {
        loadingUsers.value = false
      }
    }

    const loadSubscriptionInfo = async () => {
      if (!isCreating.value) return // Only load for creation
      if (isAdmin.value) return // Don't load for admin users
      
      try {
        const response = await axios.get('/api/subscriptions/usage')
        const usage = response.data.data
        
        if (usage && usage.package) {
          subscriptionInfo.value = {
            plan: usage.package.name || 'No Plan',
            used: usage.assistants.used || 0,
            limit: usage.assistants.limit || 0,
            remaining: usage.assistants.remaining || 0,
            hasSubscription: true
          }
        } else {
          // No active subscription
          subscriptionInfo.value = {
            plan: 'No Plan',
            used: 0,
            limit: 0,
            remaining: 0,
            hasSubscription: false
          }
        }
      } catch (error) {
        console.error('Error loading subscription info:', error)
        // If 404, it means no active subscription
        if (error.response && error.response.status === 404) {
          subscriptionInfo.value = {
            plan: 'No Plan',
            used: 0,
            limit: 0,
            remaining: 0,
            hasSubscription: false
          }
        } else {
          // Set default values if API fails - assume no subscription
          subscriptionInfo.value = {
            plan: 'Unknown',
            used: 0,
            limit: 0,
            remaining: 0,
            hasSubscription: false
          }
        }
      }
    }

    const saveAssistant = async () => {
      try {
        submitting.value = true
        error.value = null
        
        // Clear all field errors
        Object.keys(fieldErrors.value).forEach(key => {
          fieldErrors.value[key] = ''
        })
        
        // Client-side validation
        let hasErrors = false
        
        if (!form.value.name.trim()) {
          fieldErrors.value.name = 'Agent Name is required'
          hasErrors = true
        }
        
        if (!form.value.model.messages[0].content.trim()) {
          fieldErrors.value.systemPrompt = 'System Prompt is required'
          hasErrors = true
        }
        
        if (!form.value.firstMessage.trim()) {
          fieldErrors.value.firstMessage = 'First Message is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.company_name.trim()) {
          fieldErrors.value.company_name = 'Company Name is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.industry.trim()) {
          fieldErrors.value.industry = 'Industry is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.services_products.trim()) {
          fieldErrors.value.services_products = 'Services/Products is required'
          hasErrors = true
        }
        
        // Admin user assignment validation
        if (isAdmin.value && !form.value.user_id) {
          fieldErrors.value.user_assignment = 'Please select a user to assign this assistant to'
          hasErrors = true
        }
        
        if (hasErrors) {
          return
        }
        
        // Process the system prompt with company information before saving
        const processedPrompt = processedSystemPrompt.value
        
        // Prepare the data according to Vapi API structure
        const assistantData = {
          name: form.value.name,
          type: form.value.type, // Add type field
          model: {
            ...form.value.model,
            messages: [
              {
                role: 'system',
                content: isCreating.value ? processedSystemPrompt.value : form.value.model.messages[0].content
              }
            ]
          },
          voice: form.value.voice,
          firstMessage: isCreating.value ? processedFirstMessage.value : form.value.firstMessage, // Use processed for create, actual for update
          endCallMessage: isCreating.value ? processedEndCallMessage.value : form.value.endCallMessage, // Use processed for create, actual for update
          metadata: {
            ...form.value.metadata,
            user_id: form.value.user_id || currentUser.value.id,
            user_email: currentUser.value.email,
            updated_at: new Date().toISOString()
          },
          user_id: form.value.user_id || currentUser.value.id // Add user_id to main data
        }
        
        // Add selected phone number if chosen
        if (selectedPhoneNumber.value) {
          assistantData.selected_phone_number = selectedPhoneNumber.value
        }
        
        if (isCreating.value) {
          await axios.post('/api/assistants', assistantData)
          await showSuccess('Success', 'Assistant created successfully!')
          // Navigate based on user role
          if (isAdmin.value) {
            router.push('/admin/assistants')
          } else {
            router.push('/assistants')
          }
        } else {
          await axios.put(`/api/assistants/${route.params.id}`, assistantData)
          await showSuccess('Success', 'Assistant updated successfully!')
          // Navigate based on user role
          if (isAdmin.value) {
            router.push('/admin/assistants')
          } else {
            router.push('/assistants')
          }
        }
      } catch (err) {
        console.error('Error saving assistant:', err)
        
        if (err.response) {
          const { status, data } = err.response
          
          switch (status) {
            case 400:
            case 422:
              // Validation errors - map to specific fields
              if (data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                  const fieldName = field.replace(/\./g, '_') // Convert metadata.company_name to metadata_company_name
                  const errorMessage = Array.isArray(messages) ? messages.join(', ') : messages
                  
                  // Map server field names to our field error keys
                  const fieldMapping = {
                    'name': 'name',
                    'model.messages.0.content': 'systemPrompt',
                    'firstMessage': 'firstMessage',
                    'endCallMessage': 'endCallMessage',
                    'metadata.company_name': 'company_name',
                    'metadata.industry': 'industry',
                    'metadata.services_products': 'services_products',
                    'metadata.sms_phone_number': 'sms_phone_number',
                    'user_id': 'user_assignment' // Map user_id to user_assignment
                  }
                  
                  const mappedField = fieldMapping[field] || fieldName
                  if (fieldErrors.value.hasOwnProperty(mappedField)) {
                    fieldErrors.value[mappedField] = errorMessage
                  }
                })
                await showError('Validation Error', 'Please check the form and fix the errors.');
              } else if (data.message) {
                error.value = data.message
                await showError('Update Failed', data.message);
              } else {
                error.value = 'Invalid data provided. Please check your inputs and try again.'
                await showError('Update Failed', 'Invalid data provided. Please check your inputs and try again.');
              }
              break
              
            case 401:
              error.value = 'You are not authorized to update this assistant. Please log in again.'
              await showError('Authentication Error', 'You are not authorized to update this assistant. Please log in again.');
              break
              
            case 403:
              if (data.message && data.message.includes('need an active subscription')) {
                error.value = 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
                await showError('Subscription Required', 'You need an active subscription to create assistants. Please subscribe to a plan to get started.');
              } else if (data.message && data.message.includes('assistant limit')) {
                error.value = 'You have reached your assistant limit for your current subscription plan. Please upgrade your plan to create more assistants.'
                await showError('Subscription Limit Reached', 'You have reached your assistant limit for your current subscription plan. Please upgrade your plan to create more assistants.');
              } else {
                error.value = 'You do not have permission to update this assistant.'
                await showError('Permission Denied', 'You do not have permission to update this assistant.');
              }
              break
              
            case 404:
              error.value = 'Assistant not found. Please check the URL and try again.'
              await showError('Not Found', 'Assistant not found. Please check the URL and try again.');
              break
              
            case 500:
              error.value = 'Server error occurred. Please try again later.'
              await showError('Server Error', 'Server error occurred. Please try again later.');
              break
              
            default:
              if (data.message) {
                error.value = data.message
                await showError('Update Failed', data.message);
              } else {
                error.value = `Failed to update assistant (Status: ${status}). Please try again.`
                await showError('Update Failed', `Failed to update assistant (Status: ${status}). Please try again.`);
              }
          }
        } else if (err.request) {
          // Network error
          error.value = 'Network error. Please check your internet connection and try again.'
          await showError('Network Error', 'Network error. Please check your internet connection and try again.');
        } else {
          // Other errors
          error.value = 'An unexpected error occurred. Please try again.'
          await showError('Error', 'An unexpected error occurred. Please try again.');
        }
      } finally {
        submitting.value = false
      }
    }

    const loadTemplates = async () => {
      try {
        const response = await axios.get('/api/assistant-templates')
        if (response.data.success) {
          templates.value = response.data.data
          // Update templated data with current company info
          updateTemplatedData()
        }
      } catch (error) {
        console.error('Error loading templates:', error)
      }
    }

    const loadDefaultTemplate = () => {
      // This function is now only used for creating new assistants
      if (isCreating.value && form.value.type === 'demo') {
        form.value.model.messages[0].content = templates.value.system_prompt || `## COMPANY PROFILE - 
\`\`\`
COMPANY_NAME: {{company_name}}
COMPANY_INDUSTRY: {{company_industry}}
COMPANY_SERVICES: {{company_name}} provides {{company_services}}
\`\`\`

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}, a leading {{company_industry}} company specializing in {{company_name}} provides {{company_services}}. 

You embody the highest standards of customer service that {{company_name}} would provide to their valued clients.`
      }
    }

    const loadDefaultFirstMessage = () => {
      // This function is now only used for creating new assistants
      if (isCreating.value && form.value.type === 'demo') {
        form.value.firstMessage = templates.value.first_message || 'Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?'
      }
    }

    const loadDefaultEndCallMessage = () => {
      // This function is now only used for creating new assistants
      if (isCreating.value && form.value.type === 'demo') {
        form.value.endCallMessage = templates.value.end_call_message || 'Thank you for calling {{company_name}}. Have a wonderful day!'
      }
    }

    const goBack = () => {
      // Try to go back in browser history, fallback to appropriate route
      if (window.history.length > 1) {
        router.go(-1)
      } else {
        // Fallback based on user role
        if (isAdmin.value) {
          router.push('/admin/assistants')
        } else {
          router.push('/assistants')
        }
      }
    }

    const loadAvailableNumbers = async () => {
      try {
        loadingNumbers.value = true
        const params = {}
        if (areaCode.value.trim()) {
          params.area_code = areaCode.value.trim()
        }
        
        const response = await axios.get('/api/twilio/available-numbers', { params })
        
        if (response.data.success) {
          availableNumbers.value = response.data.data
          selectedPhoneNumber.value = '' // Reset selection
        } else {
          await showError('Error', 'Failed to load available phone numbers')
        }
      } catch (error) {
        console.error('Error loading available numbers:', error)
        await showError('Error', 'Failed to load available phone numbers')
      } finally {
        loadingNumbers.value = false
      }
    }



    // Watch for type changes to handle template loading
    watch(() => form.value.type, (newType) => {
      if (newType === 'demo' && templates.value.system_prompt) {
        // Auto-load templates for demo assistants
        loadDefaultTemplate()
        loadDefaultFirstMessage()
        loadDefaultEndCallMessage()
      }
    })

    // Watch for company information changes to update templated data
    watch([() => form.value.metadata.company_name, () => form.value.metadata.industry, () => form.value.metadata.services_products], () => {
      updateTemplatedData()
    }, { deep: true })

    // Watch for company name changes to auto-populate agent name
    watch(() => form.value.metadata.company_name, (newCompanyName) => {
      if (newCompanyName && newCompanyName.trim()) {
        // Auto-populate agent name based on company name for both create and edit
        form.value.name = `${newCompanyName.trim()} Assistant`
      } else if (!newCompanyName || !newCompanyName.trim()) {
        // Clear agent name if company name is empty
        form.value.name = ''
      }
    })

    onMounted(async () => {
      await loadTemplates() // Load templates first
      await loadAssistant()
      loadUsers()
      loadSubscriptionInfo()
      
      // Set default user for admin when creating
      if (isCreating.value && isAdmin.value && currentUser.value.id) {
        form.value.user_id = currentUser.value.id
      }
    })

    return {
      loading,
      submitting,
      error,
      form,
      fieldErrors,
      processedSystemPrompt,
      processedFirstMessage,
      processedEndCallMessage,
      isCreating,
      loadAssistant,
      saveAssistant,
      goBack,
      users,
      loadingUsers,
      isAdmin,
      subscriptionInfo,
      templates,
      availableNumbers,
      loadingNumbers,
      purchasingNumber,
      areaCode,
      selectedPhoneNumber,
      loadAvailableNumbers,
      replaceWithTemplate,
      replaceWithActual,
      actualVapiData,
      templatedData
    }
  }
}
</script> 