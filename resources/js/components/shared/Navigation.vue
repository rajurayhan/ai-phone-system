<template>
  <nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <router-link to="/dashboard" class="flex items-center hover:opacity-80 transition-opacity">
              <div v-if="settings.logo_url" class="h-8 w-auto">
                <img :src="settings.logo_url" :alt="settings.site_title" class="h-full w-auto">
              </div>
              <div v-else class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </div>
              <div class="ml-2">
                <h1 class="text-xl font-bold text-gray-900">{{ settings.site_title || 'XpartFone' }}</h1>
              </div>
            </router-link>
          </div>
          <!-- Desktop Navigation -->
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <router-link 
              to="/dashboard" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/dashboard' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Dashboard
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/assistants" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/assistants' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              My Assistants
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/demo-request" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/demo-request' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Request Demo
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/users" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/users') 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Users
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/assistants" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/assistants') 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              All Assistants
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/demo-requests" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/demo-requests') 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Demo Requests
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/system-settings" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/system-settings') 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              System Settings
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/subscription" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/subscription' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Subscription
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/transactions" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/transactions' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Transactions
            </router-link>
            <!-- Config Menu for Admin -->
            <div v-if="isAdmin" class="relative inline-flex items-center">
              <button 
                @click="configMenuOpen = !configMenuOpen"
                :class="[
                  'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                  $route.path.startsWith('/admin/features') || $route.path.startsWith('/admin/packages') || $route.path.startsWith('/admin/subscriptions') || $route.path.startsWith('/admin/transactions')
                    ? 'border-green-500 text-green-600' 
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                Config
                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div v-if="configMenuOpen" class="absolute right-0 top-full mt-1 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
                <router-link to="/admin/features" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Features</router-link>
                <router-link to="/admin/packages" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Packages</router-link>
                <router-link to="/admin/subscriptions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Subscriptions</router-link>
                <router-link to="/admin/transactions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transactions</router-link>
                <router-link to="/admin/templates" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Templates</router-link>
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center">
          <!-- Mobile menu button -->
          <div class="sm:hidden">
            <button 
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500"
            >
              <span class="sr-only">Open main menu</span>
              <svg 
                :class="mobileMenuOpen ? 'hidden' : 'block'"
                class="h-6 w-6" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg 
                :class="mobileMenuOpen ? 'block' : 'hidden'"
                class="h-6 w-6" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Desktop user menu -->
          <div class="hidden sm:ml-3 sm:relative">
            <div>
              <button @click="userMenuOpen = !userMenuOpen" class="max-w-xs bg-gray-100 flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <span class="sr-only">Open user menu</span>
                <div v-if="user.profile_picture" class="h-8 w-8 rounded-full overflow-hidden">
                  <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                </div>
                <div v-else class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center">
                  <span class="text-white font-medium">{{ userInitials }}</span>
                </div>
              </button>
            </div>
            <div v-if="userMenuOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
              <div class="px-4 py-2 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                <p class="text-xs text-gray-500">{{ user.email }}</p>
              </div>
              <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</router-link>
              <router-link v-if="!isAdmin" to="/pricing" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pricing</router-link>
              <router-link v-if="!isAdmin" to="/subscription" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Subscription</router-link>
              <router-link v-if="!isAdmin" to="/transactions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transactions</router-link>
              <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-if="mobileMenuOpen" class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1 border-t border-gray-200">
          <router-link 
            to="/dashboard" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path === '/dashboard' 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            Dashboard
          </router-link>
          <router-link 
            v-if="!isAdmin"
            to="/assistants" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path === '/assistants' 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            My Assistants
          </router-link>
          <router-link 
            v-if="!isAdmin"
            to="/demo-request" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path === '/demo-request' 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            Request Demo
          </router-link>
          <router-link 
            v-if="isAdmin"
            to="/admin/users" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path.startsWith('/admin/users') 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            Users
          </router-link>
          <router-link 
            v-if="isAdmin"
            to="/admin/assistants" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path.startsWith('/admin/assistants') 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            All Assistants
          </router-link>
          <router-link 
            v-if="isAdmin"
            to="/admin/demo-requests" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path.startsWith('/admin/demo-requests') 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            Demo Requests
          </router-link>
          <router-link 
            v-if="isAdmin"
            to="/admin/system-settings" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path.startsWith('/admin/system-settings') 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            System Settings
          </router-link>
          <router-link 
            v-if="!isAdmin"
            to="/subscription" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path === '/subscription' 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            Subscription
          </router-link>
          <router-link 
            v-if="!isAdmin"
            to="/transactions" 
            :class="[
              'block px-3 py-2 rounded-md text-base font-medium',
              $route.path === '/transactions' 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
          >
            Transactions
          </router-link>
          
          <!-- Mobile Config Menu for Admin -->
          <div v-if="isAdmin" class="border-t border-gray-200 pt-4">
            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Configuration
            </div>
            <router-link to="/admin/features" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Features</router-link>
            <router-link to="/admin/packages" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Packages</router-link>
            <router-link to="/admin/subscriptions" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Subscriptions</router-link>
            <router-link to="/admin/transactions" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Transactions</router-link>
            <router-link to="/admin/templates" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Templates</router-link>
          </div>
        </div>
        
        <!-- Mobile user menu -->
        <div class="pt-4 pb-3 border-t border-gray-200">
          <div class="flex items-center px-4">
            <div v-if="user.profile_picture" class="h-10 w-10 rounded-full overflow-hidden">
              <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
            </div>
            <div v-else class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
              <span class="text-white font-medium">{{ userInitials }}</span>
            </div>
            <div class="ml-3">
              <div class="text-base font-medium text-gray-800">{{ user.name }}</div>
              <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
            </div>
          </div>
          <div class="mt-3 space-y-1">
            <router-link to="/profile" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Your Profile</router-link>
            <router-link v-if="!isAdmin" to="/pricing" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Pricing</router-link>
            <router-link v-if="!isAdmin" to="/subscription" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Subscription</router-link>
            <router-link v-if="!isAdmin" to="/transactions" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Transactions</router-link>
            <button @click="logout" class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Sign out</button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { getSystemSettings } from '../../utils/systemSettings.js'

export default {
  name: 'Navigation',
  data() {
    return {
      userMenuOpen: false,
      configMenuOpen: false,
      mobileMenuOpen: false,
      user: JSON.parse(localStorage.getItem('user') || '{}'),
      settings: {
        site_title: 'XpartFone',
        logo_url: '/logo.png'
      }
    }
  },
  computed: {
    userInitials() {
      return this.user.name ? this.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    },
    isAdmin() {
      return this.user.role === 'admin';
    }
  },
  methods: {
    async loadSettings() {
      try {
        const settings = await getSystemSettings()
        this.settings = settings
        // Cache settings in localStorage for faster loading
        localStorage.setItem('settings', JSON.stringify(settings))
      } catch (error) {
        console.error('Error loading system settings:', error)
      }
    },
    async logout() {
      try {
        // Call logout API
        const response = await fetch('/api/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        // Clear local storage
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        
        // Redirect to login
        this.$router.push('/login');
      } catch (error) {
        console.error('Logout error:', error);
        // Even if API call fails, clear local storage and redirect
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        this.$router.push('/login');
      }
    }
  },
  mounted() {
    this.loadSettings()
    
    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.userMenuOpen = false;
        this.configMenuOpen = false;
        this.mobileMenuOpen = false;
      }
    });
  }
}
</script> 