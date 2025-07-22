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
              <!-- Subscription Status (only show when creating) -->
              <div v-if="isCreating && subscriptionInfo" class="mt-2 text-sm">
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
              {{ submitting ? (isCreating ? 'Creating...' : 'Updating...') : (isCreating ? 'Create Assistant' : 'Update Assistant') }}
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
        <!-- Basic Information -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
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
        </div>

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

        <!-- Model Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Model Configuration</h2>
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">System Prompt</label>
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Use {{company_name}}, {{company_industry}}, and {{company_services}} as template variables</span>
                <button
                  @click="loadDefaultTemplate"
                  type="button"
                  class="text-sm text-green-600 hover:text-green-700 font-medium"
                >
                  Load Default Template
                </button>
              </div>
              <textarea
                v-model="form.model.messages[0].content"
                rows="8"
                placeholder="## COMPANY PROFILE - 
```
COMPANY_NAME: {{company_name}}
COMPANY_INDUSTRY: {{company_industry}}
COMPANY_SERVICES: {{company_services}}
```

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}..."
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.systemPrompt 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              ></textarea>
              <p v-if="fieldErrors.systemPrompt" class="text-xs text-red-600 mt-1">{{ fieldErrors.systemPrompt }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">Define the assistant's behavior and capabilities. The template variables will be automatically replaced with your company information.</p>
            </div>
            
            <!-- Prompt Preview -->
            <div v-if="form.metadata.company_name || form.metadata.industry || form.metadata.services_products">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt Preview</label>
              <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700 max-h-40 overflow-y-auto">
                <pre class="whitespace-pre-wrap">{{ processedSystemPrompt }}</pre>
              </div>
              <p class="text-xs text-gray-500 mt-1">This is how your system prompt will appear with the company information filled in.</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">First Message</label>
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Use {{company_name}}, {{company_industry}}, and {{company_services}} as template variables</span>
                <button
                  @click="loadDefaultFirstMessage"
                  type="button"
                  class="text-sm text-green-600 hover:text-green-700 font-medium"
                >
                  Load Default Template
                </button>
              </div>
              <textarea
                v-model="form.firstMessage"
                rows="3"
                maxlength="1000"
                placeholder="Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.firstMessage 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              ></textarea>
              <p v-if="fieldErrors.firstMessage" class="text-xs text-red-600 mt-1">{{ fieldErrors.firstMessage }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">Maximum 1000 characters. This is the first message the assistant will say.</p>
              
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
        </div>

        <!-- Messaging Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Messaging Configuration</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Call Message</label>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm text-gray-600">Use {{company_name}}, {{company_industry}}, and {{company_services}} as template variables</span>
              <button
                @click="loadDefaultEndCallMessage"
                type="button"
                class="text-sm text-green-600 hover:text-green-700 font-medium"
              >
                Load Default Template
              </button>
            </div>
            <textarea
              v-model="form.endCallMessage"
              rows="3"
              maxlength="1000"
              placeholder="Thank you for calling {{company_name}}. Have a wonderful day!"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.endCallMessage 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            ></textarea>
            <p v-if="fieldErrors.endCallMessage" class="text-xs text-red-600 mt-1">{{ fieldErrors.endCallMessage }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">Message said when the call ends (max 1000 characters)</p>
            
            <!-- End Call Message Preview -->
            <div v-if="form.metadata.company_name || form.metadata.industry || form.metadata.services_products" class="mt-3">
              <label class="block text-sm font-medium text-gray-700 mb-2">End Call Message Preview</label>
              <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700">
                <pre class="whitespace-pre-wrap">{{ processedEndCallMessage }}</pre>
              </div>
              <p class="text-xs text-gray-500 mt-1">This is how your end call message will appear with the company information filled in.</p>
            </div>
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
import { ref, onMounted, computed } from 'vue'
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
    
    // Check if we're creating a new assistant or editing an existing one
    const isCreating = computed(() => {
      return route.params.id === 'create' || !route.params.id
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
COMPANY_SERVICES: {{company_services}}
\`\`\`

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}, a leading {{company_industry}} company specializing in {{company_services}}. 

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
        sms_phone_number: ''
      },
      user_id: null // Will be set based on isAdmin computed value
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
        
        // Map the assistant data to our form structure
        form.value.name = assistant.name || ''
        form.value.firstMessage = assistant.vapi_data?.firstMessage || ''
        form.value.endCallMessage = assistant.vapi_data?.endCallMessage || ''
        
        // Map model configuration
        if (assistant.vapi_data?.model) {
          form.value.model.provider = assistant.vapi_data.model.provider || 'openai'
          form.value.model.model = assistant.vapi_data.model.model || 'gpt-4o'
          if (assistant.vapi_data.model.messages && assistant.vapi_data.model.messages.length > 0) {
            form.value.model.messages[0].content = assistant.vapi_data.model.messages[0].content || ''
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
        }
        
        // Map user_id for admin assignment
        if (isAdmin.value) {
          form.value.user_id = assistant.user_id || null
        }
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
      
      try {
        const response = await axios.get('/api/subscriptions/usage')
        const usage = response.data.data
        
        if (usage && usage.subscription) {
          subscriptionInfo.value = {
            plan: usage.subscription.package?.name || 'No Plan',
            used: usage.subscription.assistants_used || 0,
            limit: usage.subscription.package?.voice_agents_limit || 0,
            remaining: (usage.subscription.package?.voice_agents_limit || 0) - (usage.subscription.assistants_used || 0)
          }
        }
      } catch (error) {
        console.error('Error loading subscription info:', error)
        // Set default values if API fails
        subscriptionInfo.value = {
          plan: 'Unknown',
          used: 0,
          limit: 0,
          remaining: 0
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
          model: {
            ...form.value.model,
            messages: [
              {
                role: 'system',
                content: processedPrompt
              }
            ]
          },
          voice: form.value.voice,
          firstMessage: processedFirstMessage.value, // Use processed first message
          endCallMessage: processedEndCallMessage.value, // Use processed end call message
          metadata: {
            ...form.value.metadata,
            user_id: form.value.user_id || currentUser.value.id,
            user_email: currentUser.value.email,
            updated_at: new Date().toISOString()
          },
          user_id: form.value.user_id || currentUser.value.id // Add user_id to main data
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
              if (data.message && data.message.includes('assistant limit')) {
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

    const loadDefaultTemplate = () => {
      form.value.model.messages[0].content = `## COMPANY PROFILE - 
\`\`\`
COMPANY_NAME: {{company_name}}
COMPANY_INDUSTRY: {{company_industry}}
COMPANY_SERVICES: {{company_services}}
\`\`\`

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}, a leading {{company_industry}} company specializing in {{company_services}}. 

You embody the highest standards of customer service that {{company_name}} would provide to their valued clients.

## Primary Objectives
Your main goals during this call are to:
1. **Demonstrate Professional Excellence**: Show how advanced customer service technology can enhance {{company_name}}'s customer experience
2. **Showcase Industry Knowledge**: Display understanding of {{company_industry}} terminology and common needs
3. **Handle Inquiries Naturally**: Respond to typical business questions with confidence
4. **Provide Exceptional Experience**: Leave callers impressed with the service quality

## Core Conversation Guidelines

### Opening & Greeting
- Always answer with: "Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?"
- **If directly asked about being automated/AI**: "Yes, I'm an automated assistant designed to provide excellent customer service for {{company_name}}. How can I help you with our {{company_services}} today?"
- **If asked "Are you real/human?"**: "I'm an automated system, but I'm here to provide you with real help and accurate information about {{company_name}}. What can I assist you with?"

### Industry-Specific Knowledge Base
**For {{company_industry}} businesses, I can assist with:**
- General information about our {{company_services}}
- Business hours and location details
- Scheduling consultations or appointments
- Explaining our service process and approach
- Connecting callers with the right department or specialist
- Basic pricing and service option inquiries

### Common Business Scenarios

#### **Business Hours & Location**
"Our business hours are Monday through Friday, 9 AM to 5 PM Eastern Time. We're located at [Standard Business District]. Would you like me to help you schedule a time to visit or speak with one of our specialists?"

#### **Service Inquiries**
"I'd be happy to explain our {{company_services}}. As a {{company_industry}} company, we focus on [provide 2-3 relevant benefits]. What specific aspect would you like to know more about?"

#### **Pricing Questions**
"Our pricing varies based on your specific needs and the scope of services required. I can connect you with one of our specialists who can provide a customized quote. Would you prefer a call back or would you like to schedule a consultation?"

#### **Scheduling & Appointments**
"I can help you schedule a consultation with one of our {{company_industry}} experts. What type of service are you interested in, and what days work best for you this week?"

#### **Complaint or Issue Resolution**
"I sincerely apologize for any inconvenience you've experienced. Your satisfaction is extremely important to {{company_name}}. Let me gather some details so I can ensure this gets resolved quickly. Can you tell me more about the situation?"

### Advanced Scenario Handling

#### **Outside Scope Requests**
"That's a great question, but it's outside my area of expertise. Let me connect you with [Specialist/Manager] who can give you the detailed information you need. Would you prefer I transfer you now or have someone call you back?"

#### **Technical or Complex Questions**
"That requires our technical expertise to answer properly. Rather than give you incomplete information, I'd like to connect you with our {{company_industry}} specialist who can provide you with accurate, detailed answers."

#### **Angry or Frustrated Customers**
"I understand your frustration, and I want to help resolve this for you. {{company_name}} values every client relationship. Let me get you connected with our management team immediately so we can address this properly."

#### **Competitor Comparisons**
"{{company_name}} focuses on providing exceptional {{company_services}} tailored to each client's unique needs. I'd love to have one of our specialists discuss how our approach might benefit your specific situation. When would be a good time for them to call you?"

### Information Collection
When gathering details, ask:
- "May I get your name and the best number to reach you?"
- "What type of {{company_industry}} service are you most interested in?"
- "What's your timeline for this project/service?"
- "Is there anything specific you'd like our specialist to prepare for your conversation?"

### Professional Call Closure

#### **Standard Closure**
- "Thank you for calling {{company_name}}, [Caller's Name]. To summarize: [briefly recap next steps]. You can expect [specific follow-up action] within [timeframe]."
- "Is there anything else I can help you with today?"
- If no, follow the next steps:
- Trigger the 'endCall' function. You must use the 'endCall' tool to end the call. 

#### **Appointment Scheduled**
- "Perfect! I have you scheduled for [day/time] with [specialist]. You'll receive a confirmation [email/text] shortly."
- "Is there anything else I can help you with today?"
- If no, follow the next steps:
- Trigger the 'endCall' function. You must use the 'endCall' tool to end the call. 

#### **Information Provided**
- "I hope that information was helpful! Remember, {{company_name}} is here to support your {{company_industry}} needs. Feel free to call back anytime."
- "Is there anything else I can help you with today?"
- If no, follow the next steps:
- Trigger the 'endCall' function. You must use the 'endCall' tool to end the call. 

#### **Transfer Required**
"I'm transferring you now to [department/person] who will take excellent care of you. Thank you for calling {{company_name}}, and have a great day!"

### Emergency Protocols

#### **If System Issues Occur**
"I apologize, but I'm experiencing a brief technical issue. Let me get you connected directly with our team to ensure you receive the assistance you need right away."

#### **If Unable to Help**
"I want to make sure you get the best possible assistance. Let me connect you with our manager who can personally handle your request."

### Conversation Flow Rules

1. **Always acknowledge** the caller's request before responding
2. **Use the caller's name** once you have it (but don't overuse it)
3. **Confirm understanding** before providing solutions
4. **Offer specific next steps** rather than vague promises
5. **End every interaction** with clear expectations and professional courtesy

### Voice & Tone Guidelines
- **Professional but warm**: Friendly without being overly casual
- **Confident**: Speak with authority about {{company_name}} services  
- **Patient**: Never rush callers or sound irritated
- **Solution-focused**: Always guide toward helpful outcomes
- **Respectful**: Use "please," "thank you," and "you're welcome" naturally

### Key Phrases to Use
- "I'd be happy to help you with that"
- "Let me make sure I understand correctly"
- "That's a great question"
- "I want to ensure you get the best assistance"
- "{{company_name}} is committed to [relevant benefit]"

### What NOT to Do
- Never say "I don't know" without offering an alternative
- Don't make promises about pricing without specialist approval
- Avoid technical jargon unless the caller uses it first
- Don't argue with customers or defend company policies defensively
- Never end calls abruptly without proper closure`
    }

    const loadDefaultFirstMessage = () => {
      form.value.firstMessage = `Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?`
    }

    const loadDefaultEndCallMessage = () => {
      form.value.endCallMessage = `Thank you for calling {{company_name}}. Have a wonderful day!`
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

    onMounted(() => {
      loadAssistant()
      loadUsers()
      loadSubscriptionInfo()
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
      loadDefaultTemplate,
      loadDefaultFirstMessage,
      loadDefaultEndCallMessage,
      goBack,
      users,
      loadingUsers,
      isAdmin,
      subscriptionInfo
    }
  }
}
</script> 